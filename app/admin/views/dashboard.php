<div class="kpis">
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'ongoing']) ?>"><span class="kpi-icon"><?= icon('building') ?></span><div><strong><?= $byStatus['ongoing'] ?></strong><small>Projets en cours</small></div></a>
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'upcoming']) ?>"><span class="kpi-icon"><?= icon('target') ?></span><div><strong><?= $byStatus['upcoming'] ?></strong><small>Projets à venir</small></div></a>
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'completed']) ?>"><span class="kpi-icon"><?= icon('check') ?></span><div><strong><?= $byStatus['completed'] ?></strong><small>Projets achevés</small></div></a>
    <a class="kpi" href="<?= admin_url('donors') ?>"><span class="kpi-icon"><?= icon('users') ?></span><div><strong><?= $donors ?></strong><small>Donateurs</small></div></a>
    <a class="kpi <?= $unread ? 'kpi-alert' : '' ?>" href="<?= admin_url('messages') ?>"><span class="kpi-icon"><?= icon('mail') ?></span><div><strong><?= $unread ?></strong><small>Messages non lus</small></div></a>
    <a class="kpi" href="<?= admin_url('stats') ?>"><span class="kpi-icon"><?= icon('chart') ?></span><div><strong><?= number_format($views, 0, ',', ' ') ?></strong><small>Pages vues (30 jours)</small></div></a>
</div>

<div class="grid-2">
    <section class="panel">
        <div class="panel-head"><h2>Financement</h2></div>
        <p class="big-number"><?= e(money($raised)) ?></p>
        <p class="muted">collectés au total (tous projets)</p>
        <?php if ($goal > 0): $pct = percent($raisedOpen, $goal); ?>
            <div class="progress"><span style="width: <?= $pct ?>%"></span></div>
            <p class="muted"><?= e(money($raisedOpen)) ?> sur <?= e(money($goal)) ?> pour les projets en cours et à venir (<?= $pct ?> %)</p>
        <?php endif; ?>
        <h3>Projets en cours</h3>
        <?php if ($projects): ?>
            <ul class="mini-list">
                <?php foreach ($projects as $p): $pc = percent($p['raised_amount'], $p['goal_amount']); ?>
                    <li><a href="<?= admin_url('projects', ['a' => 'edit', 'id' => $p['id']]) ?>"><?= e($p['title']) ?></a><span class="mini-progress"><span style="width: <?= $pc ?>%"></span></span><small><?= $pc ?> %</small></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?><p class="muted">Aucun projet en cours.</p><?php endif; ?>
    </section>

    <section class="panel">
        <div class="panel-head"><h2>Derniers messages</h2><a href="<?= admin_url('messages') ?>">Tout voir</a></div>
        <?php if ($messages): ?>
            <ul class="msg-list">
                <?php foreach ($messages as $m): ?>
                    <li class="<?= $m['is_read'] ? '' : 'unread' ?>">
                        <a href="<?= admin_url('messages', ['a' => 'view', 'id' => $m['id']]) ?>">
                            <span class="pill <?= $m['kind'] === 'pledge' ? 'pill-ongoing' : '' ?>"><?= $m['kind'] === 'pledge' ? 'Don' : 'Contact' ?></span>
                            <strong><?= e($m['name']) ?></strong>
                            <span class="muted"><?= $m['amount'] ? e(money($m['amount'])) : e(excerpt($m['body'] ?? '', 50)) ?></span>
                            <small><?= e(format_date($m['created_at'])) ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?><p class="muted">Aucun message pour le moment.</p><?php endif; ?>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h2>Actions rapides</h2></div>
    <div class="quick-actions">
        <a class="btn btn-primary" href="<?= admin_url('projects', ['a' => 'new']) ?>">+ Nouveau projet</a>
        <a class="btn btn-light" href="<?= admin_url('donors', ['a' => 'new']) ?>">+ Ajouter un donateur</a>
        <a class="btn btn-light" href="<?= admin_url('testimonials', ['a' => 'new']) ?>">+ Ajouter un témoignage</a>
        <a class="btn btn-light" href="<?= admin_url('settings', ['tab' => 'home']) ?>">Modifier la page d'accueil</a>
    </div>
</section>
