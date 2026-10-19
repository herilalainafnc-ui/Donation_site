<p><a href="<?= admin_url($key) ?>" class="back">← Retour à la liste « <?= e($ent['label']) ?> »</a></p>

<form method="post" enctype="multipart/form-data" action="<?= admin_url($key, ['a' => 'save'] + ($row ? ['id' => $row['id']] : [])) ?>" class="panel form-grid" data-dirty-check>
    <?= csrf_field() ?>
    <?php foreach ($ent['fields'] as $name => $def): ?>
        <?= admin_field($name, $def, $values[$name] ?? null) ?>
    <?php endforeach; ?>

    <?php if (!empty($ent['gallery'])): ?>
        <fieldset class="field gallery-field">
            <legend>Galerie photos</legend>
            <?php if ($images): ?>
                <div class="gallery-admin">
                    <?php foreach ($images as $img): ?>
                        <div class="gallery-tile">
                            <img src="<?= e(media($img['path'])) ?>" alt="">
                            <input type="text" name="gallery_caption[<?= (int) $img['id'] ?>]" value="<?= e($img['caption']) ?>" placeholder="Légende (facultatif)">
                            <div class="tile-row">
                                <label>Ordre <input type="number" name="gallery_order[<?= (int) $img['id'] ?>]" value="<?= (int) $img['sort_order'] ?>"></label>
                                <label class="remove"><input type="checkbox" name="gallery_delete[]" value="<?= (int) $img['id'] ?>"> Supprimer</label>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <label for="gallery">Ajouter des photos (plusieurs fichiers possibles)</label>
            <input id="gallery" type="file" name="gallery[]" multiple accept="image/jpeg,image/png,image/webp,image/gif">
            <small class="help">Sélectionnez plusieurs images à la fois avec Ctrl (ou Cmd) + clic.</small>
        </fieldset>
    <?php endif; ?>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Enregistrer</button>
        <a class="btn btn-light" href="<?= admin_url($key) ?>">Annuler</a>
        <?php if ($row && !empty($ent['view_url'])): ?>
            <a class="btn btn-light" target="_blank" rel="noopener" href="<?= url(str_replace('{slug}', $row['slug'], $ent['view_url'])) ?>">Voir sur le site ↗</a>
        <?php endif; ?>
    </div>
</form>
