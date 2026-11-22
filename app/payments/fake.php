<?php
declare(strict_types=1);

/**
 * Prestataire « test » : simule une page de paiement sans aucun appel externe.
 * Activé uniquement si app/config.php contient payments.test = true (développement local).
 * Il se comporte comme le Mobile Money : les dons réguliers fonctionnent par rappel e-mail.
 */
final class FakeProvider extends BasePaymentProvider
{
    public function id(): string
    {
        return 'test';
    }

    public function label(): string
    {
        return __('Paiement de test');
    }

    public function kind(): string
    {
        return 'mobile';
    }

    public function isConfigured(): bool
    {
        return (config('payments', [])['test'] ?? false) === true;
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function isTest(): bool
    {
        return true;
    }

    public function currencies(): array
    {
        return ['MGA', 'EUR'];
    }

    public function start(array $d, ?array $plan): array
    {
        return ['redirect' => $this->endpoint('test/' . $d['reference'])];
    }
}
