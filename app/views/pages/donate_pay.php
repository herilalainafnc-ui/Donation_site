<?php
/** Paiement d'une échéance de don régulier (Mobile Money). Variables : $plan, $providers. */
$amount = money($plan['amount'], currency_symbol($plan['currency']));
?>
<?= partial('page_hero', ['kicker' => __('Don en ligne'), 'title' => $pageTitle]) ?>

<section class="section donate-status-section">
    <div class="container narrow">
        <form class="card-form donate-status" method="post" action="<?= e(donate_page_url('pay', ['t' => $plan['token']])) ?>" novalidate <?= form_validate_attr() ?>>
            <span class="status-icon wait"><?= icon('calendar', 30) ?></span>
            <h2 class="status-title"><?= e(__('Régler mon don régulier')) ?></h2>
            <p class="lead-dark"><?= e(__(':amount — :f', ['amount' => $amount, 'f' => mb_strtolower(frequency_label($plan['frequency']))])) ?></p>

            <?php if ($plan['status'] !== 'active'): ?>
                <p><?= e(__('Ce don régulier n\'est plus actif.')) ?></p>
                <div class="status-actions"><a class="btn btn-gold" href="<?= e(route_url('donate')) ?>"><span><?= e(__('Faire un nouveau don')) ?></span></a></div>
            <?php elseif (!$providers): ?>
                <p><?= e(__('Le paiement en ligne est momentanément indisponible. Merci de réessayer plus tard.')) ?></p>
            <?php else: ?>
                <?= csrf_field() ?>
                <?= form_time_field() ?>
                <div class="hp" aria-hidden="true"><label><?= e(__('Laisser ce champ vide')) ?> <input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
                <?php if ($plan['next_due_date']): ?><p class="muted"><?= e(__('Échéance du :date', ['date' => format_date($plan['next_due_date'])])) ?></p><?php endif; ?>
                <div class="pay-methods">
                    <?php $i = 0; foreach ($providers as $p): ?>
                        <label class="pay-method">
                            <input type="radio" name="provider" value="<?= e($p->id()) ?>" <?= ($plan['provider'] === $p->id() || ($i === 0 && !isset($providers[$plan['provider']]))) ? 'checked' : '' ?> required>
                            <span class="pm-box"><span class="pm-icon"><?= icon('mobile', 24) ?></span><span class="pm-text"><strong><?= e($p->label()) ?></strong><small><?= e(__('Confirmation sur votre téléphone')) ?></small></span></span>
                        </label>
                    <?php $i++; endforeach; ?>
                </div>
                <label><span class="label-text"><?= e(__('Numéro Mobile Money')) ?> *</span><input type="tel" name="phone" required maxlength="25" pattern="\+?[0-9][0-9 ]{5,24}" value="<?= e((string) $plan['phone']) ?>" autocomplete="tel"></label>
                <button class="btn btn-gold btn-lg btn-block" type="submit"><span><?= icon('heart', 20) ?> <?= e(__('Payer :amount', ['amount' => $amount])) ?></span></button>
                <p class="form-note"><a href="<?= e(donate_page_url('manage', ['t' => $plan['token']])) ?>"><?= e(__('Gérer ou arrêter mon don régulier')) ?></a></p>
            <?php endif; ?>
        </form>
    </div>
</section>
