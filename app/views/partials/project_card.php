<?php $pct = percent($p['raised_amount'], $p['goal_amount']); $pUrl = route_url('projects', ['slug' => $p['slug']]); ?>
<article class="project-card reveal tilt">
    <a class="project-media" href="<?= $pUrl ?>" tabindex="-1" aria-hidden="true">
        <?php if ($p['cover_image']): ?>
            <img src="<?= e(media($p['cover_image'])) ?>" alt="" loading="lazy">
        <?php else: ?>
            <span class="media-placeholder"><?= icon('building', 52) ?></span>
        <?php endif; ?>
        <span class="badge badge-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
        <?php if (!empty($n)): ?><span class="card-num"><?= str_pad((string) $n, 2, '0', STR_PAD_LEFT) ?></span><?php endif; ?>
    </a>
    <div class="project-body">
        <p class="meta">
            <?php if (!empty($p['code'])): ?><span class="code-tag"><?= e($p['code']) ?></span><?php endif; ?>
            <?php if (loc($p, 'location') !== ''): ?><span><?= icon('pin', 15) ?> <?= e(loc($p, 'location')) ?></span><?php endif; ?>
        </p>
        <h3><a href="<?= $pUrl ?>"><?= e(loc($p, 'title')) ?></a></h3>
        <p class="excerpt"><?= e(excerpt(loc($p, 'summary') ?: loc($p, 'description'), 120)) ?></p>
        <?php if ((float) $p['goal_amount'] > 0): ?>
            <div class="funding">
                <div class="progress" role="progressbar" aria-valuenow="<?= $pct ?>" aria-valuemin="0" aria-valuemax="100" aria-label="<?= e(__('Financement')) ?>"><span style="--pct: <?= $pct ?>%"></span></div>
                <div class="progress-legend">
                    <span><strong><?= e(money($p['raised_amount'])) ?></strong> / <?= e(money($p['goal_amount'])) ?></span>
                    <span><?= $pct ?> %</span>
                </div>
            </div>
        <?php endif; ?>
        <div class="card-actions">
            <a class="link-arrow" href="<?= $pUrl ?>"><?= e(project_cta_label($p['status'])) ?> <?= icon('arrow', 18) ?></a>
            <?php if ($p['status'] !== 'completed'): ?>
                <a class="btn btn-gold btn-sm" href="<?= route_url('give', ['query' => ['project' => $p['slug']], 'hash' => 'promesse']) ?>"><span><?= e(__('Soutenir')) ?></span></a>
            <?php endif; ?>
        </div>
    </div>
</article>
