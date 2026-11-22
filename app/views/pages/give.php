<?= partial('page_hero', ['kicker' => __('Comment donner'), 'title' => __('Votre générosité **change des vies**'), 'lead' => setting_t('give_intro')]) ?>

<section class="section">
    <div class="container">
        <?php if (online_payments_enabled()): ?>
            <div class="online-give-cta reveal">
                <div><h2><?= e(__('Donner en ligne')) ?></h2><p><?= e(__('Carte bancaire ou Mobile Money, une seule fois ou chaque mois : en quelques minutes.')) ?></p></div>
                <a class="btn btn-gold btn-lg" href="<?= e(route_url('donate', ['query' => $selected ? ['project' => $selected] : []])) ?>"><span><?= icon('heart', 20) ?> <?= e(__('Faire un don en ligne')) ?></span></a>
            </div>
        <?php elseif (setting('online_donation_url')): ?>
            <div class="online-give reveal">
                <div><?= icon('globe', 32) ?><div><h2><?= e(__('Don en ligne sécurisé')) ?></h2><p><?= e(__('Donnez en quelques clics par carte bancaire, où que vous soyez.')) ?></p></div></div>
                <a class="btn btn-gold btn-lg" href="<?= e(setting('online_donation_url')) ?>" target="_blank" rel="noopener"><span><?= e(setting_t('online_donation_label', __('Donner en ligne'))) ?></span></a>
            </div>
        <?php endif; ?>

        <p class="section-label reveal"><b>01</b> <?= e(__('Choisissez votre moyen de don')) ?></p>
        <div class="methods-grid">
            <?php foreach ($methods as $m): $mUrl = route_url('give', ['slug' => $m['id']]); ?>
                <article class="method-card reveal is-link">
                    <span class="method-icon<?= $m['logo'] ? ' has-logo' : '' ?>"><?= method_visual($m) ?></span>
                    <h3><a href="<?= $mUrl ?>" class="stretched"><?= e(loc($m, 'title')) ?></a></h3>
                    <?php if (loc($m, 'description') !== ''): ?><p><?= e(loc($m, 'description')) ?></p><?php endif; ?>
                    <span class="method-more"><?= e(__('Donner avec ce moyen')) ?> <?= icon('arrow', 18) ?></span>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="section section-alt" id="promesse">
    <div class="container split form-split">
        <div class="reveal">
            <p class="kicker"><?= e(__('Formulaire de don')) ?></p>
            <h2 class="section-title"><?= e(__('Faites-nous part de votre don')) ?></h2>
            <p><?= e(setting_t('give_after')) ?></p>
            <ul class="check-list">
                <li><?= icon('check', 20) ?> <?= e(__('Un remerciement personnel')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Un reçu sur demande')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Des nouvelles de l\'avancement du projet')) ?></li>
                <li><?= icon('check', 20) ?> <?= e(__('Votre nom sur le mur des donateurs (si vous le souhaitez)')) ?></li>
            </ul>
        </div>
        <?= partial('pledge_form', ['action' => route_url('give', ['hash' => 'promesse']), 'projectOptions' => $projectOptions, 'selected' => $selected, 'methods' => $methods]) ?>
    </div>
</section>
