<?php
declare(strict_types=1);

/*
 * Paiement en ligne des dons.
 *
 * - Prestataires : Stripe (carte bancaire, prélèvement automatique pour les dons réguliers),
 *   MVola et Orange Money (Mobile Money Madagascar : chaque échéance d'un don régulier est
 *   payée par le donateur après un rappel par e-mail). Fournisseur « test » en développement.
 * - Les clés secrètes sont UNIQUEMENT dans app/config.php (« payments »), jamais en base ni dans l'admin.
 *   L'admin ne fait qu'activer / désactiver chaque moyen et régler les montants proposés.
 * - Tables : donations (chaque paiement), donation_plans (engagements réguliers), payment_events (journal anti-doublon).
 */

require_once APP . '/mailer.php';
require_once APP . '/payments/provider.php';
require_once APP . '/payments/stripe.php';
require_once APP . '/payments/mvola.php';
require_once APP . '/payments/orange_money.php';
require_once APP . '/payments/fake.php';

/** Fréquences proposées : [unité, nombre d'unités entre deux échéances]. */
const PAY_FREQUENCIES = [
    'once'      => null,
    'monthly'   => ['month', 1],
    'quarterly' => ['month', 3],
    'yearly'    => ['year', 1],
];

/** Devises sans sous-unité chez Stripe (montant envoyé tel quel). */
const ZERO_DECIMAL_CURRENCIES = ['MGA', 'BIF', 'CLP', 'DJF', 'GNF', 'JPY', 'KMF', 'KRW', 'PYG', 'RWF', 'UGX', 'VND', 'VUV', 'XAF', 'XOF', 'XPF'];

function frequency_label(string $f): string
{
    return match ($f) {
        'monthly'   => __('Chaque mois'),
        'quarterly' => __('Tous les 3 mois'),
        'yearly'    => __('Chaque année'),
        default     => __('Une seule fois'),
    };
}

/** Fréquences activées dans l'admin (« une fois » toujours proposé). */
function enabled_frequencies(): array
{
    $out = ['once'];
    foreach (['monthly', 'quarterly', 'yearly'] as $f) {
        if (setting('pay_freq_' . $f, '1') === '1') {
            $out[] = $f;
        }
    }
    return $out;
}

/** Date de l'échéance suivante. */
function next_due(string $from, string $frequency): string
{
    [$unit, $n] = PAY_FREQUENCIES[$frequency] ?? ['month', 1];
    return date('Y-m-d', strtotime("$from +$n $unit"));
}

/* ---------- Devises ---------- */

/** Code ISO 4217 à partir de la devise saisie dans l'admin (« Ar », « € », « $ », « EUR »…). */
function currency_iso(string $c): string
{
    $k = mb_strtolower(trim($c));
    return match (true) {
        in_array($k, ['ar', 'mga', 'ariary', 'ariary malgache'], true) => 'MGA',
        in_array($k, ['€', 'eur', 'euro', 'euros'], true) => 'EUR',
        in_array($k, ['$', 'usd', 'us$', 'dollar', 'dollars'], true) => 'USD',
        in_array($k, ['£', 'gbp'], true) => 'GBP',
        in_array($k, ['chf', 'fr.'], true) => 'CHF',
        (bool) preg_match('/^[a-z]{3}$/', $k) => strtoupper($k),
        default => 'MGA',
    };
}

