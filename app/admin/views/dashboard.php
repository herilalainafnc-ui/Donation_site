<div class="kpis">
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'ongoing']) ?>"><span class="kpi-icon"><?= icon('building') ?></span><div><strong><?= $byStatus['ongoing'] ?></strong><small>Projets en cours</small></div></a>
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'upcoming']) ?>"><span class="kpi-icon"><?= icon('target') ?></span><div><strong><?= $byStatus['upcoming'] ?></strong><small>Projets à venir</small></div></a>
    <a class="kpi" href="<?= admin_url('projects', ['f' => 'completed']) ?>"><span class="kpi-icon"><?= icon('check') ?></span><div><strong><?= $byStatus['completed'] ?></strong><small>Projets achevés</small></div></a>
    <a class="kpi" href="<?= admin_url('donors') ?>"><span class="kpi-icon"><?= icon('users') ?></span><div><strong><?= $donors ?></strong><small>Donateurs</small></div></a>
    <a class="kpi <?= $unread ? 'kpi-alert' : '' ?>" href="<?= admin_url('messages') ?>"><span class="kpi-icon"><?= icon('mail') ?></span><div><strong><?= $unread ?></strong><small>Messages non lus</small></div></a>
    <a class="kpi" href="<?= admin_url('stats') ?>"><span class="kpi-icon"><?= icon('chart') ?></span><div><strong><?= number_format($views, 0, ',', ' ') ?></strong><small>Pages vues (30 jours)</small></div></a>
</div>

<section class="panel todo-panel">
    <div class="panel-head"><h2><?= icon('check', 18) ?> À faire</h2><?php if (!$todos): ?><span class="pill pill-ok">Tout est à jour</span><?php endif; ?></div>
    <?php if ($todos): ?>
        <ul class="todo-list">
            <?php foreach ($todos as [$text, $link, $ic]): ?>
                <li><a href="<?= e($link) ?>"><span class="todo-icon"><?= icon($ic, 18) ?></span><span><?= e($text) ?></span><?= icon('chevron', 16) ?></a></li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p class="muted">Aucun point en attente : messages lus, traductions et légendes complètes.</p>
    <?php endif; ?>
</section>

<div class="grid-2">
    <section class="panel">
        <div class="panel-head"><h2>Financement</h2></div>
        <p class="big-number"><?= e(money_totals($raised)) ?></p>
        <p class="muted">collectés au total (tous projets<?= count(array_filter($raised)) > 1 ? ', par devise' : '' ?>)</p>
        <?php foreach ($goal as $cur => $g): if ($g <= 0) continue; $ro = $raisedOpen[$cur] ?? 0; $pct = percent($ro, $g); ?>
            <div class="progress"><span style="width: <?= $pct ?>%"></span></div>
            <p class="muted"><?= e(money($ro, $cur)) ?> sur <?= e(money($g, $cur)) ?> pour les projets en cours et à venir (<?= $pct ?> %)</p>
        <?php endforeach; ?>
        <h3>Projets en cours</h3>
        <?php if ($projects): ?>
            <ul class="mini-list">
                <?php foreach ($projects as $p): $pc = project_pct($p); ?>
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
                            <span class="muted"><?= $m['amount'] ? e(money($m['amount'], ($m['project_id'] ? currency_for_project($m['project_id']) : null))) : e(excerpt($m['body'] ?? '', 50)) ?></span>
                            <small><?= e(format_date($m['created_at'])) ?></small>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?><p class="muted">Aucun message pour le moment.</p><?php endif; ?>
    </section>
</div>

<section class="panel">
    <div class="panel-head"><h2>Raccourcis</h2></div>
    <div class="shortcuts">
        <a href="<?= admin_url('projects', ['a' => 'new']) ?>"><span><?= icon('building', 22) ?></span><strong>Nouveau projet</strong><small>Présentation, objectif, photos</small></a>
        <a href="<?= admin_url('updates', ['a' => 'new']) ?>"><span><?= icon('calendar', 22) ?></span><strong>Étape de chantier</strong><small>Photos légendées de l'avancement</small></a>
        <a href="<?= admin_url('donors', ['a' => 'new']) ?>"><span><?= icon('users', 22) ?></span><strong>Ajouter un donateur</strong><small>Mur des donateurs</small></a>
        <a href="<?= admin_url('testimonials', ['a' => 'new']) ?>"><span><?= icon('quote', 22) ?></span><strong>Ajouter un témoignage</strong><small>Étudiants, partenaires</small></a>
        <a href="<?= admin_url('settings', ['tab' => 'home']) ?>"><span><?= icon('home', 22) ?></span><strong>Page d'accueil</strong><small>Bannière, textes, citation</small></a>
        <a href="<?= admin_url('donations') ?>"><span><?= icon('heart', 22) ?></span><strong>Dons en ligne</strong><small>Paiements et dons réguliers</small></a>
    </div>
</section>
