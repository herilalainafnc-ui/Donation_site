<?php
/** Liste d'une entité : recherche, filtres en pastilles, tableau avec actions par ligne. */
$titleCol = $ent['title_column'] ?? null;
foreach ($titleCol ? [] : $ent['list'] as $col => $_) {
    if ($col !== 'icon' && ($ent['fields'][$col]['type'] ?? '') !== 'image') {
        $titleCol = $col;
        break;
    }
}
$filterOpts = !empty($ent['filter']) ? $ent['filter'][1] : [];
$useChips = $filterOpts && count($filterOpts) <= 6;
$hasQuery = $search !== '' || $filter !== '';
$newUrl = admin_url($key, ['a' => 'new'] + ($filter !== '' && !empty($ent['filter']) && $ent['filter'][0] === 'project_id' ? ['project_id' => $filter] : []));
?>
<div class="list-toolbar">
    <form method="get" action="<?= url('admin/') ?>" class="list-search" role="search">
        <input type="hidden" name="p" value="<?= e($key) ?>">
        <?php if ($filter !== ''): ?><input type="hidden" name="f" value="<?= e($filter) ?>"><?php endif; ?>
        <?php if (!empty($ent['search'])): ?>
            <label class="search-box"><?= icon('search', 17) ?><span class="sr-only">Rechercher</span>
                <input type="search" name="q" value="<?= e($search) ?>" placeholder="Rechercher dans <?= e(mb_strtolower($ent['label'])) ?>…">
            </label>
        <?php endif; ?>
        <?php if ($filterOpts && !$useChips): ?>
            <select name="f" onchange="this.form.submit()" aria-label="Filtrer">
                <option value="">Tous</option>
                <?php foreach ($filterOpts as $k => $lbl): ?><option value="<?= e($k) ?>" <?= $filter === (string) $k ? 'selected' : '' ?>><?= e($lbl) ?></option><?php endforeach; ?>
            </select>
        <?php endif; ?>
        <?php if ($hasQuery): ?><a class="btn btn-ghost btn-sm" href="<?= admin_url($key) ?>">Effacer</a><?php endif; ?>
    </form>
    <span class="list-count"><?= $total ?> élément<?= $total > 1 ? 's' : '' ?></span>
</div>

<?php if ($useChips): ?>
    <nav class="chips" aria-label="Filtrer">
        <a href="<?= admin_url($key, array_filter(['q' => $search])) ?>" class="chip <?= $filter === '' ? 'active' : '' ?>">Tous</a>
        <?php foreach ($filterOpts as $k => $lbl): ?>
            <a href="<?= admin_url($key, array_filter(['q' => $search, 'f' => (string) $k])) ?>" class="chip <?= $filter === (string) $k ? 'active' : '' ?>"><?= e($lbl) ?></a>
        <?php endforeach; ?>
    </nav>
<?php endif; ?>

<div class="panel table-panel">
    <?php if ($rows): ?>
        <table class="table">
            <thead><tr>
                <?php foreach ($ent['list'] as $col => $lbl): ?><th class="<?= ($ent['fields'][$col]['type'] ?? '') === 'image' ? 'col-thumb' : '' ?>"><?= e($lbl) ?></th><?php endforeach; ?>
                <th class="actions-col"><span class="sr-only">Actions</span></th>
            </tr></thead>
            <tbody>
            <?php foreach ($rows as $r): $editUrl = admin_url($key, ['a' => 'edit', 'id' => $r['id']]); ?>
                <tr data-href="<?= e($editUrl) ?>">
                    <?php foreach ($ent['list'] as $col => $lbl): ?>
                        <td data-label="<?= e($lbl) ?>" class="<?= ($ent['fields'][$col]['type'] ?? '') === 'image' ? 'col-thumb' : ($col === $titleCol ? 'col-title' : 'col-nowrap') ?>">
                            <?php if ($col === $titleCol): ?>
                                <a class="row-title" href="<?= $editUrl ?>"><?= admin_cell($col, $ent, $r, $options) ?></a>
                                <?php if (!empty($ent['list_sub']) && !empty($r[$ent['list_sub']])): ?><small class="row-sub"><?= e((string) $r[$ent['list_sub']]) ?></small><?php endif; ?>
                            <?php else: ?>
                                <?= admin_cell($col, $ent, $r, $options) ?>
                            <?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                    <td class="actions-col">
                        <div class="row-actions">
                            <a class="icon-btn" href="<?= $editUrl ?>" title="Modifier" aria-label="Modifier"><?= icon('edit', 17) ?></a>
                            <?php if (!empty($ent['view_url'])): ?><a class="icon-btn" target="_blank" rel="noopener" href="<?= url(str_replace(['{slug}', '{id}'], [$r['slug'] ?? '', $r['id']], $ent['view_url'])) ?>" title="Voir sur le site" aria-label="Voir sur le site"><?= icon('eye', 17) ?></a><?php endif; ?>
                            <form method="post" action="<?= admin_url($key, ['a' => 'delete', 'id' => $r['id']]) ?>" data-confirm="Supprimer définitivement cet élément ? Cette action est irréversible.">
                                <?= csrf_field() ?><button class="icon-btn danger" type="submit" title="Supprimer" aria-label="Supprimer"><?= icon('trash', 17) ?></button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty-state">
            <span class="empty-icon"><?= icon($hasQuery ? 'search' : ($ent['icon'] ?? 'plus'), 28) ?></span>
            <h2><?= $hasQuery ? 'Aucun résultat' : 'Rien pour le moment' ?></h2>
            <p class="muted"><?= $hasQuery ? 'Aucun élément ne correspond à votre recherche ou à ce filtre.' : 'Commencez par ajouter ' . e($ent['singular']) . '.' ?></p>
            <?= $hasQuery ? '<a class="btn btn-light" href="' . admin_url($key) . '">Effacer la recherche</a>' : '<a class="btn btn-primary" href="' . e($newUrl) . '">' . icon('plus', 18) . ' Ajouter ' . e($ent['singular']) . '</a>' ?>
        </div>
    <?php endif; ?>
</div>

<?php if ($pages > 1): ?>
    <nav class="pagination" aria-label="Pages">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a class="<?= $i === $page ? 'active' : '' ?>" href="<?= admin_url($key, array_filter(['q' => $search, 'f' => $filter, 'page' => $i])) ?>"><?= $i ?></a>
        <?php endfor; ?>
    </nav>
<?php endif; ?>