/** Symbole d'affichage d'un code ISO. */
function currency_symbol(string $iso): string
{
    return ['MGA' => 'Ar', 'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'CHF' => 'CHF'][$iso] ?? $iso;
}

/** Montant en plus petite unité (centimes) pour l'API de paiement. */
function to_minor(float $amount, string $iso): int
{
    return in_array($iso, ZERO_DECIMAL_CURRENCIES, true) ? (int) round($amount) : (int) round($amount * 100);
}

function from_minor(int $minor, string $iso): float
{
    return in_array($iso, ZERO_DECIMAL_CURRENCIES, true) ? (float) $minor : $minor / 100;
}

/** Montants suggérés pour une devise (réglés dans l'admin). */
function preset_amounts(string $iso): array
{
    $raw = $iso === 'MGA' ? setting('pay_presets_mga', '10000, 25000, 50000, 100000') : setting('pay_presets_other', '10, 25, 50, 100');
    $out = [];
    foreach (preg_split('/[;,]+/', $raw) as $v) {
        $v = (float) str_replace([' ', "\u{a0}"], '', $v);
        if ($v > 0) {
            $out[] = $v;
        }
    }
    return array_slice($out, 0, 6);
}

function min_amount(string $iso): float
{
    return (float) ($iso === 'MGA' ? setting('pay_min_mga', '1000') : setting('pay_min_other', '1'));
}

/** Devises proposées pour le paiement par carte (réglage de l'admin). */
function card_currencies(): array
{
    $list = [];
    foreach (preg_split('/[\s,;]+/', setting('pay_card_currencies', 'EUR, USD, MGA')) as $c) {
        $c = strtoupper(trim($c));
        if (preg_match('/^[A-Z]{3}$/', $c)) {
            $list[] = $c;
        }
    }
    return array_values(array_unique($list)) ?: ['EUR'];
}

/* ---------- Prestataires ---------- */

/** @return PaymentProvider[] tous les prestataires connus, indexés par identifiant. */
function payment_providers(): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        foreach ([new StripeProvider(), new MvolaProvider(), new OrangeMoneyProvider(), new FakeProvider()] as $p) {
            $all[$p->id()] = $p;
        }
    }
    return $all;
}

function payment_provider(string $id): ?PaymentProvider
{
    return payment_providers()[$id] ?? null;
}

/** Prestataires utilisables : configurés (clés dans config.php) ET activés dans l'admin. */
function available_providers(): array
{
    return array_filter(payment_providers(), fn (PaymentProvider $p) => $p->isConfigured() && $p->isEnabled());
}

function online_payments_enabled(): bool
{
    try {
        return (bool) available_providers();
    } catch (Throwable) {
        return false;
    }
}

/** Le site tourne-t-il avec des clés de test (bandeau « mode test ») ? */
function payments_test_mode(): bool
{
    foreach (available_providers() as $p) {
        if ($p->isTest()) {
            return true;
        }
    }
    return false;
}

/* ---------- Références et enregistrements ---------- */

function payment_reference(string $prefix): string
{
    return 'UAZ-' . $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
}

function find_donation(string $ref): ?array
{
    return fetch_one('SELECT * FROM donations WHERE reference = ?', [$ref]);
}

function find_plan_by_token(string $token): ?array
{
    return preg_match('/^[a-f0-9]{32,64}$/', $token) ? fetch_one('SELECT * FROM donation_plans WHERE token = ?', [$token]) : null;
}

/** Enregistre un événement reçu d'un prestataire. Renvoie false s'il a déjà été traité (doublon). */
function record_payment_event(string $provider, string $eventId, string $type, string $payload): bool
{
    try {
        q('INSERT INTO payment_events (provider, event_id, event_type, payload, created_at) VALUES (?, ?, ?, ?, ?)',
            [$provider, mb_substr($eventId, 0, 190), mb_substr($type, 0, 100), mb_substr($payload, 0, 60000), now()]);
        return true;
    } catch (PDOException) {
        return false;
    }
}

/** Fusionne des informations techniques du prestataire dans donations.provider_data (JSON). */
function donation_data(array $d, array $merge = []): array
{
    $data = json_decode((string) ($d['provider_data'] ?? ''), true) ?: [];
    if ($merge) {
        $data = array_merge($data, $merge);
        q('UPDATE donations SET provider_data = ?, updated_at = ? WHERE id = ?', [json_encode($data, JSON_UNESCAPED_SLASHES), now(), $d['id']]);
    }
    return $data;
}

/**
 * Crée un paiement (et l'engagement régulier si besoin). $in : donor_name, email, phone, country, amount,
 * currency (ISO), provider, frequency, project_id, message. $plan : engagement existant (échéance d'un don régulier).
 */
