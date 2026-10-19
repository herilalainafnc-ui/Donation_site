<p><a href="<?= admin_url('messages') ?>" class="back">← Retour aux messages</a></p>

<div class="panel">
    <div class="panel-head">
        <h2><?= $msg['kind'] === 'pledge' ? 'Promesse de don' : 'Message de contact' ?></h2>
        <small class="muted">Reçu le <?= e(date('d/m/Y à H:i', strtotime($msg['created_at']))) ?></small>
    </div>
    <dl class="detail-list">
        <div><dt>Nom</dt><dd><?= e($msg['name']) ?></dd></div>
        <div><dt>E-mail</dt><dd><a href="mailto:<?= e($msg['email']) ?>"><?= e($msg['email']) ?></a></dd></div>
        <?php if ($msg['phone']): ?><div><dt>Téléphone</dt><dd><?= e($msg['phone']) ?></dd></div><?php endif; ?>
        <?php if ($msg['country']): ?><div><dt>Pays</dt><dd><?= e($msg['country']) ?></dd></div><?php endif; ?>
        <?php if ($msg['subject']): ?><div><dt>Sujet</dt><dd><?= e($msg['subject']) ?></dd></div><?php endif; ?>
        <?php if ($msg['kind'] === 'pledge'): ?>
            <div><dt>Projet</dt><dd><?= e($msg['project_title'] ?? 'Là où le besoin est le plus grand') ?></dd></div>
            <div><dt>Montant annoncé</dt><dd><?= $msg['amount'] ? e(money($msg['amount'])) : '—' ?></dd></div>
            <div><dt>Moyen de don</dt><dd><?= e($msg['method'] ?? '—') ?></dd></div>
        <?php endif; ?>
    </dl>
    <?php if ($msg['body']): ?><div class="message-body"><?= nl2br(e($msg['body'])) ?></div><?php endif; ?>

    <div class="form-actions">
        <a class="btn btn-primary" href="mailto:<?= e($msg['email']) ?>?subject=<?= rawurlencode('Re : ' . ($msg['subject'] ?: ($msg['kind'] === 'pledge' ? 'Votre don à l\'UAZ' : 'Votre message'))) ?>">Répondre par e-mail</a>
        <?php if ($msg['kind'] === 'pledge'): ?>
            <form method="post" action="<?= admin_url('messages', ['a' => 'to_donor', 'id' => $msg['id']]) ?>"><?= csrf_field() ?><button class="btn btn-light" title="Une fois le don reçu">Ajouter aux donateurs</button></form>
        <?php endif; ?>
        <form method="post" action="<?= admin_url('messages', ['a' => 'unread', 'id' => $msg['id']]) ?>"><?= csrf_field() ?><button class="btn btn-light">Marquer non lu</button></form>
        <form method="post" action="<?= admin_url('messages', ['a' => 'delete', 'id' => $msg['id']]) ?>" data-confirm="Supprimer ce message ?"><?= csrf_field() ?><button class="btn btn-danger">Supprimer</button></form>
    </div>
</div>
