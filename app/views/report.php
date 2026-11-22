<?php
/** Fiche de suivi d'un projet — page A4 autonome, téléchargeable en PDF. */
$siteName = setting_t('site_name', 'Université Adventiste Zurcher');
$title = loc($p, 'title');
$isDone = $p['status'] === 'completed';
$cur = project_currency($p);
$updates ??= [];
// Dernière étape indiquant un pourcentage de travaux (les étapes sont triées de la plus récente à la plus ancienne)
$lastWorks = null;
foreach ($updates as $u) {
    if ((int) $u['progress'] > 0) {
        $lastWorks = $u;
        break;
    }
}
const REPORT_PHOTOS_PER_STEP = 4;
$fileName = 'fiche-de-suivi-' . slugify((string) ($p['code'] ?: $p['slug']));
$projectUrl = rtrim((string) config('site_url', ''), '/') . route_url('projects', ['slug' => $p['slug']]);
?><!doctype html>
<html lang="<?= lang() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= e(__('Fiche de suivi')) ?> — <?= e($p['code'] ?: $title) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset("css/fonts.css") ?>">
    <link rel="stylesheet" href="<?= asset('css/report.css') ?>">
</head>
<body>
<div class="toolbar">
    <a href="<?= route_url('projects', ['slug' => $p['slug']]) ?>" class="tb-link">← <?= e(__('Retour au projet')) ?></a>
    <span class="tb-status" id="status" role="status"></span>
    <div class="tb-actions">
        <button type="button" class="tb-btn" onclick="window.print()"><?= icon('print', 18) ?> <?= e(__('Imprimer')) ?></button>
        <button type="button" class="tb-btn tb-primary" id="download"><?= icon('download', 18) ?> <?= e(__('Télécharger en PDF')) ?></button>
    </div>
</div>

