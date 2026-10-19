<?= partial('page_hero', ['kicker' => __('Nos projets'), 'title' => __('Bâtir ensemble le campus **de demain**'), 'lead' => __('Découvrez ce qui a déjà été réalisé grâce à vous, ce qui est en cours et les projets qui attendent votre soutien.')]) ?>

<section class="section">
    <div class="container">
        <nav class="filters" aria-label="<?= e(__('Filtrer les projets')) ?>">
            <?php
            $tabs = ['' => [__('Tous'), $counts['ongoing'] + $counts['upcoming'] + $counts['completed']],
                'ongoing' => [__('En cours'), $counts['ongoing']], 'upcoming' => [__('À venir'), $counts['upcoming']], 'completed' => [__('Achevés'), $counts['completed']]];
            foreach ($tabs as $key => [$label, $n]): ?>
                <a href="<?= route_url('projects', $key ? ['query' => ['status' => $key]] : []) ?>" class="filter<?= $filter === $key ? ' active' : '' ?>" <?= $filter === $key ? 'aria-current="true"' : '' ?>>
                    <?= e($label) ?> <span><?= $n ?></span>
                </a>
            <?php endforeach; ?>
        </nav>

        <?php if ($projects): ?>
            <div class="card-grid">
                <?php foreach ($projects as $p): ?><?= partial('project_card', ['p' => $p]) ?><?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <?= icon('building', 48) ?>
                <p><?= e(__('Aucun projet dans cette catégorie pour le moment.')) ?></p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?= partial('cta') ?>
