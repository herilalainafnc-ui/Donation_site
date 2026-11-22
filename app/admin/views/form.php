<?php
/**
 * Formulaire d'ajout / modification.
 * Colonne principale : une carte par section (intertitres « heading » de l'entité).
 * Panneau latéral : publication et affichage (interrupteurs, statut, ordre) + actions.
 * Champs traduisibles : onglets Français / English.
 */
$asideNames = $ent['aside'] ?? null;
$isAside = fn (string $name, array $def) => $asideNames !== null
    ? in_array($name, $asideNames, true)
    : ($def['type'] === 'checkbox' || in_array($name, ['status', 'sort_order'], true));
$sections = [];
$current = ['title' => '', 'help' => '', 'fields' => []];
$aside = [];
foreach ($ent['fields'] as $name => $def) {
    if ($def['type'] === 'heading') {
        if ($current['fields']) {
            $sections[] = $current;
        }
        $current = ['title' => $def['label'], 'help' => $def['help'] ?? '', 'fields' => []];
        continue;
    }
    if ($isAside($name, $def)) {
        $aside[$name] = $def;
    } else {
        $current['fields'][$name] = $def;
    }
}
if ($current['fields']) {
    $sections[] = $current;
}
if ($sections && $sections[0]['title'] === '') {
    $sections[0]['title'] = 'Informations';
}
$hasI18n = (bool) array_filter($ent['fields'], fn ($d) => !empty($d['i18n_lang']));
$viewUrl = $row && !empty($ent['view_url']) ? url(str_replace(['{slug}', '{id}'], [$row['slug'] ?? '', $row['id']], $ent['view_url'])) : null;
$formAction = admin_url($key, ['a' => 'save'] + ($row ? ['id' => $row['id']] : []));
?>
<form id="edit-form" method="post" enctype="multipart/form-data" action="<?= $formAction ?>" class="edit-layout" data-dirty-check>
    <?= csrf_field() ?>
    <div class="edit-main">
        <?php if ($hasI18n): ?>
            <div class="lang-bar" data-lang-tabs>
                <div class="seg" role="tablist" aria-label="Langue de saisie">
                    <button type="button" role="tab" data-lang="fr" aria-selected="true">Français</button>
                    <button type="button" role="tab" data-lang="en" aria-selected="false">English <b class="todo-count" data-en-missing hidden></b></button>
                </div>
                <small class="muted">Le site s'affiche en anglais par défaut ; un texte anglais laissé vide est remplacé par le français.</small>
            </div>
        <?php endif; ?>

        <?php foreach ($sections as $sec): ?>
            <section class="card">
                <header class="card-head"><h2><?= e($sec['title']) ?></h2><?php if ($sec['help'] !== ''): ?><p class="muted"><?= e($sec['help']) ?></p><?php endif; ?></header>
                <div class="form-grid">
                    <?php foreach ($sec['fields'] as $name => $def): ?><?= admin_field($name, $def, $values[$name] ?? null) ?><?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>

        <?php if ($gallery): ?>
            <section class="card">
                <header class="card-head"><h2><?= icon('image', 18) ?> <?= e($gallery['legend']) ?></h2><?php if ($gallery['help']): ?><p class="muted"><?= e($gallery['help']) ?></p><?php endif; ?></header>
                <?php if ($images): ?>
                    <div class="gallery-admin">
                        <?php foreach ($images as $img): $iid = (int) $img['id']; $noCaption = $gallery['caption_required'] && trim((string) $img['caption']) === ''; ?>
                            <div class="gallery-tile<?= $noCaption ? ' needs-caption' : '' ?>">
                                <img src="<?= e(media($img['path'])) ?>" alt="">
                                <input type="text" name="gallery_caption[<?= $iid ?>]" value="<?= e($img['caption']) ?>" maxlength="255"
                                    placeholder="<?= $gallery['caption_required'] ? 'Légende (obligatoire)' : 'Légende (facultatif)' ?>"<?= $gallery['caption_required'] ? ' required' : '' ?>>
                                <?php if ($gallery['caption_en']): ?>
                                    <input type="text" name="gallery_caption_en[<?= $iid ?>]" value="<?= e($img['caption_en'] ?? '') ?>" maxlength="255" placeholder="Caption — English (optional)" lang="en">
                                <?php endif; ?>
                                <div class="tile-row">
                                    <label>Ordre <input type="number" name="gallery_order[<?= $iid ?>]" value="<?= (int) $img['sort_order'] ?>"></label>
                                    <label class="remove"><input type="checkbox" name="gallery_delete[]" value="<?= $iid ?>"> Supprimer</label>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <label class="dropzone" for="gallery">
                    <?= icon('image', 26) ?>
                    <strong>Ajouter des photos</strong>
                    <small>Cliquez ou glissez vos images ici — plusieurs à la fois<?= $gallery['caption_required'] ? ' ; ajoutez ensuite une légende à chacune puis enregistrez' : '' ?>.</small>
                    <input id="gallery" type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif" data-dropzone>
                    <span class="dropzone-files" data-dropzone-files></span>
                </label>
            </section>
        <?php endif; ?>

        <?php if ($key === 'projects' && $row): ?>
            <section class="card">
                <header class="card-head card-head-row">
                    <div><h2><?= icon('calendar', 18) ?> Avancement des travaux</h2><p class="muted">Une étape à chaque avancée du chantier, avec ses photos légendées.</p></div>
                    <a class="btn btn-light btn-sm" href="<?= admin_url('updates', ['a' => 'new', 'project_id' => $row['id']]) ?>"><?= icon('plus', 16) ?> Ajouter une étape</a>
                </header>
                <?php if ($updates): ?>
                    <ul class="updates-list">
                        <?php foreach ($updates as $u): ?>
                            <li>
                                <a href="<?= admin_url('updates', ['a' => 'edit', 'id' => $u['id']]) ?>"><strong><?= e($u['title']) ?></strong></a>
                                <small><?= e(format_date($u['update_date'])) ?> · <?= (int) $u['photos'] ?> photo(s)<?= (int) $u['progress'] > 0 ? ' · ' . (int) $u['progress'] . ' %' : '' ?><?= $u['is_published'] ? '' : ' · <span class="pill">non publiée</span>' ?></small>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="muted">Aucune étape pour l'instant. Elles s'affichent dans la section « Avancement des travaux » de la page du projet et dans la fiche de suivi.</p>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </div>

    <aside class="edit-aside">
        <div class="card aside-card">
            <h2 class="aside-title">Publication</h2>
            <?php foreach ($aside as $name => $def): ?><?= admin_field($name, ['half' => false] + $def, $values[$name] ?? null) ?><?php endforeach; ?>
            <div class="aside-actions">
                <button class="btn btn-primary btn-block" type="submit"><?= icon('check', 18) ?> Enregistrer</button>
                <?php if ($viewUrl): ?><a class="btn btn-light btn-block" href="<?= e($viewUrl) ?>" target="_blank" rel="noopener"><?= icon('eye', 17) ?> Voir sur le site</a><?php endif; ?>
                <a class="btn btn-ghost btn-block" href="<?= admin_url($key) ?>">Annuler</a>
            </div>
            <p class="dirty-note" data-dirty-note hidden><?= icon('alert', 15) ?> Modifications non enregistrées</p>
            <?php if ($row): ?>
                <dl class="aside-meta">
                    <?php if (!empty($row['created_at'])): ?><div><dt>Créé le</dt><dd><?= e(date('d/m/Y', strtotime($row['created_at']))) ?></dd></div><?php endif; ?>
                    <?php if (!empty($row['updated_at'])): ?><div><dt>Modifié le</dt><dd><?= e(date('d/m/Y H:i', strtotime($row['updated_at']))) ?></dd></div><?php endif; ?>
                </dl>
                <button class="btn-link danger" type="submit" formaction="<?= admin_url($key, ['a' => 'delete', 'id' => $row['id']]) ?>" formnovalidate
                        data-confirm-click="Supprimer définitivement cet élément ? Cette action est irréversible."><?= icon('trash', 15) ?> Supprimer <?= e($ent['singular']) ?></button>
            <?php endif; ?>
        </div>
    </aside>

    <div class="savebar">
        <a class="btn btn-ghost" href="<?= admin_url($key) ?>">Annuler</a>
        <button class="btn btn-primary" type="submit"><?= icon('check', 18) ?> Enregistrer</button>
    </div>
</form>
