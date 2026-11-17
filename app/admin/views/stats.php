<?php
$max = max(1, max($perDay));
$trend = $previous > 0 ? (int) round(($total - $previous) / $previous * 100) : null;
$deviceLabels = ['desktop' => 'Ordinateur', 'mobile' => 'Téléphone', 'tablet' => 'Tablette'];
$langLabels = ['en' => 'Anglais', 'fr' => 'Français'];
$bars = function (array $rows, array $labels = []) {
    $top = max(1, (int) ($rows[0]['n'] ?? 1));
    $html = '<ul class="stat-bars">';
    foreach ($rows as $r) {
        $label = $labels[$r['label']] ?? $r['label'];
        $html .= '<li><span class="stat-bar" style="width:' . round($r['n'] / $top * 100) . '%"></span><span class="stat-label">' . e($label) . '</span><strong>' . number_format((int) $r['n'], 0, ',', ' ') . '</strong></li>';
    }
    return $html . '</ul>';
};
?>
<p class="muted">Mesure d'audience anonyme intégrée au site : sans cookie, sans adresse IP, robots et administrateurs connectés exclus.</p>

<nav class="tabs" aria-label="Période">
    <?php foreach ([7 => '7 jours', 30 => '30 jours', 90 => '3 mois', 365 => '12 mois'] as $d => $label): ?>
        <a href="<?= admin_url('stats', ['d' => $d]) ?>" class="<?= $days === $d ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
</nav>

<div class="kpis">
    <div class="kpi"><span class="kpi-icon"><?= icon('chart') ?></span><div><strong><?= number_format($total, 0, ',', ' ') ?></strong><small>Pages vues (<?= $days ?> j)</small></div></div>
    <div class="kpi"><span class="kpi-icon"><?= icon('calendar') ?></span><div><strong><?= number_format($today, 0, ',', ' ') ?></strong><small>Aujourd'hui</small></div></div>
    <div class="kpi"><span class="kpi-icon"><?= icon('target') ?></span><div><strong><?= $trend === null ? '—' : ($trend >= 0 ? '+' : '') . $trend . ' %' ?></strong><small>Par rapport à la période précédente</small></div></div>
</div>

<section class="panel">
    <div class="panel-head"><h2>Pages vues par jour</h2></div>
    <?php if ($total): ?>
        <div class="stat-chart" role="img" aria-label="Pages vues par jour sur <?= $days ?> jours">
            <?php foreach ($perDay as $day => $n): ?>
                <span title="<?= e(format_date($day)) ?> : <?= $n ?>" style="height: <?= max(2, round($n / $max * 100)) ?>%"></span>
            <?php endforeach; ?>
        </div>
        <div class="stat-chart-axis"><span><?= e(format_date(array_key_first($perDay))) ?></span><span><?= e(format_date(array_key_last($perDay))) ?></span></div>
    <?php else: ?>
        <p class="muted">Aucune visite enregistrée sur cette période pour le moment.</p>
    <?php endif; ?>
</section>

<div class="grid-2">
    <section class="panel">
        <div class="panel-head"><h2>Pages les plus vues</h2></div>
        <?= $pages ? $bars($pages) : '<p class="muted">—</p>' ?>
    </section>
    <section class="panel">
        <div class="panel-head"><h2>Provenance</h2></div>
        <?= $referrers ? $bars($referrers) : '<p class="muted">Aucun site référent : visites directes ou depuis les moteurs qui masquent leur provenance.</p>' ?>
        <h3>Appareils</h3>
        <?= $devices ? $bars($devices, $deviceLabels) : '<p class="muted">—</p>' ?>
        <h3>Langue</h3>
        <?= $langs ? $bars($langs, $langLabels) : '<p class="muted">—</p>' ?>
    </section>
</div>
