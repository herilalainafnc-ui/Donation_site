<?php
declare(strict_types=1);

/**
 * MVola (Telma) — API « Merchant Pay » v1.0.0.
 * Le donateur saisit son numéro MVola ; il reçoit une demande de confirmation sur son téléphone
 * (code secret). Le site interroge ensuite MVola pour connaître le résultat.
 *
 * Configuration (app/config.php → payments.mvola) :
 *   consumer_key, consumer_secret : identifiants de l'application créée sur le portail développeur MVola ;
 *   merchant_number : numéro MVola marchand de l'UAZ (ex. 0343500004 en sandbox) ;
 *   partner_name : nom du partenaire déclaré chez MVola ; sandbox : true tant que le compte n'est pas en production.
 * Numéros de test sandbox (documentation MVola) : 0343500003 et 0343500004.
 * À vérifier au moment de l'ouverture du compte : URL de base et en-têtes exacts demandés par Telma.
 */
final class MvolaProvider extends BasePaymentProvider
{
    public function id(): string
    {
        return 'mvola';
    }

    public function label(): string
    {
        return 'MVola';
    }

    public function kind(): string
    {
        return 'mobile';
    }

    public function isConfigured(): bool
    {
        return $this->conf('consumer_key') && $this->conf('consumer_secret') && $this->conf('merchant_number');
    }

    public function isTest(): bool
    {
        return (bool) $this->conf('sandbox', true);
    }

    public function currencies(): array
    {
        return ['MGA'];
    }

    private function base(): string
    {
        return rtrim((string) $this->conf('base_url', $this->isTest() ? 'https://devapi.mvola.mg' : 'https://api.mvola.mg'), '/');
    }

    private function token(): string
    {
        [$code, $resp] = $this->http('POST', $this->base() . '/token', [
            'Authorization' => 'Basic ' . base64_encode($this->conf('consumer_key') . ':' . $this->conf('consumer_secret')),
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Cache-Control' => 'no-cache',
        ], ['grant_type' => 'client_credentials', 'scope' => 'EXT_INT_MVOLA_SCOPE']);
        if ($code >= 300 || empty($resp['access_token'])) {
            throw $this->apiError($code, $resp);
        }
        return $resp['access_token'];
    }

    private function headers(string $token, ?string $callback = null): array
    {
        $h = [
            'Authorization' => 'Bearer ' . $token,
            'Version' => '1.0',
            'X-CorrelationID' => bin2hex(random_bytes(16)),
            'UserLanguage' => lang() === 'fr' ? 'FR' : 'MG',
            'UserAccountIdentifier' => 'msisdn;' . $this->conf('merchant_number'),
            'partnerName' => (string) $this->conf('partner_name', 'UAZ'),
            'Content-Type' => 'application/json',
            'Cache-Control' => 'no-cache',
        ];
        if ($callback) {
            $h['X-Callback-URL'] = $callback;
        }
        return $h;
    }

    public function start(array $d, ?array $plan): array
    {
        $msisdn = mobile_number_local((string) $d['phone']);
        if (!preg_match('/^03[48]\d{7}$/', $msisdn)) {
            throw new RuntimeException(__('Indiquez un numéro MVola valide (034 ou 038).'));
        }
        // MVola n'accepte qu'un libellé court, sans caractères spéciaux.
        $desc = mb_substr(trim(preg_replace('/[^A-Za-z0-9 ]+/', ' ', iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', 'Don UAZ ' . $d['reference']) ?: 'Don UAZ')), 0, 40);
        $body = [
            'amount' => (string) (int) round((float) $d['amount']),
            'currency' => 'Ar',
            'descriptionText' => $desc,
            'requestingOrganisationTransactionReference' => $d['reference'],
            'requestDate' => gmdate('Y-m-d\TH:i:s.v\Z'),
            'originalTransactionReference' => $d['reference'],
            'debitParty' => [['key' => 'msisdn', 'value' => $msisdn]],
            'creditParty' => [['key' => 'msisdn', 'value' => (string) $this->conf('merchant_number')]],
            'metadata' => [
                ['key' => 'partnerName', 'value' => (string) $this->conf('partner_name', 'UAZ')],
                ['key' => 'fc', 'value' => 'USD'],
                ['key' => 'amountFc', 'value' => '1'],
            ],
        ];
        $token = $this->token();
        [$code, $resp] = $this->http('POST', $this->base() . '/mvola/mm/transactions/type/merchantpay/1.0.0/',
            $this->headers($token, $this->endpoint('mvola/callback/' . $d['reference'])), json_encode($body));
        if ($code >= 300 || empty($resp['serverCorrelationId'])) {
            throw $this->apiError($code, $resp);
        }
        q('UPDATE donations SET provider_ref = ? WHERE id = ?', [$resp['serverCorrelationId'], $d['id']]);
        donation_data($d, ['mvola_status' => $resp['status'] ?? 'pending']);
        return ['wait' => true];
    }

    /** Statut de la transaction auprès de MVola (la page d'attente l'appelle toutes les quelques secondes). */
    public function refresh(array $d): void
    {
        if ($d['status'] !== 'pending' || !$d['provider_ref']) {
            return;
        }
        [$code, $resp] = $this->http('GET', $this->base() . '/mvola/mm/transactions/type/merchantpay/1.0.0/status/' . rawurlencode($d['provider_ref']),
            $this->headers($this->token()));
        if ($code >= 300 || !is_array($resp)) {
            return;
        }
        $status = strtolower((string) ($resp['status'] ?? ''));
        if ($status === 'completed') {
            mark_donation_paid($d, null, ['mvola_transaction' => $resp['objectReference'] ?? null]);
        } elseif ($status === 'failed') {
            mark_donation_failed($d);
        }
    }

    /** Rappel de MVola : on ne fait pas confiance au contenu, on revérifie le statut auprès de MVola. */
    public function webhook(string $raw, ?string $reference): void
    {
        $d = $reference ? find_donation($reference) : null;
        if (!$d || $d['provider'] !== 'mvola') {
            http_response_code(404);
            exit;
        }
        record_payment_event('mvola', $reference . ':' . md5($raw), 'callback', $raw);
        $this->refresh($d);
    }
}

/** Numéro malgache au format local 03XXXXXXXX (accepte +261 / 261 / espaces). */
function mobile_number_local(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    if (str_starts_with($digits, '261')) {
        $digits = '0' . substr($digits, 3);
    } elseif (strlen($digits) === 9 && $digits[0] === '3') {
        $digits = '0' . $digits;
    }
    return $digits;
}
