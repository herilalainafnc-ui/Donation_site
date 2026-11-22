<?php
/** Résultat d'un paiement en ligne. Variables : $d (paiement), $plan (engagement régulier ou null), $provider. */
$amount = money($d['amount'], currency_symbol($d['currency']));
$project = $d['project_id'] ? fetch_one('SELECT slug, title, title_en FROM projects WHERE id = ?', [$d['project_id']]) : null;
$isMobile = $provider && $provider->kind() === 'mobile';
?>
<?= partial('page_hero', ['kicker' => __('Don en ligne'), 'title' => $pageTitle]) ?>

<section class="section donate-status-section">
    <div class="container narrow">
        <div class="card-form donate-status status-<?= e($d['status']) ?>" <?= $d['status'] === 'pending' ? 'data-poll="' . e(url('paiement/statut/' . $d['reference'])) . '"' : '' ?>>
            <?php if ($d['status'] === 'paid'): ?>
                <span class="status-icon ok"><?= icon('check', 34) ?></span>
                <h2 class="status-title"><?= e(__('Merci, votre don est bien reçu !')) ?></h2>
                <p class="lead-dark"><?= e(__('Votre don de :amount a été payé avec succès.', ['amount' => $amount])) ?></p>
            <?php elseif ($d['status'] === 'pending'): ?>
                <span class="status-icon wait" aria-hidden="true"><span class="spinner"></span></span>
                <h2 class="status-title"><?= e($isMobile ? __('Confirmez le paiement sur votre téléphone') : __('Vérification de votre paiement…')) ?></h2>
                <p class="lead-dark">
                    <?php if ($provider && $provider->id() === 'mvola'): ?>
                        <?= e(__('Une demande de :amount a été envoyée au :phone. Validez-la avec votre code secret MVola.', ['amount' => $amount, 'phone' => $d['phone']])) ?>
                    <?php elseif ($isMobile): ?>
                        <?= e(__('Terminez le paiement de :amount, puis revenez sur cette page.', ['amount' => $amount])) ?>
                    <?php else: ?>
                        <?= e(__('Cela ne prend que quelques secondes.')) ?>
                    <?php endif; ?>
                </p>
                <p class="muted" data-poll-note><?= e(__('Cette page se met à jour automatiquement.')) ?></p>
            <?php else: ?>
                <span class="status-icon ko"><?= icon('close', 30) ?></span>
                <h2 class="status-title"><?= e(__('Le paiement n\'a pas abouti')) ?></h2>
                <p class="lead-dark"><?= e(__('Aucun montant n\'a été débité. Vous pouvez réessayer ou choisir un autre moyen de paiement.')) ?></p>
            <?php endif; ?>

            <dl class="status-facts">
                <div><dt><?= e(__('Référence')) ?></dt><dd><?= e($d['reference']) ?></dd></div>
                <div><dt><?= e(__('Montant')) ?></dt><dd><?= e($amount) ?></dd></div>
                <div><dt><?= e(__('Fréquence')) ?></dt><dd><?= e(frequency_label($d['frequency'])) ?></dd></div>
                <div><dt><?= e(__('Moyen de paiement')) ?></dt><dd><?= e($provider ? $provider->label() : $d['provider']) ?></dd></div>
                <?php if ($project): ?><div><dt><?= e(__('Projet soutenu')) ?></dt><dd><?= e(loc($project, 'title')) ?></dd></div><?php endif; ?>
            </dl>

            <?php if ($d['status'] === 'paid'): ?>
                <p><?= e(__('Un e-mail de confirmation a été envoyé à :email.', ['email' => $d['email']])) ?></p>
                <?php if ($plan): ?>
                    <p class="donate-note"><?= icon('calendar', 16) ?>
                        <?= e($provider && $provider->supportsAutoRecurring()
                            ? __('Votre don régulier est actif : il sera prélevé automatiquement.')
                            : __('Votre don régulier est actif : nous vous enverrons un rappel par e-mail à chaque échéance (prochaine : :date).', ['date' => format_date($plan['next_due_date'])])) ?>
                    </p>
                <?php endif; ?>
            <?php endif; ?>

            <div class="status-actions">
                <?php if (in_array($d['status'], ['failed', 'expired', 'cancelled'], true)): ?>
                    <a class="btn btn-gold" href="<?= e(route_url('donate', ['query' => $project ? ['project' => $project['slug']] : []])) ?>"><span><?= e(__('Réessayer')) ?></span></a>
                <?php endif; ?>
                <?php if ($plan): ?><a class="btn btn-outline" href="<?= e(donate_page_url('manage', ['t' => $plan['token']])) ?>"><span><?= e(__('Gérer mon don régulier')) ?></span></a><?php endif; ?>
                <?php if ($project): ?><a class="btn btn-outline" href="<?= e(route_url('projects', ['slug' => $project['slug']])) ?>"><span><?= e(__('Voir le projet')) ?></span></a><?php endif; ?>
                <a class="btn btn-outline" href="<?= e(route_url('home')) ?>"><span><?= e(__('Retour à l\'accueil')) ?></span></a>
            </div>
        </div>
    </div>
</section>
