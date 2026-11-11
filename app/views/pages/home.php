<?php
$heroImg = setting('hero_image');
$hp = $featured[0] ?? null;
$hpPct = $hp ? percent($hp['raised_amount'], $hp['goal_amount']) : 0;
?>
<!-- Bannière plein écran -->
<section class="hero<?= $heroImg ? ' has-image' : '' ?>" data-header="dark"<?= $heroImg ? ' style="--hero-img: url(\'' . e(media($heroImg)) . '\')"' : '' ?>>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <div class="container hero-inner">
        <div class="hero-text" data-hero-text>
            <p class="eyebrow"><span class="eyebrow-line"></span><?= e(setting_t('hero_kicker')) ?></p>
            <h1 class="display" data-split><?= inline_format(setting_t('hero_title')) ?></h1>
            <p class="lead" data-fade><?= e(setting_t('hero_subtitle')) ?></p>
            <div class="hero-actions" data-fade>
                <a class="btn btn-gold btn-lg magnetic" href="<?= route_url('give') ?>"><span><?= icon('heart', 20) ?> <?= e(__('Faire un don')) ?></span></a>
                <a class="btn btn-line-light btn-lg magnetic" href="<?= route_url('projects') ?>"><span><?= e(__('Découvrir nos projets')) ?> <?= icon('arrow', 18) ?></span></a>
            </div>
        </div>

        <div class="hero-visual">
            <div class="gift-stage" data-mouse-parallax>
                <?= partial('hero_gifts') ?>
            </div>
            <?php if ($hp && (float) $hp['goal_amount'] > 0): ?>
                <a class="hero-project glass" href="<?= route_url('projects', ['slug' => $hp['slug']]) ?>">
                    <span class="badge badge-<?= e($hp['status']) ?>"><?= e(status_label($hp['status'])) ?></span>
                    <strong><?= e(loc($hp, 'title')) ?></strong>
                    <span class="progress"><span style="--pct: <?= $hpPct ?>%"></span></span>
                    <span class="hero-project-meta"><b><?= e(money($hp['raised_amount'])) ?></b><em><?= $hpPct ?> %</em></span>
                </a>
            <?php endif; ?>
        </div>
    </div>
    <a href="#decouvrir" class="scroll-cue" aria-label="<?= e(__('Faire défiler')) ?>"><span></span><?= e(__('Défiler')) ?></a>
</section>

<!-- Qui sommes-nous + chiffres -->
<section class="section" id="decouvrir">
    <div class="container">
        <p class="section-label reveal"><b>01</b> <?= e(__('Qui sommes-nous')) ?></p>
        <p class="statement" data-words><?= e(str_replace(['**', '##'], '', trim(preg_split("/\n{2,}/", trim(setting_t('about_teaser')))[0]))) ?></p>
        <div class="about-grid">
            <div class="img-reveal">
                <?php if (setting('about_image')): ?>
                    <img src="<?= e(media(setting('about_image'))) ?>" alt="<?= e(__('Campus de l\'Université Adventiste Zurcher')) ?>" loading="lazy">
                <?php else: ?>
                    <div class="media-placeholder"><img src="<?= e(site_logo()) ?>" alt="" class="placeholder-seal"></div>
                <?php endif; ?>
            </div>
            <div class="about-side">
                <div class="stat-list">
                    <div class="stat-row reveal"><strong data-count="<?= $stats['completed'] ?>"><?= $stats['completed'] ?></strong><span><?= e(__('Projets achevés')) ?><br><small><?= e(__('grâce à nos donateurs')) ?></small></span></div>
                    <div class="stat-row reveal"><strong data-count="<?= $stats['ongoing'] ?>"><?= $stats['ongoing'] ?></strong><span><?= e(__('Projets en cours')) ?><br><small><?= e(__('à finaliser ensemble')) ?></small></span></div>
                    <div class="stat-row reveal"><strong data-count="<?= $stats['upcoming'] ?>"><?= $stats['upcoming'] ?></strong><span><?= e(__('Projets à venir')) ?><br><small><?= e(__('pour préparer demain')) ?></small></span></div>
                    <div class="stat-row reveal"><strong data-count="<?= $stats['donors'] ?>"><?= $stats['donors'] ?></strong><span><?= e(__('Donateurs')) ?><br><small><?= e(__('qui nous font confiance')) ?></small></span></div>
                </div>
                <a class="link-arrow reveal" href="<?= route_url('about') ?>"><?= e(__('En savoir plus sur notre mission')) ?> <?= icon('arrow', 18) ?></a>
            </div>
        </div>
    </div>
