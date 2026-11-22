<?php
/** Paramètres du site : navigation latérale par rubrique, sections en cartes, onglets de langue. */
$tabMeta = [
    'general'  => ['cog', 'Nom, logo, devise, référencement'],
    'home'     => ['home', 'Bannière, textes et citation de l\'accueil'],
    'about'    => ['building', 'Présentation, histoire, mission'],
    'give'     => ['heart', 'Textes de la page « Comment donner »'],
    'contact'  => ['mail', 'Adresse, e-mail, réseaux sociaux'],
    'legal'    => ['lock', 'Confidentialité et conditions générales'],
    'payments' => ['bank', 'Moyens de paiement, montants, dons réguliers'],
];
$fields = $schema[$tab]['fields'];
$sections = [];
$current = ['title' => $schema[$tab]['label'], 'help' => '', 'fields' => []];
foreach ($fields as $name => $def) {
    if ($def['type'] === 'heading') {
        if ($current['fields'] || $current['help'] !== '') {
            $sections[] = $current;
        }
        $current = ['title' => $def['label'], 'help' => $def['help'] ?? '', 'fields' => []];
        continue;
    }
    $current['fields'][$name] = $def;
}
if ($current['fields'] || $current['help'] !== '') {
    $sections[] = $current;
}
$hasI18n = (bool) array_filter($fields, fn ($d) => !empty($d['i18n_lang']));
?>
<div class="settings-layout">
    <nav class="settings-nav" aria-label="Rubriques des paramètres">
        <?php foreach ($schema as $k => $grp): [$ic, $hint] = $tabMeta[$k] ?? ['cog', '']; ?>
            <a href="<?= admin_url('settings', ['tab' => $k]) ?>" class="<?= $tab === $k ? 'active' : '' ?>" <?= $tab === $k ? 'aria-current="page"' : '' ?>>
                <span class="sn-icon"><?= icon($ic, 18) ?></span>
                <span><strong><?= e($grp['label']) ?></strong><?php if ($hint): ?><small><?= e($hint) ?></small><?php endif; ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <form id="edit-form" method="post" enctype="multipart/form-data" action="<?= admin_url('settings', ['tab' => $tab]) ?>" class="settings-main" data-dirty-check>
        <?= csrf_field() ?>
        <?php if ($hasI18n): ?>
            <div class="lang-bar" data-lang-tabs>
                <div class="seg" role="tablist" aria-label="Langue de saisie">
                    <button type="button" role="tab" data-lang="fr" aria-selected="true">Français</button>
                    <button type="button" role="tab" data-lang="en" aria-selected="false">English <b class="todo-count" data-en-missing hidden></b></button>
                </div>
                <small class="muted">Un texte anglais laissé vide est remplacé par le français sur la version anglaise du site.</small>
            </div>
        <?php endif; ?>
        <?php foreach ($sections as $sec): ?>
            <section class="card">
                <header class="card-head"><h2><?= e($sec['title']) ?></h2><?php if ($sec['help'] !== ''): ?><p class="muted"><?= e($sec['help']) ?></p><?php endif; ?></header>
                <?php if ($sec['fields']): ?>
                    <div class="form-grid">
                        <?php foreach ($sec['fields'] as $name => $def): ?><?= admin_field($name, $def, $values[$name] ?? '') ?><?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </section>
        <?php endforeach; ?>
        <div class="settings-actions">
            <p class="dirty-note" data-dirty-note hidden><?= icon('alert', 15) ?> Modifications non enregistrées</p>
            <a class="btn btn-ghost" href="<?= url() ?>" target="_blank" rel="noopener"><?= icon('eye', 17) ?> Voir le site</a>
            <button class="btn btn-primary" type="submit"><?= icon('check', 18) ?> Enregistrer « <?= e($schema[$tab]['label']) ?> »</button>
        </div>
    </form>
</div>
