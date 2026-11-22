<?php
/**
 * Cadre de l'administration : menu latéral groupé, barre du haut (fil d'Ariane, compte), en-tête de page.
 * Variables facultatives transmises par les pages : $title, $subtitle, $headerActions (HTML), $crumbs ([libellé => url]), $nav.
 */
$me = admin_user();
$nav ??= 'dashboard';
$subtitle ??= '';
$headerActions ??= '';
$crumbs ??= [];
$unreadCount = (int) fetch_value('SELECT COUNT(*) FROM messages WHERE is_read = 0');
$pendingPay = 0;
try {
    $pendingPay = (int) fetch_value("SELECT COUNT(*) FROM donation_plans WHERE status = 'active' AND next_due_date IS NOT NULL AND next_due_date < ?", [date('Y-m-d')]);
} catch (Throwable) {
}
$groups = [
    'Pilotage' => [
        'dashboard' => ['Tableau de bord', 'home'],
        'stats' => ['Statistiques', 'chart'],
    ],
    'Contenu du site' => [
        'projects' => ['Projets', 'building'],
        'updates' => ['Avancement des travaux', 'calendar'],
        'testimonials' => ['Témoignages', 'quote'],
        'donors' => ['Donateurs', 'users'],
        'methods' => ['Moyens de don', 'bank'],
    ],
    'Dons et contacts' => [
        'donations' => ['Dons en ligne', 'heart'],
        'messages' => ['Messages', 'inbox'],
    ],
    'Configuration' => [
        'settings' => ['Paramètres du site', 'cog'],
        'users' => ['Administrateurs', 'lock'],
    ],
];
$badges = ['messages' => $unreadCount, 'donations' => $pendingPay];
$currentGroup = '';
foreach ($groups as $g => $items) {
    if (isset($items[$nav])) {
        $currentGroup = $g;
    }
}
$flashes = take_flashes();
?><!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title><?= e($title ?? 'Administration') ?> — Admin <?= e(setting('site_short', 'UAZ')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body>
<div class="admin-shell">
    <aside class="sidebar" id="sidebar" aria-label="Menu de l'administration">
        <a class="sb-brand" href="<?= admin_url() ?>">
            <img class="brand-mark" src="<?= e(site_logo()) ?>" alt="">
            <span><?= e(setting('site_short', 'UAZ')) ?> Admin<small>Campagne de dons</small></span>
        </a>
        <nav class="sb-nav">
            <?php foreach ($groups as $group => $items): ?>
                <p class="sb-group"><?= e($group) ?></p>
                <?php foreach ($items as $k => [$label, $ic]): ?>
                    <a href="<?= admin_url($k) ?>" class="<?= $nav === $k ? 'active' : '' ?>" <?= $nav === $k ? 'aria-current="page"' : '' ?>>
                        <?= icon($ic, 19) ?> <span><?= e($label) ?></span>
                        <?php if (!empty($badges[$k])): ?><b class="count-badge" title="<?= $k === 'messages' ? 'Messages non lus' : 'Échéances en retard' ?>"><?= (int) $badges[$k] ?></b><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </nav>
        <a class="sb-site" href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('external', 17) ?> Voir le site public</a>
    </aside>
    <div class="sb-backdrop" data-sidebar-close hidden></div>

    <div class="main">
        <header class="topbar">
            <button class="sb-toggle" type="button" aria-label="Ouvrir le menu" aria-controls="sidebar" aria-expanded="false" data-sidebar-toggle><?= icon('menu', 22) ?></button>
            <nav class="crumbs" aria-label="Fil d'Ariane">
                <a href="<?= admin_url() ?>"><?= icon('home', 15) ?><span class="sr-only">Tableau de bord</span></a>
                <?php if ($currentGroup && $nav !== 'dashboard'): ?><span class="crumb-group"><span class="crumb-sep">/</span> <?= e($currentGroup) ?></span><?php endif; ?>
                <?php foreach ($crumbs as $cLabel => $cUrl): ?><span class="crumb-sep">/</span><a href="<?= e($cUrl) ?>"><?= e($cLabel) ?></a><?php endforeach; ?>
            </nav>
            <div class="topbar-right">
                <a class="btn btn-ghost btn-sm hide-sm" href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('eye', 16) ?> Voir le site</a>
                <details class="user-menu">
                    <summary><span class="avatar-sm"><?= e(initials($me['name'])) ?></span><span class="hide-sm"><?= e($me['name']) ?></span><?= icon('chevron', 14) ?></summary>
                    <div class="user-pop">
                        <p class="user-pop-head"><strong><?= e($me['name']) ?></strong><small><?= e($me['email']) ?></small></p>
                        <a href="<?= admin_url('account') ?>"><?= icon('lock', 16) ?> Mon compte et mot de passe</a>
                        <form method="post" action="<?= admin_url('logout') ?>"><?= csrf_field() ?><button type="submit"><?= icon('logout', 16) ?> Se déconnecter</button></form>
                    </div>
                </details>
            </div>
        </header>

        <main class="content" id="contenu">
            <div class="page-head">
                <div>
                    <h1><?= e($title ?? '') ?></h1>
                    <?php if ($subtitle !== ''): ?><p class="page-sub"><?= e($subtitle) ?></p><?php endif; ?>
                </div>
                <?php if ($headerActions !== ''): ?><div class="page-actions"><?= $headerActions ?></div><?php endif; ?>
            </div>
            <?= $content ?>
        </main>
    </div>
</div>

<?php if ($flashes): ?>
    <div class="toasts" role="status" aria-live="polite">
        <?php foreach ($flashes as $f): ?>
            <div class="toast toast-<?= e($f['type']) ?>"><?= icon($f['type'] === 'success' ? 'check' : 'alert', 18) ?><span><?= e($f['message']) ?></span><button type="button" class="toast-close" aria-label="Fermer">×</button></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<script src="<?= asset('js/admin.js') ?>" defer></script>
</body>
</html>
