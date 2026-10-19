<?= partial('page_hero', ['kicker' => __('Témoignages'), 'title' => __('Ils racontent **leur parcours**'), 'lead' => __('Grâce à la générosité de nos donateurs, des étudiants ont pu poursuivre leurs études et réaliser leurs rêves.')]) ?>

<section class="section">
    <div class="container">
        <?php if ($testimonials): ?>
            <div class="testimonial-grid light">
                <?php foreach ($testimonials as $t): ?><?= partial('testimonial_card', ['t' => $t]) ?><?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state"><?= icon('quote', 48) ?><p><?= e(__('Les témoignages arrivent bientôt.')) ?></p></div>
        <?php endif; ?>
        <?php if (setting('website_url')): ?>
            <p class="center more-link"><a class="link-arrow" href="<?= e(rtrim(setting('website_url'), '/')) ?>" target="_blank" rel="noopener"><?= e(__('Visitez le site de l\'université')) ?> <?= icon('arrow', 18) ?></a></p>
        <?php endif; ?>
    </div>
</section>

<?= partial('cta') ?>
