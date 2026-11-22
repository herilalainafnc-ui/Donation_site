<?php
declare(strict_types=1);

/**
 * Orange Money — API « Web Payment » (Orange Developer).
 * Le donateur est redirigé vers la page de paiement Orange Money, puis revient sur le site.
 * Orange envoie aussi une notification (notif_url) ; le site revérifie toujours le statut auprès d'Orange.
 *
 * Configuration (app/config.php → payments.orange_money) :
 *   authorization : en-tête « Basic … » fourni par le portail Orange Developer pour l'application ;
 *   merchant_key : clé marchand Orange Money ; country : code pays de l'API (« mg » en production, « dev » en test) ;
 *   currency : « MGA » en production (« OUV » en environnement de test Orange) ; sandbox : true en test.
 * À vérifier au moment de l'ouverture du compte : chemin exact de l'API pour Madagascar.
 */
final class OrangeMoneyProvider extends BasePaymentProvider
{
    public function id(): string
    {
        return 'orange_money';
    }

    public function label(): string
    {
        return 'Orange Money';
    }

    public function kind(): string
    {
        return 'mobile';
    }

    public function isConfigured(): bool
    {
        return $this->conf('authorization') && $this->conf('merchant_key');
    }

    public function isTest(): bool
    {
        return (bool) $this->conf('sandbox', true);
    }

    public function currencies(): array
    {
        return ['MGA'];
    }

    private function apiBase(): string
    {
        $country = (string) $this->conf('country', $this->isTest() ? 'dev' : 'mg');
        return rtrim((string) $this->conf('base_url', 'https://api.orange.com/orange-money-webpay/' . $country . '/v1'), '/');
    }

    private function token(): string
    {
        $auth = (string) $this->conf('authorization');
        [$code, $resp] = $this->http('POST', (string) $this->conf('token_url', 'https://api.orange.com/oauth/v3/token'), [
            'Authorization' => str_starts_with($auth, 'Basic ') ? $auth : 'Basic ' . $auth,
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Accept' => 'application/json',
        ], ['grant_type' => 'client_credentials']);
        if ($code >= 300 || empty($resp['access_token'])) {
            throw $this->apiError($code, $resp);
        }
        return $resp['access_token'];
    }

    private function headers(string $token): array
    {
        return ['Authorization' => 'Bearer ' . $token, 'Content-Type' => 'application/json', 'Accept' => 'application/json'];
    }

    public function start(array $d, ?array $plan): array
    {
        $body = [
            'merchant_key' => (string) $this->conf('merchant_key'),
            'currency' => (string) $this->conf('currency', $this->isTest() ? 'OUV' : 'MGA'),
            'order_id' => $d['reference'],
            'amount' => (int) round((float) $d['amount']),
            'return_url' => donate_page_url('thanks', ['ref' => $d['reference']]),
            'cancel_url' => donate_page_url('', ['annule' => $d['reference']]),
            'notif_url' => $this->endpoint('orange/notification/' . $d['reference']),
            'lang' => lang() === 'fr' ? 'fr' : 'en',
            'reference' => mb_substr((string) $this->conf('reference', 'UAZ'), 0, 30),
        ];
        [$code, $resp] = $this->http('POST', $this->apiBase() . '/webpayment', $this->headers($this->token()), json_encode($body));
        if ($code >= 300 || empty($resp['payment_url'])) {
            throw $this->apiError($code, $resp);
        }
        q('UPDATE donations SET provider_ref = ? WHERE id = ?', [$resp['pay_token'] ?? null, $d['id']]);
        donation_data($d, ['notif_token' => $resp['notif_token'] ?? null, 'pay_token' => $resp['pay_token'] ?? null]);
        return ['redirect' => $resp['payment_url']];
    }

    public function refresh(array $d): void
    {
        $data = donation_data($d);
        if ($d['status'] !== 'pending' || empty($data['pay_token'])) {
            return;
        }
        [$code, $resp] = $this->http('POST', $this->apiBase() . '/transactionstatus', $this->headers($this->token()), json_encode([
            'order_id' => $d['reference'], 'amount' => (int) round((float) $d['amount']), 'pay_token' => $data['pay_token'],
        ]));
        if ($code >= 300 || !is_array($resp)) {
            return;
        }
        $status = strtoupper((string) ($resp['status'] ?? ''));
        if ($status === 'SUCCESS') {
            mark_donation_paid($d, null, ['orange_txnid' => $resp['txnid'] ?? null]);
        } elseif (in_array($status, ['FAILED', 'EXPIRED'], true)) {
            mark_donation_failed($d, strtolower($status));
        }
    }

    /** Notification d'Orange : contrôle du notif_token, puis vérification du statut auprès d'Orange. */
    public function webhook(string $raw, ?string $reference): void
    {
        $d = $reference ? find_donation($reference) : null;
        $in = json_decode($raw, true) ?: [];
        $expected = $d ? (donation_data($d)['notif_token'] ?? '') : '';
        if (!$d || $expected === '' || !hash_equals((string) $expected, (string) ($in['notif_token'] ?? ''))) {
            http_response_code(403);
            exit;
        }
        record_payment_event('orange_money', $reference . ':' . ($in['txnid'] ?? md5($raw)), (string) ($in['status'] ?? ''), $raw);
        $this->refresh($d);
    }
}