<main class="sheet" id="sheet">
    <header class="sheet-head">
        <img src="<?= e(site_logo()) ?>" alt="<?= e(__('Sceau de l\'Université Adventiste Zurcher')) ?>" class="sheet-logo">
        <div>
            <p class="sheet-org"><?= e($siteName) ?></p>
            <h1><?= e(__('Fiche de suivi de projet')) ?></h1>
            <p class="sheet-date"><?= e(__('Établie le :date', ['date' => format_date(date('Y-m-d'))])) ?></p>
        </div>
        <?php if ($p['code']): ?><div class="sheet-code"><small><?= e(__('Code projet')) ?></small><strong><?= e($p['code']) ?></strong></div><?php endif; ?>
    </header>

    <section class="sheet-title">
        <span class="status status-<?= e($p['status']) ?>"><?= e(status_label($p['status'])) ?></span>
        <h2><?= e($title) ?></h2>
        <?php if (loc($p, 'summary') !== ''): ?><p class="summary"><?= e(loc($p, 'summary')) ?></p><?php endif; ?>
    </section>

    <section class="grid-2">
        <div class="box">
            <h3><?= e(__('Informations générales')) ?></h3>
            <table class="kv">
                <?php if ($p['code']): ?><tr><th><?= e(__('Code projet')) ?></th><td><?= e($p['code']) ?></td></tr><?php endif; ?>
                <tr><th><?= e(__('Statut')) ?></th><td><?= e(status_label($p['status'])) ?></td></tr>
                <?php if (loc($p, 'location') !== ''): ?><tr><th><?= e(__('Lieu')) ?></th><td><?= e(loc($p, 'location')) ?></td></tr><?php endif; ?>
                <?php if ($p['start_date']): ?><tr><th><?= e(__('Début')) ?></th><td><?= e(format_date($p['start_date'])) ?></td></tr><?php endif; ?>
                <?php if ($p['end_date']): ?><tr><th><?= e($isDone ? __('Achèvement') : __('Fin prévue')) ?></th><td><?= e(format_date($p['end_date'])) ?></td></tr><?php endif; ?>
                <tr><th><?= e(__('Donateurs')) ?></th><td><?= (int) $dash['donors'] ?></td></tr>
                <?php if ($lastWorks): ?><tr><th><?= e(__('Avancement des travaux')) ?></th><td class="strong"><?= min(100, (int) $lastWorks['progress']) ?> % <small class="muted">(<?= e(format_date($lastWorks['update_date'])) ?>)</small></td></tr><?php endif; ?>
            </table>
        </div>
        <div class="box box-funding">
            <h3><?= e(__('Financement')) ?></h3>
            <table class="kv">
                <tr><th><?= e(__('Objectif')) ?></th><td><?= $dash['goal'] > 0 ? e(money($dash['goal'], $cur)) : '—' ?></td></tr>
                <?php if ($dash['uaz'] > 0): ?><tr><th><?= e(__('Budget engagé par l\'UAZ')) ?></th><td><?= e(money($dash['uaz'], $cur)) ?></td></tr><?php endif; ?>
                <tr><th><?= e(__('Montant collecté (dons)')) ?></th><td class="strong"><?= e(money($dash['raised'], $cur)) ?></td></tr>
                <?php if ($dash['uaz'] > 0): ?><tr><th><?= e(__('Total financé')) ?></th><td class="strong"><?= e(money($dash['funded'], $cur)) ?></td></tr><?php endif; ?>
                <tr><th><?= e(__('Reste à financer')) ?></th><td><?= $dash['goal'] > 0 ? ($dash['missing'] > 0 ? e(money($dash['missing'], $cur)) : e(__('Objectif atteint'))) : '—' ?></td></tr>
                <tr><th><?= e(__('Taux de financement')) ?></th><td class="strong"><?= $dash['goal'] > 0 ? $dash['pct'] . ' %' : '—' ?></td></tr>
            </table>
            <?php if ($dash['goal'] > 0): ?>
                <div class="bar"><span style="width: <?= $dash['pct'] ?>%"></span></div>
            <?php endif; ?>
            <?php if ($dash['time_pct'] !== null): ?>
                <p class="note"><?= e(__('Calendrier écoulé :')) ?> <?= $dash['time_pct'] ?> %<?= !$isDone && $dash['days_left'] ? ' — ' . e(__(':n jours restants', ['n' => $dash['days_left']])) : '' ?></p>
            <?php endif; ?>
        </div>
    </section>

    <?php if (loc($p, 'description') !== ''): ?>
        <section class="box">
            <h3><?= e(__('Détails du projet')) ?></h3>
            <div class="prose"><?= rich_text(loc($p, 'description')) ?></div>
        </section>
    <?php endif; ?>

    <?php if ($updates): ?>
        <section class="box box-progress">
            <h3><?= e(__('Avancement des travaux')) ?></h3>
            <ol class="pl">
                <?php foreach ($updates as $u): $uPct = max(0, min(100, (int) $u['progress'])); $shown = array_slice($u['photos'], 0, REPORT_PHOTOS_PER_STEP); $more = count($u['photos']) - count($shown); ?>
                    <li class="pl-step">
                        <div class="pl-head">
                            <span class="pl-date"><?= e(format_date($u['update_date'])) ?></span>
                            <strong class="pl-title"><?= e(loc($u, 'title')) ?></strong>
                            <?php if ($uPct > 0): ?><span class="pl-pct"><?= e(__('Travaux : :pct %', ['pct' => $uPct])) ?></span><?php endif; ?>
                        </div>
                        <?php if ($uPct > 0): ?><div class="pl-bar"><span style="width: <?= $uPct ?>%"></span></div><?php endif; ?>
                        <?php if (loc($u, 'body') !== ''): ?><div class="prose pl-body"><?= rich_text(loc($u, 'body')) ?></div><?php endif; ?>
                        <?php if ($shown): ?>
                            <div class="pl-photos">
                                <?php foreach ($shown as $ph): $capt = loc($ph, 'caption') ?: loc($u, 'title'); ?>
                                    <figure><img src="<?= e(media($ph['path'])) ?>" alt="<?= e($capt) ?>"><figcaption><?= e($capt) ?></figcaption></figure>
                                <?php endforeach; ?>
                            </div>
                            <?php if ($more > 0): ?><p class="note"><?= e(__('+ :n autre(s) photo(s) sur la page du projet.', ['n' => $more])) ?></p><?php endif; ?>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ol>
        </section>
    <?php endif; ?>

    <section class="box">
        <h3><?= e(__('Les donateurs de ce projet')) ?></h3>
        <?php if ($donors): ?>
            <table class="list">
                <thead><tr><th><?= e(__('Donateur')) ?></th><th><?= e(__('Type')) ?></th><th><?= e(__('Date')) ?></th><th class="num"><?= e(__('Montant')) ?></th></tr></thead>
                <tbody>
                    <?php foreach ($donors as $d): ?>
                        <tr>
                            <td><?= e(donor_name($d)) ?></td>
                            <td><?= e(donor_type_label($d['donor_type'])) ?></td>
                            <td><?= $d['donated_on'] ? e(format_date($d['donated_on'])) : '—' ?></td>
                            <td class="num"><?= $d['show_amount'] && (float) $d['amount'] > 0 ? e(money($d['amount'], $cur)) : e(__('Non communiqué')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="note"><?= e(__('Aucun donateur n\'est encore affiché pour ce projet.')) ?></p>
        <?php endif; ?>
    </section>

    <footer class="sheet-foot">
        <span><?= e($siteName) ?><?= setting('contact_email') ? ' · ' . e(setting('contact_email')) : '' ?></span>
        <span><?= e($projectUrl) ?></span>
    </footer>
</main>

<script src="<?= asset("vendor/html2pdf.bundle.min.js") ?>"></script>
<script>
(function () {
    var btn = document.getElementById('download');
    var status = document.getElementById('status');
    function download() {
        if (!window.html2pdf) { window.print(); return; }
        btn.disabled = true;
        status.textContent = <?= json_encode(__('Préparation du PDF…')) ?>;
        html2pdf().set({
            margin: 0,
            filename: <?= json_encode($fileName . '.pdf') ?>,
            image: { type: 'jpeg', quality: 0.96 },
            html2canvas: { scale: 2, useCORS: true },
            jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' },
            pagebreak: { mode: ['css', 'legacy'], avoid: ['tr', '.box h3', '.bar', '.pl-step'] }
        }).from(document.getElementById('sheet')).save().then(function () {
            status.textContent = <?= json_encode(__('Fiche téléchargée.')) ?>;
            btn.disabled = false;
        });
    }
    btn.addEventListener('click', download);
    <?php if ($autoDownload): ?>window.addEventListener('load', function () { setTimeout(download, 400); });<?php endif; ?>
})();
</script>
</body>
</html>
