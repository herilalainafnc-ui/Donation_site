<?php $masked = !empty($d['mask_name']); ?>
<div class="donor-card reveal">
    <?php if ($d['logo'] && !$masked): ?>
        <img src="<?= e(media($d['logo'])) ?>" alt="" class="donor-logo" loading="lazy">
    <?php else: ?>
        <span class="donor-initials"><?= e(donor_initials($d)) ?></span>
    <?php endif; ?>
    <div class="donor-info">
        <strong>
            <?php if ($d['website'] && !$masked): ?><a href="<?= e($d['website']) ?>" target="_blank" rel="noopener nofollow"><?= e(donor_name($d)) ?></a><?php else: ?><?= e(donor_name($d)) ?><?php endif; ?>
        </strong>
        <small><?= e(donor_type_label($d['donor_type'])) ?><?= $d['country'] ? ' · ' . e($d['country']) : '' ?></small>
        <?php if (!empty($full)): ?>
            <?php if ($d['show_amount'] && (float) $d['amount'] > 0): ?><span class="donor-amount"><?= e(money($d['amount'])) ?></span><?php endif; ?>
            <?php if (!empty($d['project_slug'])): ?><small><?= e(__('Projet :')) ?> <a href="<?= route_url('projects', ['slug' => $d['project_slug']]) ?>"><?= e(loc(['title' => $d['project_title'], 'title_en' => $d['project_title_en'] ?? ''], 'title')) ?></a></small><?php endif; ?>
            <?php if (loc($d, 'message') !== ''): ?><p class="donor-message">« <?= e(loc($d, 'message')) ?> »</p><?php endif; ?>
        <?php endif; ?>
    </div>
</div>
