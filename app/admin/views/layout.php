<?php
$me = admin_user();
$nav ??= 'dashboard';
$unreadCount = (int) fetch_value('SELECT COUNT(*) FROM messages WHERE is_read = 0');
$menu = [
    'dashboard' => ['Tableau de bord', 'target'],
    'projects' => ['Projets', 'building'],
    'testimonials' => ['Témoignages', 'quote'],
    'donors' => ['Donateurs', 'users'],
    'methods' => ['Moyens de don', 'bank'],
    'messages' => ['Messages', 'mail'],
    'stats' => ['Statistiques', 'chart'],
    'settings' => ['Paramètres du site', 'globe'],
    'users' => ['Administrateurs', 'hand'],
];
$flashes = take_flashes();
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Administration') ?> — Admin <?= e(setting('site_short', 'UAZ')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset("css/fonts.css") ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar" id="sidebar">
        <a class="sb-brand" href="<?= admin_url() ?>">
            <img class="brand-mark" src="<?= e(site_logo()) ?>" alt="">
            <span>Administration<small>Site de donation</small></span>
        </a>
        <nav>
            <?php foreach ($menu as $k => [$label, $ic]): ?>
                <a href="<?= admin_url($k) ?>" class="<?= $nav === $k ? 'active' : '' ?>">
                    <?= icon($ic, 20) ?> <span><?= e($label) ?></span>
                    <?php if ($k === 'messages' && $unreadCount): ?><b class="count-badge"><?= $unreadCount ?></b><?php endif; ?>
                </a>
            <?php endforeach; ?>
        </nav>
        <div class="sb-footer">
            <a href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('arrow', 18) ?> Voir le site</a>
        </div>
    </aside>

    <div class="main">
        <header class="topbar">
            <button class="sb-toggle" type="button" aria-label="Menu" onclick="document.getElementById('sidebar').classList.toggle('open')"><?= icon('menu') ?></button>
            <h1><?= e($title ?? '') ?></h1>
            <div class="user-menu">
                <a href="<?= admin_url('account') ?>" class="<?= $nav === 'account' ? 'active' : '' ?>"><span class="avatar-sm"><?= e(initials($me['name'])) ?></span> <span class="hide-sm"><?= e($me['name']) ?></span></a>
                <form method="post" action="<?= admin_url('logout') ?>"><?= csrf_field() ?><button class="btn btn-light btn-sm" type="submit">Déconnexion</button></form>
            </div>
        </header>
        <div class="content">
            <?php foreach ($flashes as $f): ?>
                <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
            <?php endforeach; ?>
            <?= $content ?>
        </div>
    </div>
</div>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
