<?php
declare(strict_types=1);

/** Contrat commun à tous les prestataires de paiement. */
interface PaymentProvider
{
    /** Identifiant technique (stripe, mvola, orange_money, test). */
    public function id(): string;

    /** Nom affiché au donateur. */
    public function label(): string;

    /** « card » (carte bancaire) ou « mobile » (Mobile Money). */
    public function kind(): string;

    /** Les clés nécessaires sont-elles présentes dans app/config.php ? */
    public function isConfigured(): bool;

    /** Activé dans l'admin (Paramètres du site → Paiement en ligne) ? */
    public function isEnabled(): bool;

    /** Clés de test / environnement « sandbox » ? */
    public function isTest(): bool;

    /** Vrai si le prestataire prélève lui-même les échéances d'un don régulier (abonnement). */
    public function supportsAutoRecurring(): bool;

    /** Devises acceptées (codes ISO). */
    public function currencies(): array;

    /**
     * Lance le paiement. Renvoie ['redirect' => url] (page de paiement du prestataire)
     * ou ['wait' => true] (confirmation sur le téléphone du donateur). Lève une RuntimeException en cas d'erreur.
     */
    public function start(array $donation, ?array $plan): array;

    /** Interroge le prestataire sur un paiement en attente et met à jour son statut. */
    public function refresh(array $donation): void;

    /** Traite une notification (webhook / callback) envoyée par le prestataire. */
    public function webhook(string $rawBody, ?string $reference): void;

    /** Arrête l'abonnement chez le prestataire (dons réguliers à prélèvement automatique). */
    public function cancelPlan(array $plan): void;
}

/** Outils communs : configuration, appels HTTP, adresses de retour. */
abstract class BasePaymentProvider implements PaymentProvider
{
    protected function conf(string $key = '', mixed $default = null): mixed
    {
        $all = (array) (config('payments', [])[$this->id()] ?? []);
        return $key === '' ? $all : ($all[$key] ?? $default);
    }

    public function isEnabled(): bool
    {
        return setting('pay_' . $this->id() . '_enabled', '1') === '1';
    }

    public function supportsAutoRecurring(): bool
    {
        return false;
    }

    public function refresh(array $donation): void
    {
    }

    public function webhook(string $rawBody, ?string $reference): void
    {
    }

    public function cancelPlan(array $plan): void
    {
    }

    /** Adresse absolue d'une route technique (notifications des prestataires). */
    protected function endpoint(string $path): string
    {
        return rtrim((string) config('site_url', ''), '/') . url('paiement/' . ltrim($path, '/'));
    }

    /**
     * Requête HTTP (cURL). $body : tableau (form-urlencoded), chaîne JSON ou null.
     * Renvoie [code HTTP, réponse décodée (tableau) ou texte brut].
     */
    protected function http(string $method, string $url, array $headers = [], array|string|null $body = null): array
    {
        $h = [];
        foreach ($headers as $k => $v) {
            $h[] = $k . ': ' . $v;
        }
        if (!function_exists('curl_init')) {
            return $this->httpStream($method, $url, $h, $body);
        }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => $h,
        ]);
        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_array($body) ? http_build_query($body) : $body);
        }
        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new RuntimeException($this->label() . ' : connexion impossible (' . $err . ')');
        }
        $code = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        curl_close($ch);
        $json = json_decode((string) $raw, true);
        return [$code, is_array($json) ? $json : (string) $raw];
    }

    /** Repli sans l'extension cURL (flux HTTP de PHP, nécessite openssl pour le HTTPS). */
    private function httpStream(string $method, string $url, array $h, array|string|null $body): array
    {
        if (is_array($body) && !preg_grep('/^Content-Type:/i', $h)) {
            $h[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $ctx = stream_context_create(['http' => [
            'method' => $method, 'header' => implode("\r\n", $h), 'ignore_errors' => true, 'timeout' => 30,
            'content' => $body === null ? '' : (is_array($body) ? http_build_query($body) : $body),
        ]]);
        $raw = @file_get_contents($url, false, $ctx);
        if ($raw === false) {
            throw new RuntimeException($this->label() . ' : connexion impossible (' . (error_get_last()['message'] ?? 'réseau') . ')');
        }
        $code = 0;
        foreach ($http_response_header ?? [] as $line) {
            if (preg_match('#^HTTP/\S+ (\d+)#', $line, $m)) {
                $code = (int) $m[1];
            }
        }
        $json = json_decode($raw, true);
        return [$code, is_array($json) ? $json : $raw];
    }

    /** Message d'erreur lisible extrait d'une réponse d'API (pour le journal, jamais affiché tel quel au donateur). */
    protected function apiError(int $code, mixed $resp): RuntimeException
    {
        $detail = is_array($resp) ? json_encode($resp, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : (string) $resp;
        error_log($this->label() . " : erreur API HTTP $code — " . mb_substr($detail, 0, 800));
        return new RuntimeException($this->label() . " : le paiement n'a pas pu être lancé (code $code).");
    }
}
