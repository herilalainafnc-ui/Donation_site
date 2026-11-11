<?php
$title = loc($m, 'title');
$details = trim(loc($m, 'details'));
$instructions = trim(loc($m, 'instructions'));
$payLabel = loc($m, 'payment_label') ?: __('Payer maintenant');
?>
<?= partial('page_hero', ['kicker' => __('Moyen de don'), 'title' => $title, 'lead' => loc($m, 'description')]) ?>

<section class="section method-page">
    <div class="container method-layout">
        <div class="method-main">
            <a class="back-link dark" href="<?= route_url('give') ?>">← <?= e(__('Tous les moyens de don')) ?></a>

            <div class="method-identity reveal">
                <span class="method-logo-lg<?= $m['logo'] ? ' has-logo' : '' ?>"><?= method_visual($m, 56) ?></span>
                <div>
                    <p class="kicker"><?= e(__('Donner par')) ?></p>
                    <h2 class="section-title small"><?= e($title) ?></h2>
                </div>
            </div>

            <?php if (!empty($m['payment_url'])): ?>
                <div class="pay-now reveal">
                    <div>
                        <strong><?= e(__('Paiement en ligne sécurisé')) ?></strong>
                        <p><?= e(__('Vous serez redirigé vers la page de paiement officielle. Revenez ensuite remplir le formulaire pour que nous puissions vous remercier.')) ?></p>
                    </div>
                    <a class="btn btn-gold btn-lg magnetic" href="<?= e($m['payment_url']) ?>" target="_blank" rel="noopener"><span><?= icon('heart', 20) ?> <?= e($payLabel) ?> ↗</span></a>
                </div>
            <?php endif; ?>

            <?php if ($details !== ''): ?>
                <div class="method-block reveal">
                    <h3><?= e(__('Coordonnées')) ?></h3>
                    <dl class="details">
                        <?php foreach (preg_split('/\R/', $details) as $line):
                            if (trim($line) === '') continue;
                            [$k, $v] = str_contains($line, ':') ? array_map('trim', explode(':', $line, 2)) : ['', trim($line)]; ?>
                            <div>
                                <?php if ($k !== ''): ?><dt><?= e($k) ?></dt><?php endif; ?>
                                <dd><span><?= e($v) ?></span>
                                    <button type="button" class="copy-btn" data-copy="<?= e($v) ?>" aria-label="<?= e(__('Copier')) ?> <?= e($k ?: $v) ?>"><?= icon('copy', 16) ?></button>
                                </dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                </div>
            <?php endif; ?>

            <div class="method-block reveal">
                <h3><?= e(__('Comment procéder')) ?></h3>
                <?php if ($instructions !== ''): ?>
                    <div class="prose steps-prose"><?= rich_text($instructions) ?></div>
                <?php else: ?>
                    <ol class="mini-steps">
                        <li><?= e(__('Effectuez votre don avec les coordonnées ci-dessus.')) ?></li>
                        <li><?= e(__('Notez la référence de la transaction.')) ?></li>
                        <li><?= e(__('Remplissez le formulaire pour nous prévenir : nous vous remercierons et affecterons votre don au projet choisi.')) ?></li>
                    </ol>
                <?php endif; ?>
            </div>
        </div>

        <aside class="method-form" id="promesse">
            <div class="form-intro reveal">
                <p class="kicker"><?= e(__('Formulaire de don')) ?></p>
                <h2 class="section-title small"><?= e(__('Faites-nous part de votre don')) ?></h2>
            </div>
            <?= partial('pledge_form', ['action' => route_url('give', ['slug' => $m['id'], 'hash' => 'promesse']), 'projectOptions' => $projectOptions, 'selected' => $selected, 'fixedMethod' => $title]) ?>
        </aside>
    </div>
</section>

<?php if ($others): ?>
<section class="section section-alt">
    <div class="container">
        <h2 class="section-title small"><?= e(__('Autres moyens de don')) ?></h2>
        <div class="method-chips">
            <?php foreach ($others as $o): ?>
                <a class="method-chip reveal" href="<?= route_url('give', ['slug' => $o['id']]) ?>">
                    <span class="method-icon<?= $o['logo'] ? ' has-logo' : '' ?>"><?= method_visual($o, 22) ?></span>
                    <strong><?= e(loc($o, 'title')) ?></strong>
                    <?= icon('arrow', 18) ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
