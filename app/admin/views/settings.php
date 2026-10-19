<nav class="tabs">
    <?php foreach ($schema as $k => $grp): ?>
        <a href="<?= admin_url('settings', ['tab' => $k]) ?>" class="<?= $tab === $k ? 'active' : '' ?>"><?= e($grp['label']) ?></a>
    <?php endforeach; ?>
</nav>

<form method="post" enctype="multipart/form-data" action="<?= admin_url('settings', ['tab' => $tab]) ?>" class="panel form-grid" data-dirty-check>
    <?= csrf_field() ?>
    <?php foreach ($schema[$tab]['fields'] as $name => $def): ?>
        <?= admin_field($name, $def, $values[$name] ?? '') ?>
    <?php endforeach; ?>
    <div class="form-actions">
        <button class="btn btn-primary" type="submit">Enregistrer « <?= e($schema[$tab]['label']) ?> »</button>
        <a class="btn btn-light" href="<?= url() ?>" target="_blank" rel="noopener">Voir le site ↗</a>
    </div>
</form>
