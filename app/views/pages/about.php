<?= partial('page_hero', ['kicker' => __('À propos de nous'), 'title' => setting_t('site_name'), 'lead' => setting_t('tagline'), 'image' => setting('about_image')]) ?>

<section class="section">
    <div class="container split">
        <div class="reveal">
            <p class="kicker"><?= e(__('Présentation')) ?></p>
            <h2 class="section-title"><?= e(__('Une université au service de l\'avenir')) ?></h2>
            <div class="prose"><?= rich_text(setting_t('about_intro')) ?></div>
        </div>
        <div class="split-media reveal">
            <?php if (setting('about_image')): ?>
                <img src="<?= e(media(setting('about_image'))) ?>" alt="<?= e(__('Campus de l\'Université Adventiste Zurcher')) ?>" loading="lazy">
            <?php else: ?>
                <div class="media-placeholder tall"><?= icon('building', 72) ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section section-alt">
    <div class="container mv-grid">
        <div class="mv-card reveal"><?= icon('target', 36) ?><h3><?= e(__('Notre mission')) ?></h3><div class="prose"><?= rich_text(setting_t('about_mission')) ?></div></div>
        <div class="mv-card reveal"><?= icon('globe', 36) ?><h3><?= e(__('Notre vision')) ?></h3><div class="prose"><?= rich_text(setting_t('about_vision')) ?></div></div>
        <div class="mv-card reveal"><?= icon('heart', 36) ?><h3><?= e(__('Nos valeurs')) ?></h3><div class="prose"><?= rich_text(setting_t('about_values')) ?></div></div>
    </div>
</section>

<?php if (setting_t('about_history')): ?>
<section class="section">
    <div class="container narrow reveal">
        <p class="kicker"><?= e(__('Notre histoire')) ?></p>
        <h2 class="section-title"><?= e(__('D\'hier à aujourd\'hui')) ?></h2>
        <div class="prose prose-lg"><?= rich_text(setting_t('about_history')) ?></div>
    </div>
</section>
<?php endif; ?>

<section class="stats-band">
    <div class="container stats-grid">
        <div class="stat"><strong data-count="<?= $stats['completed'] ?>"><?= $stats['completed'] ?></strong><span><?= e(__('Projets achevés')) ?></span></div>
        <div class="stat"><strong data-count="<?= $stats['ongoing'] ?>"><?= $stats['ongoing'] ?></strong><span><?= e(__('Projets en cours')) ?></span></div>
        <div class="stat"><strong data-count="<?= $stats['upcoming'] ?>"><?= $stats['upcoming'] ?></strong><span><?= e(__('Projets à venir')) ?></span></div>
        <div class="stat"><strong><?= e($stats['raised_label']) ?></strong><span><?= e(__('Collectés grâce à vous')) ?></span></div>
    </div>
</section>

<?= partial('cta') ?>