function create_donation(array $in, ?array $plan = null): array
{
    $now = now();
    if ($plan === null && $in['frequency'] !== 'once') {
        q('INSERT INTO donation_plans (reference, token, project_id, donor_name, email, phone, country, amount, currency, provider, frequency, status, lang, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
            payment_reference('R'), bin2hex(random_bytes(20)), $in['project_id'] ?: null, $in['donor_name'], $in['email'], $in['phone'] ?: null,
            $in['country'] ?: null, $in['amount'], $in['currency'], $in['provider'], $in['frequency'], 'pending', lang(), $now,
        ]);
        $plan = fetch_one('SELECT * FROM donation_plans WHERE id = ?', [(int) db()->lastInsertId()]);
    }
    $ref = payment_reference('D');
    q('INSERT INTO donations (reference, plan_id, project_id, donor_name, email, phone, country, amount, currency, provider, frequency, status, message, lang, ip, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $ref, $plan['id'] ?? null, ($plan['project_id'] ?? $in['project_id']) ?: null, $in['donor_name'], $in['email'], $in['phone'] ?: null,
        $in['country'] ?: null, $in['amount'], $in['currency'], $in['provider'], $plan['frequency'] ?? 'once', 'pending', $in['message'] ?: null, lang(), client_ip(), $now,
    ]);
    return [find_donation($ref), $plan];
}

/**
 * Marque un paiement comme réussi (idempotent). Active l'engagement régulier, avance la prochaine
 * échéance (Mobile Money), ajoute le montant au projet si l'option est active, envoie le reçu.
 */
function mark_donation_paid(array $d, ?string $providerRef = null, array $data = []): void
{
    $d = find_donation($d['reference']) ?? $d;
    if ($d['status'] === 'paid') {
        return;
    }
    $now = now();
    q('UPDATE donations SET status = ?, paid_at = ?, updated_at = ?, provider_ref = COALESCE(?, provider_ref) WHERE id = ? AND status <> ?',
        ['paid', $now, $now, $providerRef, $d['id'], 'paid']);
    if ($data) {
        donation_data($d, $data);
    }
    $d = find_donation($d['reference']);

    if ($d['plan_id']) {
        $plan = fetch_one('SELECT * FROM donation_plans WHERE id = ?', [$d['plan_id']]);
        if ($plan) {
            $provider = payment_provider($plan['provider']);
            $auto = $provider && $provider->supportsAutoRecurring();
            // Échéance suivante : calculée depuis l'échéance prévue (garde le rythme), ou depuis aujourd'hui pour le 1er paiement.
            $base = $plan['next_due_date'] && $plan['status'] === 'active' ? $plan['next_due_date'] : date('Y-m-d');
            q('UPDATE donation_plans SET status = ?, last_paid_at = ?, payments_count = payments_count + 1, next_due_date = ?,
               reminded_for = NULL, reminder_count = 0, updated_at = ? WHERE id = ?',
                [$plan['status'] === 'cancelled' ? 'cancelled' : 'active', $now, next_due($base, $plan['frequency']), $now, $plan['id']]);
            if ($auto) {
                // Prélèvement automatique : la date indicative vient du prestataire, pas de rappel.
                q('UPDATE donation_plans SET reminded_for = next_due_date WHERE id = ?', [$plan['id']]);
            }
        }
    }

    add_donation_to_project($d);
    send_donation_receipt($d);
    notify_admin('Don en ligne reçu — ' . money($d['amount'], currency_symbol($d['currency'])) . ' — ' . $d['donor_name'],
        "Référence : {$d['reference']}\nDonateur : {$d['donor_name']} <{$d['email']}>\nMontant : " . money($d['amount'], currency_symbol($d['currency']))
        . "\nMoyen : " . (payment_provider($d['provider'])?->label() ?? $d['provider']) . "\nFréquence : " . frequency_label($d['frequency'])
        . "\n\n" . absolute_url('admin/?p=donations'));
}

function mark_donation_failed(array $d, string $status = 'failed'): void
{
    q("UPDATE donations SET status = ?, updated_at = ? WHERE id = ? AND status = 'pending'", [$status, now(), $d['id']]);
}

/** Ajoute un paiement réussi au « montant déjà collecté » du projet (même devise uniquement, une seule fois). */
function add_donation_to_project(array $d): void
{
    if (setting('pay_add_to_project', '1') !== '1' || !$d['project_id'] || (int) $d['added_to_project'] === 1) {
        return;
    }
    $p = fetch_one('SELECT * FROM projects WHERE id = ?', [$d['project_id']]);
    if (!$p || currency_iso(project_currency($p)) !== $d['currency']) {
        return;
    }
    q('UPDATE projects SET raised_amount = COALESCE(raised_amount, 0) + ?, updated_at = ? WHERE id = ?', [$d['amount'], now(), $p['id']]);
    q('UPDATE donations SET added_to_project = 1 WHERE id = ?', [$d['id']]);
}

