<nav class="tabs">
    <a href="<?= admin_url('messages') ?>" class="<?= $kind === '' ? 'active' : '' ?>">Tous</a>
    <a href="<?= admin_url('messages', ['kind' => 'pledge']) ?>" class="<?= $kind === 'pledge' ? 'active' : '' ?>">Promesses de don</a>
    <a href="<?= admin_url('messages', ['kind' => 'contact']) ?>" class="<?= $kind === 'contact' ? 'active' : '' ?>">Contact</a>
</nav>

<div class="panel table-panel">
    <?php if ($rows): ?>
        <table class="table">
            <thead><tr><th>Type</th><th>Nom</th><th>E-mail</th><th>Projet / sujet</th><th>Montant</th><th>Reçu le</th><th class="actions-col">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $m): $viewUrl = admin_url('messages', ['a' => 'view', 'id' => $m['id']]); ?>
                <tr class="<?= $m['is_read'] ? '' : 'unread' ?>">
                    <td data-label="Type"><span class="pill <?= $m['kind'] === 'pledge' ? 'pill-ongoing' : '' ?>"><?= $m['kind'] === 'pledge' ? 'Don' : 'Contact' ?></span></td>
                    <td data-label="Nom"><a class="row-title" href="<?= $viewUrl ?>"><?= e($m['name']) ?></a></td>
                    <td data-label="E-mail"><?= e($m['email']) ?></td>
                    <td data-label="Projet / sujet"><?= e($m['project_title'] ?? ($m['subject'] ?? '—')) ?></td>
                    <td data-label="Montant"><?= $m['amount'] ? e(money($m['amount'])) : '—' ?></td>
                    <td data-label="Reçu le"><?= e(date('d/m/Y H:i', strtotime($m['created_at']))) ?></td>
                    <td class="actions-col">
                        <a class="btn btn-light btn-sm" href="<?= $viewUrl ?>">Ouvrir</a>
                        <form method="post" action="<?= admin_url('messages', ['a' => 'delete', 'id' => $m['id']]) ?>" data-confirm="Supprimer ce message ?"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Supprimer</button></form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div class="empty">Aucun message pour le moment.</div>
    <?php endif; ?>
</div>
<?php if ($pages > 1): ?>
    <nav class="pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $page ? 'active' : '' ?>" href="<?= admin_url('messages', array_filter(['kind' => $kind, 'page' => $i])) ?>"><?= $i ?></a><?php endfor; ?>
    </nav>
<?php endif; ?>
