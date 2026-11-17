<?php
$p = $project;
$location = loc($p, 'location');
$isDone = $p['status'] === 'completed';
$hasGoal = $dash['goal'] > 0;
$dashTitle = match ($p['status']) {
    'ongoing' => __('Avancement du projet'),
    'completed' => __('Bilan du projet'),
    default => __('Le projet en un coup d\'œil'),
};
$reportUrl = route_url('projects', ['slug' => $p['slug'], 'report' => true, 'query' => $p['report_file'] ? [] : ['download' => 1]]);
$giveUrl = route_url('give', ['query' => ['project' => $p['slug']], 'hash' => 'promesse']);
$circ = 2 * M_PI * 54;
?>
<section class="page-hero project-hero<?= $p['cover_image'] ? ' has-image' : '' ?>" data-header="dark"<?= $p['cover_image'] ? ' style="--hero-img: url(\'' . e(media($p['cover_image'])) . '\')"' : '' ?>>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <img class="hero-seal" src="<?= e(site_logo()) ?>" alt="" aria-hidden="true">
    <div class="container page-hero-inner" data-hero-text>
        <a class="back-link" href="<?= route_url('projects') ?>">← <?= e(__('Tous les projets')) ?></a>
        <p class="eyebrow">
            <span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
            <?php if ($p['code']): ?><span class="code-tag light"><?= e(__('Code projet')) ?> · <?= e($p['code']) ?></span><?php endif; ?>
        </p>
        <h1 class="display-md" data-split><?= e(loc($p, 'title')) ?></h1>
        <?php if (loc($p, 'summary') !== ''): ?><p class="lead" data-fade><?= e(loc($p, 'summary')) ?></p><?php endif; ?>
    </div>
</section>

<!-- Tableau de bord -->
<section class="section dash-section">
    <div class="container">
        <div class="dash reveal">
            <div class="dash-head">
                <div>
                    <p class="section-label"><b><?= e($p['code'] ?: '—') ?></b> <?= e($dashTitle) ?></p>
                </div>
                <div class="dash-actions">
                    <?php if (!$isDone): ?>
                        <a class="btn btn-gold magnetic" href="<?= $giveUrl ?>"><span><?= icon('heart', 18) ?> <?= e(__('Soutenir ce projet')) ?></span></a>
                    <?php endif; ?>
                    <a class="btn btn-outline" href="<?= $reportUrl ?>" <?= $p['report_file'] ? 'download' : 'target="_blank" rel="noopener"' ?>><span><?= icon('download', 18) ?> <?= e(__('Télécharger la fiche de suivi')) ?></span></a>
                </div>
            </div>

            <div class="dash-body<?= $hasGoal ? '' : ' no-goal' ?>">
                <?php if ($hasGoal): ?>
                    <div class="dash-ring" style="--pct: <?= $dash['pct'] ?>">
                        <svg viewBox="0 0 120 120" aria-hidden="true">
                            <circle class="ring-track" cx="60" cy="60" r="54"/>
                            <circle class="ring-fill" cx="60" cy="60" r="54" stroke-dasharray="<?= round($circ, 2) ?>" stroke-dashoffset="<?= round($circ * (1 - $dash['pct'] / 100), 2) ?>" data-circ="<?= round($circ, 2) ?>"/>
                        </svg>
                        <div class="ring-label"><strong data-count="<?= $dash['pct'] ?>"><?= $dash['pct'] ?></strong><span>%</span><small><?= e($isDone ? __('du budget réalisé') : __('financé')) ?></small></div>
                    </div>
                <?php endif; ?>

                <div class="dash-tiles">
                    <div class="tile tile-raised">
                        <span class="tile-label"><?= icon('check', 18) ?> <?= e(__('Montant collecté')) ?></span>
                        <strong><?= e(money($dash['raised'])) ?></strong>
                    </div>
                    <?php if ($hasGoal): ?>
                        <div class="tile tile-missing<?= $dash['missing'] <= 0 ? ' is-done' : '' ?>">
                            <span class="tile-label"><?= icon('target', 18) ?> <?= e(__('Reste à financer')) ?></span>
                            <strong><?= $dash['missing'] > 0 ? e(money($dash['missing'])) : e(__('Objectif atteint')) ?></strong>
                        </div>
                        <div class="tile">
                            <span class="tile-label"><?= icon('building', 18) ?> <?= e(__('Objectif')) ?></span>
                            <strong><?= e(money($dash['goal'])) ?></strong>
                        </div>
                    <?php endif; ?>
                    <div class="tile">
                        <span class="tile-label"><?= icon('users', 18) ?> <?= e(__('Donateurs')) ?></span>
                        <strong data-count="<?= $dash['donors'] ?>"><?= $dash['donors'] ?></strong>
                    </div>
                </div>
            </div>

            <?php if ($hasGoal): ?>
                <div class="dash-bar">
                    <div class="progress progress-lg"><span style="--pct: <?= $dash['pct'] ?>%"></span></div>
                    <div class="dash-bar-legend"><span><?= e(money($dash['raised'])) ?></span><span><?= e(money($dash['goal'])) ?></span></div>
                </div>
            <?php endif; ?>

            <?php if ($dash['time_pct'] !== null): ?>
                <div class="dash-timeline">
                    <div class="timeline-dates">
                        <span><?= icon('calendar', 16) ?> <?= e(__('Début :')) ?> <?= e(format_date($p['start_date'])) ?></span>
                        <span><?= e($isDone ? __('Achevé le :') : __('Fin prévue :')) ?> <?= e(format_date($p['end_date'])) ?></span>
                    </div>
                    <div class="timeline-track"><span style="width: <?= $dash['time_pct'] ?>%"></span><i style="left: <?= $dash['time_pct'] ?>%"></i></div>
                    <p class="timeline-note"><?= e($isDone || $dash['days_left'] === 0 ? __('Calendrier terminé') : __(':n jours restants', ['n' => $dash['days_left']])) ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Détails du projet -->