</section>

<!-- Projets : défilement horizontal épinglé -->
<?php if ($projects): ?>
<section class="section section-alt h-section" data-hscroll>
    <div class="container h-head">
        <div>
            <p class="section-label reveal"><b>02</b> <?= e(__('Nos projets')) ?></p>
            <h2 class="section-title" data-split><?= inline_format(__('Ils ont **besoin de vous**')) ?></h2>
        </div>
        <div class="h-head-side reveal">
            <p><?= e(__('Construction, équipement, bourses : chaque projet est suivi et son financement affiché en toute transparence.')) ?></p>
            <a class="link-arrow" href="<?= route_url('projects') ?>"><?= e(__('Tous les projets')) ?> <?= icon('arrow', 18) ?></a>
        </div>
    </div>
    <div class="h-viewport">
        <div class="h-track">
            <?php foreach ($projects as $i => $p): ?><?= partial('project_card', ['p' => $p, 'n' => $i + 1]) ?><?php endforeach; ?>
            <a class="project-card more-card" href="<?= route_url('projects') ?>">
                <span class="more-icon"><?= icon('arrow', 32) ?></span>
                <strong><?= inline_format(__('Voir tous les projets')) ?></strong>
            </a>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Comment donner : étapes « collantes » -->
<section class="section steps-section">
    <div class="container steps-layout">
        <div class="steps-sticky">
            <p class="section-label reveal"><b>03</b> <?= e(__('Comment donner')) ?></p>
            <h2 class="section-title" data-split><?= inline_format(__('Donner en **trois étapes**')) ?></h2>
            <p class="muted reveal"><?= e(__('Simple, sûr et traçable — depuis Madagascar ou l\'étranger.')) ?></p>
            <a class="btn btn-primary magnetic reveal" href="<?= route_url('give') ?>"><span><?= e(__('Voir les moyens de don')) ?> <?= icon('arrow', 18) ?></span></a>
        </div>
        <ol class="steps-list" data-steps>
            <li class="step">
                <span class="step-index">01</span>
                <div><h3><?= e(__('Choisissez un projet')) ?></h3><p><?= e(__('Construction, équipement ou parrainage d\'étudiants avec SPONSOR : soutenez ce qui vous tient à cœur.')) ?></p></div>
            </li>
            <li class="step">
                <span class="step-index">02</span>
                <div><h3><?= e(__('Faites votre don')) ?></h3><p><?= e(__('Virement bancaire, Mobile Money ou paiement en ligne. Toutes les coordonnées se copient en un clic.')) ?></p></div>
            </li>
            <li class="step">
                <span class="step-index">03</span>
                <div><h3><?= e(__('Prévenez-nous')) ?></h3><p><?= e(__('Remplissez le formulaire : nous vous remercions, envoyons un reçu sur demande et vous tenons informé.')) ?></p></div>
            </li>
            <span class="steps-line" aria-hidden="true"><span></span></span>
        </ol>
    </div>
</section>

