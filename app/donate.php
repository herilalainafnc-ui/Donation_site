<?php
declare(strict_types=1);

/*
 * Pages du don en ligne (/donate, /fr/faire-un-don) et routes techniques des prestataires (/paiement/…).
 *   /faire-un-don                 formulaire (ponctuel ou régulier)
 *   /faire-un-don/merci?ref=…      résultat du paiement (suivi automatique si en attente)
 *   /faire-un-don/gerer?t=…        gestion d'un don régulier par le donateur (arrêt)
 *   /faire-un-don/payer?t=…        paiement d'une échéance (lien envoyé dans le rappel Mobile Money)
 *   /paiement/stripe/webhook       notifications Stripe (signées)
 *   /paiement/mvola/callback/REF   rappel MVola
 *   /paiement/orange/notification/REF  notification Orange Money
 *   /paiement/statut/REF           statut JSON pour la page d'attente
 *   /paiement/test/REF             page de paiement simulée (développement uniquement)
 */

const DONATE_SUBS = [
    'thanks' => ['en' => 'thank-you', 'fr' => 'merci'],
    'manage' => ['en' => 'manage', 'fr' => 'gerer'],
    'pay'    => ['en' => 'pay', 'fr' => 'payer'],
];

/** Sous-page du don en ligne correspondant à un segment d'URL (toutes langues). */
function donate_sub(?string $segment): ?string
{
    foreach (DONATE_SUBS as $key => $names) {
        if (in_array($segment, $names, true)) {
            return $key;
        }
    }
    return null;
}

/** Lien « Faire un don » : le don en ligne s'il est actif, sinon la page « Comment donner ». */
function give_url(?string $projectSlug = null): string
{
    $query = $projectSlug ? ['project' => $projectSlug] : [];
    return online_payments_enabled()
        ? route_url('donate', ['query' => $query])
        : route_url('give', ['query' => $query, 'hash' => $projectSlug ? 'promesse' : null]);
}

/* ---------- Routes techniques ---------- */