/** Annule un engagement régulier (et l'abonnement chez le prestataire s'il y en a un). */
function cancel_plan(array $plan, bool $callProvider = true): void
{
    if ($plan['status'] === 'cancelled') {
        return;
    }
    $provider = payment_provider($plan['provider']);
    if ($callProvider && $provider && $plan['provider_subscription']) {
        $provider->cancelPlan($plan);
    }
    q('UPDATE donation_plans SET status = ?, cancelled_at = ?, updated_at = ? WHERE id = ?', ['cancelled', now(), now(), $plan['id']]);
}

/* ---------- E-mails au donateur ---------- */

/** Exécute $fn dans la langue du donateur (e-mails envoyés depuis un webhook ou la tâche planifiée). */
function in_lang(string $lang, callable $fn): mixed
{
    $prev = lang();
    set_lang($lang);
    try {
        return $fn();
    } finally {
        set_lang($prev);
    }
}

function donate_page_url(string $sub = '', array $query = [], ?string $lang = null): string
{
    $lang ??= lang();
    $subs = ['thanks' => ['en' => 'thank-you', 'fr' => 'merci'], 'manage' => ['en' => 'manage', 'fr' => 'gerer'], 'pay' => ['en' => 'pay', 'fr' => 'payer']];
    return rtrim((string) config('site_url', ''), '/') . route_url('donate', ['slug' => $sub ? $subs[$sub][$lang] : null, 'query' => $query, 'lang' => $lang]);
}

function send_donation_receipt(array $d): void
{
    $plan = $d['plan_id'] ? fetch_one('SELECT * FROM donation_plans WHERE id = ?', [$d['plan_id']]) : null;
    in_lang($d['lang'] ?: DEFAULT_LANG, function () use ($d, $plan) {
        $amount = money($d['amount'], currency_symbol($d['currency']));
        $project = $d['project_id'] ? fetch_one('SELECT title, title_en FROM projects WHERE id = ?', [$d['project_id']]) : null;
        $lines = [
            __('Bonjour :name,', ['name' => $d['donor_name']]),
            '',
            __('Merci pour votre don de :amount à l\'Université Adventiste Zurcher.', ['amount' => $amount]),
            $project ? __('Projet soutenu : :p', ['p' => loc($project, 'title')]) : __('Votre don sera affecté là où le besoin est le plus grand.'),
            __('Référence : :ref', ['ref' => $d['reference']]),
            __('Date : :date', ['date' => format_date(date('Y-m-d'))]),
        ];
        if ($plan) {
            $provider = payment_provider($plan['provider']);
            $lines[] = '';
            $lines[] = __('Don régulier : :f.', ['f' => mb_strtolower(frequency_label($plan['frequency']))]);
            $lines[] = $provider && $provider->supportsAutoRecurring()
                ? __('Les prochains paiements seront prélevés automatiquement.')
                : __('À chaque échéance, nous vous enverrons un e-mail avec un lien pour payer en quelques secondes.');
            $lines[] = __('Pour modifier ou arrêter votre don régulier : :url', ['url' => donate_page_url('manage', ['t' => $plan['token']], $d['lang'])]);
        }
        $lines[] = '';
        $lines[] = __('Pour obtenir un reçu officiel, répondez simplement à cet e-mail.');
        $lines[] = '';
        $lines[] = __('Avec toute notre reconnaissance,');
        $lines[] = setting_t('site_name', 'Université Adventiste Zurcher');
        send_mail($d['email'], __('Merci pour votre don — :ref', ['ref' => $d['reference']]), implode("\n", $lines));
    });
}

