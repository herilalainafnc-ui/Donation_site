<section class="page-hero<?= !empty($image) ? ' has-image' : '' ?>" data-header="dark"<?= !empty($image) ? ' style="--hero-img: url(\'' . e(media($image)) . '\')"' : '' ?>>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <img class="hero-seal" src="<?= e(site_logo()) ?>" alt="" aria-hidden="true">
    <div class="container page-hero-inner" data-hero-text>
        <?php if (!empty($kicker)): ?><p class="eyebrow"><span class="eyebrow-line"></span><?= e($kicker) ?></p><?php endif; ?>
        <h1 class="display-md" data-split><?= inline_format($title) ?></h1>
        <?php if (!empty($lead)): ?><p class="lead" data-fade><?= e($lead) ?></p><?php endif; ?>
    </div>
</section>