<!-- Témoignages : carrousel -->
<?php if ($testimonials): ?>
<section class="section section-dark" data-header="dark">
    <div class="container">
        <div class="t-head">
            <p class="section-label light reveal"><b>04</b> <?= e(__('Témoignages')) ?></p>
            <a class="link-arrow light reveal" href="<?= route_url('testimonials') ?>"><?= e(__('Tous les témoignages')) ?> <?= icon('arrow', 18) ?></a>
        </div>
        <div class="t-slider" data-slider>
            <?php foreach ($testimonials as $i => $t): ?>
                <figure class="t-slide<?= $i === 0 ? ' is-active' : '' ?>">
                    <blockquote><?= e(loc($t, 'quote')) ?></blockquote>
                    <figcaption>
                        <?php if ($t['photo']): ?><img src="<?= e(media($t['photo'])) ?>" alt="" class="avatar" loading="lazy"><?php else: ?><span class="avatar avatar-initials"><?= e(initials($t['name'])) ?></span><?php endif; ?>
                        <span><strong><?= e($t['name']) ?></strong><?php if (loc($t, 'role') !== ''): ?><small><?= e(loc($t, 'role')) ?></small><?php endif; ?></span>
                    </figcaption>
                </figure>
            <?php endforeach; ?>
            <?php if (count($testimonials) > 1): ?>
                <div class="t-controls">
                    <button type="button" class="t-btn" data-prev aria-label="<?= e(__('Témoignage précédent')) ?>"><?= icon('arrow', 20) ?></button>
                    <div class="t-dots">
                        <?php foreach ($testimonials as $i => $t): ?><button type="button" class="t-dot<?= $i === 0 ? ' is-active' : '' ?>" aria-label="<?= e(__('Témoignage :n', ['n' => $i + 1])) ?>"><span></span></button><?php endforeach; ?>
                    </div>
                    <button type="button" class="t-btn" data-next aria-label="<?= e(__('Témoignage suivant')) ?>"><?= icon('arrow', 20) ?></button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Verset qui s'illumine au défilement -->
<?php if (setting_t('quote_text')): ?>
<section class="section verse">
    <div class="container narrow">
        <p class="verse-text" data-words-scrub><?= e(setting_t('quote_text')) ?></p>
        <p class="verse-ref reveal"><?= e(setting_t('quote_author')) ?></p>
    </div>
</section>
<?php endif; ?>

<!-- Donateurs : double bandeau -->
<?php if ($donors): ?>
<section class="section section-alt donors-band">
    <div class="container t-head">
        <div>
            <p class="section-label reveal"><b>05</b> <?= e(__('Merci')) ?></p>
            <h2 class="section-title" data-split><?= inline_format(__('Ils nous **soutiennent**')) ?></h2>
        </div>
        <a class="link-arrow reveal" href="<?= route_url('donors') ?>"><?= e(__('Le mur des donateurs')) ?> <?= icon('arrow', 18) ?></a>
    </div>
    <?php
    $loop = $donors;
    while (count($loop) < 10) {
        $loop = array_merge($loop, $donors);
    }
    $half = (int) ceil(count($loop) / 2);
    ?>
    <?php foreach ([array_slice($loop, 0, $half) ?: $loop, array_reverse($loop)] as $r => $row): ?>
        <div class="marquee<?= $r ? ' reverse' : '' ?>">
            <div class="marquee-track">
                <?php foreach ([false, true] as $copy): foreach ($row as $d): ?>
                    <a class="marquee-item" href="<?= route_url('donors', ['slug' => $d['id']]) ?>"<?= $copy ? ' aria-hidden="true" tabindex="-1"' : '' ?>>
                        <?php if ($d['logo'] && empty($d['mask_name'])): ?><img src="<?= e(media($d['logo'])) ?>" alt="" loading="lazy"><?php else: ?><span class="donor-initials"><?= e(donor_initials($d)) ?></span><?php endif; ?>
                        <span><strong><?= e(donor_name($d)) ?></strong><small><?= e(donor_type_label($d['donor_type'])) ?><?= $d['country'] ? ' · ' . e($d['country']) : '' ?></small></span>
                    </a>
                <?php endforeach; endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>
</section>
<?php endif; ?>

<?= partial('cta') ?>
