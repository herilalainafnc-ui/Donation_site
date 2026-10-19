<div class="toolbar">
    <form method="get" action="<?= url('admin/') ?>" class="search">
        <input type="hidden" name="p" value="<?= e($key) ?>">
        <?php if (!empty($ent['search'])): ?><input type="search" name="q" value="<?= e($search) ?>" placeholder="Rechercher…"><?php endif; ?>
        <?php if (!empty($ent['filter'])): ?>
            <select name="f" onchange="this.form.submit()">
                <option value="">Tous</option>
                <?php foreach ($ent['filter'][1] as $k => $lbl): ?><option value="<?= e($k) ?>" <?= $filter === $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
        <?php if (!empty($ent['search'])): ?><button class="btn btn-light" type="submit">Filtrer</button><?php endif; ?>
    </form>
    <a class="btn btn-primary" href="<?= admin_url($key, ['a' => 'new']) ?>">+ Ajouter <?= e($ent['singular']) ?></a>
</div>

<div class="panel table-panel">
    <?php
    $titleCol = null;
    foreach ($ent['list'] as $col => $_) {
        if ($col !== 'icon' && ($ent['fields'][$col]['type'] ?? '') !== 'image') {
            $titleCol = $col;
            break;
        }
    }
    if ($rows): ?>
        <table class="table">
            <thead><tr>
                <?php foreach ($ent['list'] as $col => $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?>
                <th class="actions-col">Actions</th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $editUrl = admin_url($key, ['a' => 'edit', 'id' => $r['id']]); ?>
                <tr>
                    <?php foreach ($ent['list'] as $col => $lbl): ?>
                        <td data-label="<?= e($lbl) ?>">
                            <?php if ($col === $titleCol): ?>
                                <a class="row-title" href="<?= $editUrl ?>"><?= admin_cell($col, $ent, $r, $options) ?></a>
                            <?php else: ?>
                                <?= admin_cell($col, $ent, $r, $options) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="actions-col">
                        <a class="btn btn-light btn-sm" href="<?= $editUrl ?>">Modifier</a>
                        <?php if (!empty($ent['view_url'])): ?><a class="btn btn-light btn-sm" target="_blank" rel="noopener" href="<?= url(str_replace('{slug}', $r['slug'], $ent['view_url'])) ?>">Voir</a><?php endif; ?>
                        <form method="post" action="<?= admin_url($key, ['a' => 'delete', 'id' => $r['id']]) ?>" data-confirm="Supprimer définitivement cet élément ?">
                            <?= csrf_field() ?><button class="btn btn-danger btn-sm" type="submit">Supprimer</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">Aucun élément<?= $search || $filter ? ' ne correspond à votre recherche' : ' pour le moment' ?>. <a href="<?= admin_url($key, ['a' => 'new']) ?>">Ajouter <?= e($ent['singular']) ?></a></div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= admin_url($key, array_filter(['q' => $search, 'f' => $filter, 'page' => $i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
<p class="muted small"><?= $total ?> élément(s)</p>
