<?php
$masked = !empty($d['mask_name']);
$name = donor_name($d);
$hasPhoto = $d['logo'] && !$masked;
$showContact = !$masked && !empty($d['show_contact']) && ($d['email'] || $d['phone'] || $d['whatsapp']);
$message = loc($d, 'message');
$projectTitle = $d['project_slug'] ? loc(['title' => $d['project_title'], 'title_en' => $d['project_title_en']], 'title') : '';
?>
<section class="page-hero donor-hero" data-header="dark">
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <img class="hero-seal" src="<?= e(site_logo()) ?>" alt="" aria-hidden="true">
    <div class="container page-hero-inner" data-hero-text>
        <a class="back-link" href="<?= route_url('donors') ?>">← <?= e(__('Tous les donateurs')) ?></a>
        <p class="eyebrow"><span class="eyebrow-line"></span><?= e(donor_type_label($d['donor_type'])) ?></p>
        <h1 class="display-md" data-split><?= e($name) ?></h1>
        <p class="lead" data-fade><?= e(__('Merci pour votre générosité envers nos étudiants.')) ?></p>
    </div>
</section>

<section class="section donor-profile">
    <div class="container donor-layout">
        <div class="donor-photo-wrap reveal">
            <?php if ($hasPhoto): ?>
                <a href="<?= e(media($d['logo'])) ?>" class="donor-photo" data-lightbox data-caption="<?= e($name) ?>">
                    <img src="<?= e(media($d['logo'])) ?>" alt="<?= e($name) ?>">
                </a>
            <?php else: ?>
                <div class="donor-photo donor-photo-empty"><span><?= e(donor_initials($d)) ?></span></div>
            <?php endif; ?>
            <span class="donor-thanks-badge"><?= icon('heart', 18) ?> <?= e(__('Donateur UAZ')) ?></span>
        </div>

        <div class="donor-details">
            <p class="section-label reveal"><b><?= icon('users', 14) ?></b> <?= e(__('Informations')) ?></p>
            <dl class="spec spec-lg reveal">
                <div><dt><?= e(__('Nom')) ?></dt><dd><?= e($name) ?></dd></div>
                <div><dt><?= e(__('Type')) ?></dt><dd><?= e(donor_type_label($d['donor_type'])) ?></dd></div>
                <?php if ($d['country']): ?><div><dt><?= e(__('Pays')) ?></dt><dd><?= e($d['country']) ?></dd></div><?php endif; ?>
                <?php if ($d['donated_on']): ?><div><dt><?= e(__('Date du don')) ?></dt><dd><?= e(format_date($d['donated_on'])) ?></dd></div><?php endif; ?>
                <?php if ($d['show_amount'] && (float) $d['amount'] > 0): ?><div><dt><?= e(__('Montant du don')) ?></dt><dd class="donor-amount"><?= e(money($d['amount'], ($d['project_id'] ?? null) ? currency_for_project($d['project_id']) : null)) ?></dd></div><?php endif; ?>
                <?php if ($d['website'] && !$masked): ?><div><dt><?= e(__('Site web')) ?></dt><dd><a href="<?= e($d['website']) ?>" target="_blank" rel="noopener nofollow"><?= e(preg_replace('#^https?://(www\.)?#', '', rtrim($d['website'], '/'))) ?> ↗</a></dd></div><?php endif; ?>
            </dl>

            <?php if ($message !== ''): ?>
                <blockquote class="donor-quote reveal"><?= nl2br(e($message)) ?></blockquote>
            <?php endif; ?>

            <?php if ($showContact): ?>
                <div class="donor-contacts reveal">
                    <h3><?= e(__('Contact')) ?></h3>
                    <div class="contact-buttons">
                        <?php if ($d['email']): ?><a class="contact-btn" href="mailto:<?= e($d['email']) ?>"><?= icon('mail', 18) ?> <?= e($d['email']) ?></a><?php endif; ?>
                        <?php if ($d['phone']): ?><a class="contact-btn" href="tel:<?= e(preg_replace('/[^\d+]/', '', $d['phone'])) ?>"><?= icon('phone', 18) ?> <?= e($d['phone']) ?></a><?php endif; ?>
                        <?php if ($d['whatsapp']): ?><a class="contact-btn" href="https://wa.me/<?= e(preg_replace('/\D/', '', $d['whatsapp'])) ?>" target="_blank" rel="noopener"><?= icon('whatsapp', 18) ?> WhatsApp</a><?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($projectTitle !== ''): $ppct = percent((float) $d['project_raised'] + (float) $d['project_uaz'], $d['project_goal']); ?>
                <div class="donor-project reveal">
                    <h3><?= e(__('Projet soutenu')) ?></h3>
                    <a class="donor-project-card" href="<?= route_url('projects', ['slug' => $d['project_slug']]) ?>">
                        <span class="dp-media"><?php if ($d['project_cover']): ?><img src="<?= e(media($d['project_cover'])) ?>" alt="<?= e($projectTitle) ?>" loading="lazy"><?php else: ?><?= icon('building', 32) ?><?php endif; ?></span>
                        <span class="dp-body">
                            <span class="meta"><?php if ($d['project_code']): ?><span class="code-tag"><?= e($d['project_code']) ?></span><?php endif; ?> <?= e(status_label((string) $d['project_status'])) ?></span>
                            <strong><?= e($projectTitle) ?></strong>
                            <?php if ((float) $d['project_goal'] > 0): ?>
                                <span class="progress"><span style="--pct: <?= $ppct ?>%"></span></span>
                                <small><?= $ppct ?> % · <?= e(money((float) $d['project_raised'] + (float) $d['project_uaz'], currency_for_project($d['project_id']))) ?> / <?= e(money($d['project_goal'], currency_for_project($d['project_id']))) ?></small>
                            <?php endif; ?>
                        </span>
                        <?= icon('arrow', 20) ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?= partial('cta') ?>

<div class="lightbox" hidden data-prev="<?= e(__('Photo précédente')) ?>" data-next="<?= e(__('Photo suivante')) ?>" aria-label="<?= e(__('Galerie photos')) ?>">
    <button class="lightbox-close" aria-label="<?= e(__('Fermer')) ?>"><?= icon('close', 28) ?></button>
    <figure><img alt="" decoding="async"><figcaption></figcaption></figure>
</div>
