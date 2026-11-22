<section class="cta-reveal" data-cta data-header="dark">
    <div class="cta-circle">
        <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
        <img class="cta-seal" src="<?= e(site_logo()) ?>" alt="" aria-hidden="true">
        <div class="container cta-content">
            <p class="eyebrow"><span class="eyebrow-line"></span><?= e(__('Passez à l\'action')) ?></p>
            <h2 class="display-md" data-split><?= inline_format(setting_t('cta_title', __('Prêt à faire **la différence** ?'))) ?></h2>
            <p class="lead" data-fade><?= e(setting_t('cta_text')) ?></p>
            <div class="hero-actions center-actions" data-fade>
                <a class="btn btn-gold btn-lg magnetic" href="<?= e(give_url()) ?>"><span><?= icon('heart', 20) ?> <?= e(__('Faire un don')) ?></span></a>
                <a class="btn btn-line-light btn-lg magnetic" href="<?= route_url('projects') ?>"><span><?= e(__('Voir les projets')) ?></span></a>
            </div>
        </div>
    </div>
</section>