/** Rappel d'échéance d'un don régulier payé par Mobile Money (envoyé par la tâche planifiée). */
function send_plan_reminder(array $plan): bool
{
    return in_lang($plan['lang'] ?: DEFAULT_LANG, function () use ($plan) {
        $amount = money($plan['amount'], currency_symbol($plan['currency']));
        $lines = [
            __('Bonjour :name,', ['name' => $plan['donor_name']]),
            '',
            __('L\'échéance de votre don régulier (:f) de :amount est arrivée.', ['f' => mb_strtolower(frequency_label($plan['frequency'])), 'amount' => $amount]),
            __('Vous pouvez la régler en quelques secondes ici : :url', ['url' => donate_page_url('pay', ['t' => $plan['token']], $plan['lang'])]),
            '',
            __('Pour modifier ou arrêter votre don régulier : :url', ['url' => donate_page_url('manage', ['t' => $plan['token']], $plan['lang'])]),
            '',
            __('Merci pour votre fidélité,'),
            setting_t('site_name', 'Université Adventiste Zurcher'),
        ];
        return send_mail($plan['email'], __('Rappel : votre don régulier à l\'UAZ'), implode("\n", $lines));
    });
}

/**
 * Tâche quotidienne : rappels des échéances Mobile Money (le jour J puis 7 jours après si toujours impayé),
 * expiration des paiements restés en attente plus de 2 jours.
 */
function run_payment_reminders(?string $today = null): array
{
    $today ??= date('Y-m-d');
    $sent = 0;
    $plans = fetch_all("SELECT * FROM donation_plans WHERE status = 'active' AND next_due_date IS NOT NULL AND next_due_date <= ?", [$today]);
    foreach ($plans as $plan) {
        $provider = payment_provider($plan['provider']);
        if ($provider && $provider->supportsAutoRecurring()) {
            continue;
        }
        $first = $plan['reminded_for'] !== $plan['next_due_date'];
        $second = !$first && (int) $plan['reminder_count'] < 2 && $today >= date('Y-m-d', strtotime($plan['next_due_date'] . ' +7 days'));
        if (!$first && !$second) {
            continue;
        }
        send_plan_reminder($plan);
        q('UPDATE donation_plans SET reminded_for = ?, reminder_count = ?, updated_at = ? WHERE id = ?',
            [$plan['next_due_date'], $first ? 1 : 2, now(), $plan['id']]);
        $sent++;
    }
    $expired = q("UPDATE donations SET status = 'expired', updated_at = ? WHERE status = 'pending' AND created_at < ?", [now(), date('Y-m-d H:i:s', time() - 172800)])->rowCount();
    q("UPDATE donation_plans SET status = 'expired', updated_at = ? WHERE status = 'pending' AND created_at < ?", [now(), date('Y-m-d H:i:s', time() - 172800)]);
    return ['reminders' => $sent, 'expired' => $expired];
}

function plan_status_label(string $s): string
{
    return match ($s) {
        'active'    => __('Actif'),
        'pending'   => __('En attente du 1er paiement'),
        'cancelled' => __('Arrêté'),
        'expired'   => __('Non finalisé'),
        default     => $s,
    };
}

function donation_status_label(string $s): string
{
    return match ($s) {
        'paid'      => __('Payé'),
        'pending'   => __('En attente'),
        'failed'    => __('Échoué'),
        'cancelled' => __('Annulé'),
        'expired'   => __('Expiré'),
        default     => $s,
    };
}

/** Résumé pour l'admin : état de chaque prestataire (clés présentes dans config.php, mode test, adresse du webhook). */
function payment_admin_status(): string
{
    $lines = [];
    foreach (payment_providers() as $p) {
        if ($p->id() === 'test' && !$p->isConfigured()) {
            continue;
        }
        $state = !$p->isConfigured() ? 'clés absentes de app/config.php — inactif'
            : ($p->isTest() ? 'configuré en MODE TEST (aucun paiement réel)' : 'configuré en production');
        $lines[] = $p->label() . ' : ' . $state . '.';
    }
    $lines[] = 'Webhook Stripe à déclarer : ' . rtrim((string) config('site_url', ''), '/') . url('paiement/stripe/webhook') . '.';
    $lines[] = 'Les clés secrètes se saisissent uniquement sur le serveur, dans app/config.php (rubrique « payments »).';
    $lines[] = config('mail_notifications') ? (config('smtp')['host'] ?? false ? 'E-mails : envoi par SMTP actif.' : 'E-mails : envoi par la fonction mail() du serveur.')
        : 'E-mails désactivés (mail_notifications = false) : reçus et rappels ne partent pas — à configurer (rubrique « smtp »).';
    return implode(' ', $lines);
}
