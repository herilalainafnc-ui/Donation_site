<?php
declare(strict_types=1);

/**
 * Stripe Checkout : carte bancaire (et Apple Pay / Google Pay selon le compte).
 * - Don ponctuel : session en mode « payment ».
 * - Don régulier : session en mode « subscription » (prélèvement automatique mensuel, trimestriel ou annuel).
 * Configuration (app/config.php → payments.stripe) : secret_key (sk_test_… / sk_live_…), webhook_secret (whsec_…).
 * Webhook à déclarer dans le tableau de bord Stripe : https://<site>/paiement/stripe/webhook
 * avec les événements checkout.session.completed, checkout.session.async_payment_succeeded,
 * checkout.session.async_payment_failed, invoice.paid, customer.subscription.deleted.
 */
final class StripeProvider extends BasePaymentProvider
{
    private const API = 'https://api.stripe.com/v1/';

    public function id(): string
    {
        return 'stripe';
    }

    public function label(): string
    {
        return __('Carte bancaire');
    }

    public function kind(): string
    {
        return 'card';
    }

    public function isConfigured(): bool
    {
        return str_starts_with((string) $this->conf('secret_key', ''), 'sk_') || str_starts_with((string) $this->conf('secret_key', ''), 'rk_');
    }

    public function isTest(): bool
    {
        return str_contains((string) $this->conf('secret_key', ''), '_test_');
    }

    public function supportsAutoRecurring(): bool
    {
        return true;
    }

    public function currencies(): array
    {
        return card_currencies();
    }

    private function api(string $method, string $path, ?array $params = null): array
    {
        [$code, $resp] = $this->http($method, self::API . $path, [
            'Authorization' => 'Bearer ' . $this->conf('secret_key'),
            'Stripe-Version' => '2024-06-20',
        ], $params);
        if ($code >= 300 || !is_array($resp)) {
            throw $this->apiError($code, $resp);
        }
        return $resp;
    }

    public function start(array $d, ?array $plan): array
    {
        $project = $d['project_id'] ? fetch_one('SELECT title, title_en FROM projects WHERE id = ?', [$d['project_id']]) : null;
        $name = __('Don à l\'Université Adventiste Zurcher') . ($project ? ' — ' . loc($project, 'title') : '');
        $price = [
            'currency' => strtolower($d['currency']),
            'unit_amount' => to_minor((float) $d['amount'], $d['currency']),
            'product_data' => ['name' => mb_substr($name, 0, 250)],
        ];
        $params = [
            'client_reference_id' => $d['reference'],
            'customer_email' => $d['email'],
            'locale' => lang() === 'fr' ? 'fr' : 'en',
            'success_url' => donate_page_url('thanks', ['ref' => $d['reference']]),
            'cancel_url' => donate_page_url('', ['annule' => $d['reference']]),
            'metadata' => ['donation_ref' => $d['reference'], 'plan_ref' => $plan['reference'] ?? ''],
        ];
        if ($plan) {
            [$unit, $n] = PAY_FREQUENCIES[$plan['frequency']];
            $price['recurring'] = ['interval' => $unit, 'interval_count' => $n];
            $params['mode'] = 'subscription';
            $params['subscription_data'] = ['metadata' => ['plan_ref' => $plan['reference'], 'donation_ref' => $d['reference']], 'description' => mb_substr($name, 0, 250)];
        } else {
            $params['mode'] = 'payment';
            $params['submit_type'] = 'donate';
            $params['payment_intent_data'] = ['description' => mb_substr($name . ' — ' . $d['reference'], 0, 250), 'metadata' => ['donation_ref' => $d['reference']]];
        }
        $params['line_items'] = [['price_data' => $price, 'quantity' => 1]];
        $session = $this->api('POST', 'checkout/sessions', $params);
        donation_data($d, ['session_id' => $session['id']]);
        q('UPDATE donations SET provider_ref = ? WHERE id = ?', [$session['id'], $d['id']]);
        return ['redirect' => $session['url']];
    }

    /** Au retour sur la page de remerciement : vérifie la session si le webhook n'est pas encore arrivé. */
    public function refresh(array $d): void
    {
        $sid = donation_data($d)['session_id'] ?? null;
        if ($d['status'] !== 'pending' || !$sid) {
            return;
        }
        $this->applySession($this->api('GET', 'checkout/sessions/' . rawurlencode($sid)));
    }

