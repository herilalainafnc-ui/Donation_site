<?php $p = $project; $pct = percent($p['raised_amount'], $p['goal_amount']); $location = loc($p, 'location'); ?>
<section class="page-hero project-hero<?= $p['cover_image'] ? ' has-image' : '' ?>" data-header="dark"<?= $p['cover_image'] ? ' style="--hero-img: url(\'' . e(media($p['cover_image'])) . '\')"' : '' ?>>
    <div class="aurora" aria-hidden="true"><span></span><span></span><span></span></div>
    <div class="hero-grid-lines" aria-hidden="true"></div>
    <img class="hero-seal" src="<?= e(site_logo()) ?>" alt="" aria-hidden="true">
    <div class="container page-hero-inner" data-hero-text>
        <a class="back-link" href="<?= route_url('projects') ?>">← <?= e(__('Tous les projets')) ?></a>
        <p class="eyebrow"><span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span><?php if ($location !== ''): ?> <?= e($location) ?><?php endif; ?></p>
        <h1 class="display-md" data-split><?= e(loc($p, 'title')) ?></h1>
        <?php if (loc($p, 'summary') !== ''): ?><p class="lead" data-fade><?= e(loc($p, 'summary')) ?></p><?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container project-layout">
        <article class="project-content">
            <div class="prose prose-lg"><?= rich_text(loc($p, 'description')) ?></div>

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

            <?php if ($donors): ?>
                <h2 class="section-title small"><?= e(__('Merci à nos donateurs')) ?></h2>
                <div class="donor-grid compact">
                    <?php foreach ($donors as $d): ?><?= partial('donor_card', ['d' => $d]) ?><?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <aside class="project-aside">
            <div class="sticky-card">
                <?php if ((float) $p['goal_amount'] > 0): ?>
                    <p class="raised"><?= e(money($p['raised_amount'])) ?></p>
                    <p class="goal"><?= e(__('collectés sur :goal', ['goal' => money($p['goal_amount'])])) ?></p>
                    <div class="progress progress-lg"><span style="--pct: <?= $pct ?>%"></span></div>
                    <p class="pct"><?= e(__(':pct % de l\'objectif', ['pct' => $pct])) ?></p>
                    <?php if ($p['status'] !== 'completed' && $p['goal_amount'] > $p['raised_amount']): ?>
                        <p class="remaining"><?= __('Il reste :amount à financer.', ['amount' => '<strong>' . e(money($p['goal_amount'] - $p['raised_amount'])) . '</strong>']) ?></p>
                    <?php endif; ?>
                <?php endif; ?>
                <ul class="facts">
                    <?php if ($location !== ''): ?><li><?= icon('pin', 18) ?> <?= e($location) ?></li><?php endif; ?>
                    <?php if ($p['start_date']): ?><li><?= icon('calendar', 18) ?> <?= e(__('Début :')) ?> <?= e(format_date($p['start_date'])) ?></li><?php endif; ?>
                    <?php if ($p['end_date']): ?><li><?= icon('calendar', 18) ?> <?= e($p['status'] === 'completed' ? __('Achevé le :') : __('Fin prévue :')) ?> <?= e(format_date($p['end_date'])) ?></li><?php endif; ?>
                </ul>
                <?php if ($p['status'] !== 'completed'): ?>
                    <a class="btn btn-gold btn-block btn-lg" href="<?= route_url('give', ['query' => ['project' => $p['slug']], 'hash' => 'promesse']) ?>"><span><?= icon('heart', 20) ?> <?= e(__('Soutenir ce projet')) ?></span></a>
                <?php else: ?>
                    <p class="thanks"><?= icon('check', 20) ?> <?= e(__('Projet réalisé grâce à vous. Merci !')) ?></p>
                    <a class="btn btn-primary btn-block" href="<?= route_url('projects', ['query' => ['status' => 'ongoing']]) ?>"><span><?= e(__('Voir les projets en cours')) ?></span></a>
                <?php endif; ?>
            </div>
        </aside>
    </div>
</section>

<?php if ($others): ?>
<section class="section section-alt">
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
    <figure><img src="" alt=""><figcaption></figcaption></figure>
</div>
