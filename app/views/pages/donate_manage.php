<?php
/** Gestion d'un don régulier par le donateur. Variables : $plan, $provider, $payments. */
$amount = money($plan['amount'], currency_symbol($plan['currency']));
$auto = $provider && $provider->supportsAutoRecurring();
$project = $plan['project_id'] ? fetch_one('SELECT slug, title, title_en FROM projects WHERE id = ?', [$plan['project_id']]) : null;
?>
<?= partial('page_hero', ['kicker' => __('Don en ligne'), 'title' => $pageTitle]) ?>

<section class="section donate-status-section">
    <div class="container narrow">
        <div class="card-form donate-status">
            <span class="status-icon <?= $plan['status'] === 'active' ? 'ok' : 'wait' ?>"><?= icon('calendar', 30) ?></span>
            <h2 class="status-title"><?= e(__('Mon don régulier')) ?></h2>
            <p class="lead-dark"><?= e(__(':amount — :f', ['amount' => $amount, 'f' => mb_strtolower(frequency_label($plan['frequency']))])) ?></p>

            <dl class="status-facts">
                <div><dt><?= e(__('Statut')) ?></dt><dd><span class="plan-status plan-<?= e($plan['status']) ?>"><?= e(plan_status_label($plan['status'])) ?></span></dd></div>
                <div><dt><?= e(__('Donateur')) ?></dt><dd><?= e($plan['donor_name']) ?></dd></div>
                <div><dt><?= e(__('Moyen de paiement')) ?></dt><dd><?= e($provider ? $provider->label() : $plan['provider']) ?></dd></div>
                <?php if ($project): ?><div><dt><?= e(__('Projet soutenu')) ?></dt><dd><?= e(loc($project, 'title')) ?></dd></div><?php endif; ?>
                <?php if ($plan['status'] === 'active' && $plan['next_due_date']): ?><div><dt><?= e(__('Prochaine échéance')) ?></dt><dd><?= e(format_date($plan['next_due_date'])) ?></dd></div><?php endif; ?>
                <div><dt><?= e(__('Paiements effectués')) ?></dt><dd><?= (int) $plan['payments_count'] ?></dd></div>
                <div><dt><?= e(__('Référence')) ?></dt><dd><?= e($plan['reference']) ?></dd></div>
            </dl>

            <?php if ($payments): ?>
                <h2 class="small-title"><?= e(__('Derniers paiements')) ?></h2>
                <ul class="payments-list">
                    <?php foreach ($payments as $pay): ?>
                        <li><span><?= e(format_date(substr((string) $pay['paid_at'], 0, 10))) ?></span><strong><?= e(money($pay['amount'], currency_symbol($pay['currency']))) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($plan['status'] === 'active'): ?>
                <p class="donate-note"><?= icon('check', 16) ?> <?= e($auto ? __('Les échéances sont prélevées automatiquement.') : __('Nous vous envoyons un e-mail à chaque échéance avec un lien pour payer.')) ?></p>
                <div class="status-actions">
                    <?php if (!$auto): ?><a class="btn btn-gold" href="<?= e(donate_page_url('pay', ['t' => $plan['token']])) ?>"><span><?= e(__('Payer l\'échéance maintenant')) ?></span></a><?php endif; ?>
                    <form method="post" action="<?= e(donate_page_url('manage', ['t' => $plan['token']])) ?>" onsubmit="return confirm(<?= e(json_encode(__('Arrêter votre don régulier ? Aucun nouveau paiement ne sera demandé.'))) ?>)">
                        <?= csrf_field() ?>
                        <button class="btn btn-outline" type="submit" name="action" value="cancel"><span><?= e(__('Arrêter mon don régulier')) ?></span></button>
                    </form>
                </div>
            <?php elseif ($plan['status'] === 'cancelled'): ?>
                <p><?= e(__('Ce don régulier est arrêté. Merci pour votre générosité !')) ?></p>
                <div class="status-actions"><a class="btn btn-gold" href="<?= e(route_url('donate')) ?>"><span><?= e(__('Faire un nouveau don')) ?></span></a></div>
            <?php endif; ?>
            <p class="muted small"><?= e(__('Une question ? Écrivez-nous : nous vous répondons rapidement.')) ?> <a href="<?= route_url('contact') ?>"><?= e(__('Contact')) ?></a></p>
        </div>
    </div>
</section>
