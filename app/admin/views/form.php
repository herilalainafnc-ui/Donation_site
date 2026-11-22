<p><a href="<?= admin_url($key) ?>" class="back">← Retour à la liste « <?= e($ent['label']) ?> »</a></p>

<form method="post" enctype="multipart/form-data" action="<?= admin_url($key, ['a' => 'save'] + ($row ? ['id' => $row['id']] : [])) ?>" class="panel form-grid" data-dirty-check>
    <?= csrf_field() ?>
    <?php foreach ($ent['fields'] as $name => $def): ?>
        <?= admin_field($name, $def, $values[$name] ?? null) ?>
    <?php endforeach; ?>

    <?php if ($gallery): ?>
        <fieldset class="field gallery-field">
            <legend><?= e($gallery['legend']) ?></legend>
            <?php if ($gallery['help']): ?><small class="help"><?= e($gallery['help']) ?></small><?php endif; ?>
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
            <label for="gallery">Ajouter des photos (plusieurs fichiers possibles)</label>
            <input id="gallery" type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="help">Sélectionnez plusieurs images à la fois avec Ctrl (ou Cmd) + clic.<?= $gallery['caption_required'] ? ' Après l\'envoi, saisissez la légende de chaque nouvelle photo puis enregistrez à nouveau.' : '' ?></small>
        </fieldset>
    <?php endif; ?>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-light" href="<?= admin_url($key) ?>">Annuler</a>
        <?php if ($row && !empty($ent['view_url'])): ?>
            <a class="btn btn-light" target="_blank" rel="noopener" href="<?= url(str_replace(['{slug}', '{id}'], [$row['slug'] ?? '', $row['id']], $ent['view_url'])) ?>">Voir sur le site ↗</a>
        <?php endif; ?>
    </div>
</form>

<?php if ($key === 'projects' && $row): ?>
    <section class="panel">
        <div class="panel-head">
            <h2>Avancement des travaux</h2>
            <a class="btn btn-primary" href="<?= admin_url('updates', ['a' => 'new', 'project_id' => $row['id']]) ?>">+ Ajouter une étape</a>
        </div>
        <?php if ($updates): ?>
            <ul class="mini-list updates-list">
                <?php foreach ($updates as $u): ?>
                    <li>
                        <a href="<?= admin_url('updates', ['a' => 'edit', 'id' => $u['id']]) ?>"><?= e($u['title']) ?></a>
                        <small><?= e(format_date($u['update_date'])) ?> · <?= (int) $u['photos'] ?> photo(s)<?= (int) $u['progress'] > 0 ? ' · ' . (int) $u['progress'] . ' %' : '' ?><?= $u['is_published'] ? '' : ' · non publiée' ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Aucune étape pour l'instant. Ajoutez une étape à chaque avancée du chantier (fondations, murs, toiture…) avec ses photos légendées : elles s'affichent dans la section « Avancement des travaux » de la page du projet.</p>
        <?php endif; ?>
    </section>
<?php endif; ?>