function route_payment_endpoint(array $parts): void
{
    $raw = (string) file_get_contents('php://input');
    $kind = $parts[0] ?? '';
    $ref = $parts[2] ?? ($parts[1] ?? null);
    try {
        switch ($kind) {
            case 'stripe':
                if (($parts[1] ?? '') !== 'webhook' || ($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
                    break;
                }
                payment_provider('stripe')->webhook($raw, null);
                exit('ok');
            case 'mvola':
                if (($parts[1] ?? '') !== 'callback' || !$ref) {
                    break;
                }
                payment_provider('mvola')->webhook($raw, $ref);
                exit('ok');
            case 'orange':
                if (($parts[1] ?? '') !== 'notification' || !$ref) {
                    break;
                }
                payment_provider('orange_money')->webhook($raw, $ref);
                exit('ok');
            case 'statut':
                payment_status_json((string) ($parts[1] ?? ''));
                return;
            case 'test':
                fake_payment_page((string) ($parts[1] ?? ''));
                return;
        }
    } catch (Throwable $ex) {
        error_log('Paiement (' . $kind . ') : ' . $ex);
        http_response_code(500);
        exit('erreur');
    }
    http_response_code(404);
    exit('introuvable');
}

/** Statut d'un paiement pour la page d'attente (interroge le prestataire au plus toutes les 4 secondes). */
function payment_status_json(string $ref): void
{
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    $d = preg_match('/^UAZ-D-[0-9]{6}-[A-F0-9]{6}$/', $ref) ? find_donation($ref) : null;
    if (!$d) {
        http_response_code(404);
        exit('{"status":"unknown"}');
    }
    if ($d['status'] === 'pending' && strtotime((string) ($d['updated_at'] ?: $d['created_at'])) < time() - 4) {
        q('UPDATE donations SET updated_at = ? WHERE id = ?', [now(), $d['id']]);
        try {
            payment_provider($d['provider'])?->refresh($d);
        } catch (Throwable $ex) {
            error_log('Vérification du paiement ' . $ref . ' : ' . $ex->getMessage());
        }
        $d = find_donation($ref);
    }
    exit(json_encode(['status' => $d['status']]));
}

/** Page de paiement simulée (prestataire « test », développement local uniquement). */
function fake_payment_page(string $ref): void
{
    $fake = payment_provider('test');
    $d = find_donation($ref);
    if (!$fake || !$fake->isConfigured() || !$d || $d['provider'] !== 'test') {
        http_response_code(404);
        exit('introuvable');
    }
    set_lang($d['lang'] ?: DEFAULT_LANG);
    if (is_post()) {
        csrf_verify();
        ($_POST['result'] ?? '') === 'success' ? mark_donation_paid($d, 'TEST-' . strtoupper(bin2hex(random_bytes(4)))) : mark_donation_failed($d);
        redirect(donate_page_url('thanks', ['ref' => $ref]));
    }
    header('Content-Type: text/html; charset=utf-8');
    echo '<!doctype html><html lang="fr"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Paiement de test</title>'
        . '<body style="font-family:system-ui;max-width:460px;margin:60px auto;padding:0 20px">'
        . '<h1>Paiement de test</h1><p>Aucun argent réel. Référence <b>' . e($ref) . '</b> — ' . e(money($d['amount'], currency_symbol($d['currency']))) . '</p>'
        . '<form method="post">' . csrf_field()
        . '<button name="result" value="success" style="padding:12px 20px;margin-right:10px">Simuler un paiement réussi</button>'
        . '<button name="result" value="fail" style="padding:12px 20px">Simuler un échec</button></form></body></html>';
    exit;
}

/* ---------- Pages publiques ---------- */

function page_donate(?string $sub): void
{
    $key = $sub === null ? null : donate_sub($sub);
    if ($sub !== null && $key === null) {
        page_not_found();
        return;
    }
    if ($key === 'thanks') {
        page_donate_status();
        return;
    }
    if ($key === 'manage') {
        page_donate_manage();
        return;
    }
    if ($key === 'pay') {
        page_donate_installment();
        return;
    }
    if (!online_payments_enabled()) {
        redirect(route_url('give'));
    }
    if (is_post()) {
        handle_donate_post(null, route_url('donate'));
    }
    if (!empty($_GET['annule']) && is_string($_GET['annule']) && ($d = find_donation($_GET['annule'])) && $d['status'] === 'pending') {
        mark_donation_failed($d, 'cancelled');
        flash('error', __('Le paiement a été annulé. Vous pouvez réessayer quand vous voulez.'));
    }
    $projects = give_project_options();
    render_page('donate', [
        'nav' => 'give', 'pageTitle' => __('Faire un don en ligne'),
        'metaDescription' => __('Faites un don en ligne à l\'Université Adventiste Zurcher : carte bancaire ou Mobile Money, une seule fois ou chaque mois.'),
        'providers' => available_providers(),
        'projectOptions' => $projects,
        'selected' => (string) ($_GET['project'] ?? $_GET['projet'] ?? ''),
    ]);
}

/**
 * Valide le formulaire, crée le paiement et envoie le donateur chez le prestataire.
 * $plan : échéance d'un don régulier existant (montant, devise et donateur imposés).
 */
function handle_donate_post(?array $plan, string $back): never
{
    csrf_verify();
    $in = fn (string $k, int $max = 190) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $_SESSION['_old'] = $_POST;
    unset($_SESSION['_old']['_csrf'], $_SESSION['_old']['_t']);
    if (($_POST['website'] ?? '') !== '') {
        redirect($back);
    }
    if (!form_time_ok(2)) {
        flash('error', __('Merci de vérifier vos informations puis de renvoyer le formulaire.'));
        redirect($back);
    }
    $errors = [];
    $provider = payment_provider($in('provider', 30));
    if (!$provider || !isset(available_providers()[$provider->id()])) {
        $errors[] = __('Choisissez un moyen de paiement.');
    }

    if ($plan) {
        $data = [
            'donor_name' => $plan['donor_name'], 'email' => $plan['email'], 'country' => $plan['country'], 'amount' => (float) $plan['amount'],
            'currency' => $plan['currency'], 'frequency' => $plan['frequency'], 'project_id' => $plan['project_id'], 'message' => null,
        ];
        if ($provider && $provider->supportsAutoRecurring()) {
            $errors[] = __('Choisissez un moyen de paiement.');
        }
    } else {
        $amountRaw = $in('amount_custom', 30) !== '' ? $in('amount_custom', 30) : $in('amount', 30);
        $amount = (float) str_replace([' ', "\u{a0}", "\u{202f}", ','], ['', '', '', '.'], $amountRaw);
        $data = [
            'donor_name' => $in('name', 150), 'email' => $in('email'), 'country' => $in('country', 100),
            'amount' => round($amount, 2), 'currency' => strtoupper($in('currency', 3)), 'frequency' => $in('frequency', 12) ?: 'once',
            'project_id' => ctype_digit($in('project_id', 10)) ? (fetch_value("SELECT id FROM projects WHERE id = ? AND is_published = 1", [(int) $in('project_id', 10)]) ?: null) : null,
            'message' => $in('message', 2000),
        ];
        if (mb_strlen($data['donor_name']) < 2 || preg_match('#https?://|[<>]#i', $data['donor_name'])) {
            $errors[] = __('Indiquez votre nom.');
        }
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('Indiquez une adresse e-mail valide.');
        }
        if (!in_array($data['frequency'], enabled_frequencies(), true)) {
            $data['frequency'] = 'once';
        }
        if ($provider && $provider->kind() === 'mobile') {
            $data['currency'] = 'MGA';
        }
        if ($provider && !in_array($data['currency'], $provider->currencies(), true)) {
            $errors[] = __('Cette devise n\'est pas acceptée par ce moyen de paiement.');
        } elseif ($data['amount'] < min_amount($data['currency']) || $data['amount'] > 1e9) {
            $errors[] = __('Le montant minimum est de :min.', ['min' => money(min_amount($data['currency']), currency_symbol($data['currency']))]);
        }
        if (looks_like_spam($data['message'])) {
            $errors[] = __('Votre message contient trop de liens. Retirez-les puis renvoyez-le.');
        }
    }
    $data['phone'] = $in('phone', 30) ?: ($plan['phone'] ?? '');
    $data['provider'] = $provider?->id() ?? '';
    if ($provider && $provider->kind() === 'mobile' && $provider->id() !== 'test' && !preg_match('/^03[2-9]\d{7}$/', mobile_number_local($data['phone']))) {
        $errors[] = __('Indiquez votre numéro :name (ex. 034 12 345 67).', ['name' => $provider->label()]);
    }
    $recent = (int) fetch_value('SELECT COUNT(*) FROM donations WHERE ip = ? AND created_at > ?', [client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
    if ($recent >= 10) {
        $errors[] = __('Trop d\'envois depuis votre connexion. Merci de réessayer plus tard.');
    }
    if ($errors) {
        foreach (array_unique($errors) as $err) {
            flash('error', $err);
        }
        redirect($back);
    }

    [$donation, $plan] = create_donation($data, $plan);
    try {
        $next = $provider->start($donation, $plan);
    } catch (RuntimeException $ex) {
        mark_donation_failed($donation);
        flash('error', __('Le paiement n\'a pas pu être lancé. Réessayez dans un instant ou choisissez un autre moyen de paiement.'));
        error_log('Lancement du paiement ' . $donation['reference'] . ' : ' . $ex->getMessage());
        redirect($back);
    }
    clear_old();
    if (!empty($next['redirect'])) {
        header('Location: ' . $next['redirect'], true, 303);
        exit;
    }
    redirect(donate_page_url('thanks', ['ref' => $donation['reference']]));
}

function page_donate_status(): void
{
    $ref = (string) ($_GET['ref'] ?? '');
    $d = preg_match('/^UAZ-D-[0-9]{6}-[A-F0-9]{6}$/', $ref) ? find_donation($ref) : null;
    if (!$d) {
        page_not_found();
        return;
    }
    if ($d['status'] === 'pending') {
        try {
            payment_provider($d['provider'])?->refresh($d);
        } catch (Throwable $ex) {
            error_log('Vérification du paiement ' . $ref . ' : ' . $ex->getMessage());
        }
        $d = find_donation($ref);
    }
    header('X-Robots-Tag: noindex');
    render_page('donate_status', [
        'nav' => 'give', 'pageTitle' => $d['status'] === 'paid' ? __('Merci pour votre don') : __('Suivi de votre don'), 'noindex' => true,
        'd' => $d,
        'plan' => $d['plan_id'] ? fetch_one('SELECT * FROM donation_plans WHERE id = ?', [$d['plan_id']]) : null,
        'provider' => payment_provider($d['provider']),
    ]);
}

function page_donate_manage(): void
{
    $plan = find_plan_by_token((string) ($_GET['t'] ?? ''));
    if (!$plan) {
        page_not_found();
        return;
    }
    $self = donate_page_url('manage', ['t' => $plan['token']]);
    if (is_post()) {
        csrf_verify();
        if (($_POST['action'] ?? '') === 'cancel') {
            try {
                cancel_plan($plan);
                flash('success', __('Votre don régulier est arrêté. Merci pour votre soutien !'));
                notify_admin('Don régulier arrêté par le donateur — ' . $plan['reference'], "Donateur : {$plan['donor_name']} <{$plan['email']}>\n" . absolute_url('admin/?p=donations&view=plans'));
            } catch (Throwable $ex) {
                error_log('Arrêt du don régulier ' . $plan['reference'] . ' : ' . $ex->getMessage());
                flash('error', __('L\'arrêt n\'a pas pu être enregistré. Contactez-nous et nous le ferons pour vous.'));
            }
        }
        redirect($self);
    }
    header('X-Robots-Tag: noindex');
    render_page('donate_manage', [
        'nav' => 'give', 'pageTitle' => __('Mon don régulier'), 'noindex' => true,
        'plan' => $plan,
        'provider' => payment_provider($plan['provider']),
        'payments' => fetch_all("SELECT * FROM donations WHERE plan_id = ? AND status = 'paid' ORDER BY paid_at DESC LIMIT 24", [$plan['id']]),
    ]);
}

/** Paiement d'une échéance (dons réguliers par Mobile Money). */
function page_donate_installment(): void
{
    $plan = find_plan_by_token((string) ($_GET['t'] ?? ''));
    if (!$plan) {
        page_not_found();
        return;
    }
    $self = donate_page_url('pay', ['t' => $plan['token']]);
    $providers = array_filter(available_providers(), fn (PaymentProvider $p) => !$p->supportsAutoRecurring() && in_array($plan['currency'], $p->currencies(), true));
    if ($plan['status'] === 'active' && is_post()) {
        handle_donate_post($plan, $self);
    }
    header('X-Robots-Tag: noindex');
    render_page('donate_pay', [
        'nav' => 'give', 'pageTitle' => __('Régler mon don régulier'), 'noindex' => true,
        'plan' => $plan, 'providers' => $providers,
    ]);
}