    private function applySession(array $s): void
    {
        $d = find_donation((string) ($s['client_reference_id'] ?? $s['metadata']['donation_ref'] ?? ''));
        if (!$d) {
            return;
        }
        if (!empty($s['subscription']) && $d['plan_id']) {
            q('UPDATE donation_plans SET provider_subscription = ?, provider_customer = ?, updated_at = ? WHERE id = ?',
                [is_array($s['subscription']) ? $s['subscription']['id'] : $s['subscription'], is_array($s['customer'] ?? null) ? $s['customer']['id'] : ($s['customer'] ?? null), now(), $d['plan_id']]);
        }
        if (($s['payment_status'] ?? '') === 'paid' || ($s['payment_status'] ?? '') === 'no_payment_required') {
            $pi = $s['payment_intent'] ?? null;
            mark_donation_paid($d, is_string($pi) ? $pi : ($s['subscription'] ?? $s['id']));
        } elseif (($s['status'] ?? '') === 'expired') {
            mark_donation_failed($d, 'expired');
        }
    }

    /** Vérifie la signature Stripe (en-tête Stripe-Signature, HMAC SHA-256, tolérance 5 minutes). */
    public function verifySignature(string $raw, string $header, string $secret, int $tolerance = 300): bool
    {
        $t = null;
        $sigs = [];
        foreach (explode(',', $header) as $part) {
            [$k, $v] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($k === 't') {
                $t = (int) $v;
            } elseif ($k === 'v1') {
                $sigs[] = $v;
            }
        }
        if (!$t || !$sigs || abs(time() - $t) > $tolerance) {
            return false;
        }
        $expected = hash_hmac('sha256', $t . '.' . $raw, $secret);
        foreach ($sigs as $s) {
            if (hash_equals($expected, $s)) {
                return true;
            }
        }
        return false;
    }

    public function webhook(string $raw, ?string $reference): void
    {
        $secret = (string) $this->conf('webhook_secret', '');
        if ($secret === '' || !$this->verifySignature($raw, (string) ($_SERVER['HTTP_STRIPE_SIGNATURE'] ?? ''), $secret)) {
            http_response_code(400);
            exit('Signature invalide');
        }
        $event = json_decode($raw, true);
        if (!is_array($event) || empty($event['id']) || !record_payment_event('stripe', $event['id'], (string) ($event['type'] ?? ''), $raw)) {
            return; // déjà traité
        }
        $o = $event['data']['object'] ?? [];
        switch ($event['type']) {
            case 'checkout.session.completed':
            case 'checkout.session.async_payment_succeeded':
                $this->applySession($o);
                break;
            case 'checkout.session.async_payment_failed':
            case 'checkout.session.expired':
                if ($d = find_donation((string) ($o['client_reference_id'] ?? ''))) {
                    mark_donation_failed($d, $event['type'] === 'checkout.session.expired' ? 'expired' : 'failed');
                }
                break;
            case 'invoice.paid':
                $this->invoicePaid($o);
                break;
            case 'customer.subscription.deleted':
                if ($plan = fetch_one('SELECT * FROM donation_plans WHERE provider_subscription = ?', [(string) ($o['id'] ?? '')])) {
                    cancel_plan($plan, false);
                }
                break;
        }
    }

    /** Échéance d'un abonnement prélevée : enregistre un nouveau paiement lié à l'engagement. */
    private function invoicePaid(array $inv): void
    {
        $sub = is_array($inv['subscription'] ?? null) ? $inv['subscription']['id'] : (string) ($inv['subscription'] ?? '');
        if ($sub === '' || ($inv['billing_reason'] ?? '') === 'subscription_create') {
            return; // le 1er paiement est traité par checkout.session.completed
        }
        $plan = fetch_one('SELECT * FROM donation_plans WHERE provider_subscription = ?', [$sub]);
        if (!$plan || fetch_value('SELECT COUNT(*) FROM donations WHERE provider = ? AND provider_ref = ?', ['stripe', $inv['id']])) {
            return;
        }
        $iso = strtoupper((string) ($inv['currency'] ?? $plan['currency']));
        $prev = lang();
        set_lang($plan['lang'] ?: DEFAULT_LANG);
        [$d] = create_donation([
            'donor_name' => $plan['donor_name'], 'email' => $plan['email'], 'phone' => $plan['phone'], 'country' => $plan['country'],
            'amount' => from_minor((int) ($inv['amount_paid'] ?? 0), $iso), 'currency' => $iso, 'provider' => 'stripe',
            'frequency' => $plan['frequency'], 'project_id' => $plan['project_id'], 'message' => null,
        ], $plan);
        set_lang($prev);
        mark_donation_paid($d, (string) $inv['id']);
    }

    public function cancelPlan(array $plan): void
    {
        $this->api('DELETE', 'subscriptions/' . rawurlencode((string) $plan['provider_subscription']));
    }
}