<section class="section section-tight">
    <div class="container project-layout">
        <article class="project-content">
            <p class="section-label reveal"><b>01</b> <?= e(__('Détails du projet')) ?></p>
            <div class="prose prose-lg reveal"><?= rich_text(loc($p, 'description')) ?></div>

            <?php if ($images): ?>
                <h2 class="section-title small"><?= e(__('Galerie photos')) ?></h2>
                <div class="gallery">
                    <?php foreach ($images as $img): ?>
                        <a href="<?= e(media($img['path'])) ?>" class="gallery-item" data-lightbox data-caption="<?= e($img['caption']) ?>">
                            <img src="<?= e(media($img['path'])) ?>" alt="<?= e($img['caption'] ?: loc($p, 'title')) ?>" loading="lazy">
                            <?php if ($img['caption']): ?><span><?= e($img['caption']) ?></span><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <aside class="project-aside">
            <div class="sticky-card spec-card">
                <h3><?= e(__('Fiche technique')) ?></h3>
                <dl class="spec">
                    <?php if ($p['code']): ?><div><dt><?= e(__('Code projet')) ?></dt><dd><span class="code-tag"><?= e($p['code']) ?></span></dd></div><?php endif; ?>
                    <div><dt><?= e(__('Statut')) ?></dt><dd><?= e(status_label($p['status'])) ?></dd></div>
                    <?php if ($location !== ''): ?><div><dt><?= e(__('Lieu')) ?></dt><dd><?= e($location) ?></dd></div><?php endif; ?>
                    <?php if ($p['start_date']): ?><div><dt><?= e(__('Début')) ?></dt><dd><?= e(format_date($p['start_date'])) ?></dd></div><?php endif; ?>
                    <?php if ($p['end_date']): ?><div><dt><?= e($isDone ? __('Achèvement') : __('Fin prévue')) ?></dt><dd><?= e(format_date($p['end_date'])) ?></dd></div><?php endif; ?>
                    <?php if ($hasGoal): ?><div><dt><?= e(__('Objectif')) ?></dt><dd><?= e(money($dash['goal'])) ?></dd></div><?php endif; ?>
                    <div><dt><?= e(__('Collecté')) ?></dt><dd><?= e(money($dash['raised'])) ?></dd></div>
                    <div><dt><?= e(__('Mise à jour')) ?></dt><dd><?= e(format_date($p['updated_at'] ?: $p['created_at'])) ?></dd></div>
                </dl>
                <?php if (!$isDone): ?>
                    <a class="btn btn-gold btn-block" href="<?= $giveUrl ?>"><span><?= icon('heart', 18) ?> <?= e(__('Soutenir ce projet')) ?></span></a>
                <?php else: ?>
                    <p class="thanks"><?= icon('check', 20) ?> <?= e(__('Projet réalisé grâce à vous. Merci !')) ?></p>
                <?php endif; ?>
                <a class="btn btn-outline btn-block" href="<?= $reportUrl ?>" <?= $p['report_file'] ? 'download' : 'target="_blank" rel="noopener"' ?>><span><?= icon('download', 18) ?> <?= e(__('Télécharger la fiche de suivi')) ?></span></a>
            </div>
        </aside>
    </div>
</section>

<!-- Donateurs du projet -->
<section class="section section-alt">
    <div class="container">
        <div class="t-head">
            <div>
                <p class="section-label reveal"><b>02</b> <?= e(__('Les donateurs de ce projet')) ?></p>
                <h2 class="section-title" data-split><?= inline_format(__('Merci **du fond du cœur**')) ?></h2>
            </div>
            <?php if (!$isDone): ?><a class="link-arrow reveal" href="<?= $giveUrl ?>"><?= e(__('Rejoignez-les')) ?> <?= icon('arrow', 18) ?></a><?php endif; ?>
        </div>
        <?php if ($donors): ?>
            <div class="donor-grid">
                <?php foreach ($donors as $d): ?><?= partial('donor_card', ['d' => $d, 'full' => true]) ?><?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state reveal">
                <?= icon('users', 48) ?>
                <p><?= e(__('Aucun donateur n\'est encore affiché pour ce projet.')) ?></p>
                <?php if (!$isDone): ?><a class="btn btn-gold" href="<?= $giveUrl ?>"><span><?= e(__('Devenez le premier donateur')) ?></span></a><?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($others): ?>
<section class="section">
    <div class="container">
        <h2 class="section-title"><?= e(__('Autres projets à soutenir')) ?></h2>
        <div class="card-grid">
            <?php foreach ($others as $o): ?><?= partial('project_card', ['p' => $o]) ?><?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<div class="lightbox" hidden>
    <button class="lightbox-close" aria-label="<?= e(__('Fermer')) ?>"><?= icon('close', 28) ?></button>
    <figure><img alt="" decoding="async"><figcaption></figcaption></figure>
</div>
