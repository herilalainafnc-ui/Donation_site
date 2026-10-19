<figure class="testimonial reveal">
    <span class="quote-mark" aria-hidden="true">“</span>
    <blockquote><?= nl2br(e(loc($t, 'quote'))) ?></blockquote>
    <figcaption>
        <?php if ($t['photo']): ?>
            <img src="<?= e(media($t['photo'])) ?>" alt="" class="avatar" loading="lazy">
        <?php else: ?>
            <span class="avatar avatar-initials"><?= e(initials($t['name'])) ?></span>
        <?php endif; ?>
        <span><strong><?= e($t['name']) ?></strong><?php if (loc($t, 'role') !== ''): ?><small><?= e(loc($t, 'role')) ?></small><?php endif; ?></span>
    </figcaption>
</figure>
