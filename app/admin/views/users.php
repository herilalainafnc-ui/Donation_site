<div class="grid-2">
    <div class="panel table-panel">
        <table class="table">
            <thead><tr><th>Nom</th><th>E-mail</th><th>Dernière connexion</th><th class="actions-col"></th></tr></thead>
            <tbody>
            <?php foreach ($users as $u): ?>
                <tr>
                    <td data-label="Nom"><strong><?= e($u['name']) ?></strong><?= (int) $u['id'] === (int) $me['id'] ? ' <span class="pill pill-ok">vous</span>' : '' ?></td>
                    <td data-label="E-mail"><?= e($u['email']) ?></td>
                    <td data-label="Dernière connexion"><?= $u['last_login_at'] ? e(date('d/m/Y H:i', strtotime($u['last_login_at']))) : 'Jamais' ?></td>
                    <td class="actions-col">
                        <?php if ((int) $u['id'] !== (int) $me['id']): ?>
                            <form method="post" action="<?= admin_url('users', ['a' => 'delete', 'id' => $u['id']]) ?>" data-confirm="Supprimer le compte de <?= e($u['name']) ?> ?"><?= csrf_field() ?><button class="btn btn-danger btn-sm">Supprimer</button></form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <form method="post" action="<?= admin_url('users', ['a' => 'create']) ?>" class="panel stack">
        <h2>Nouvel administrateur</h2>
        <?= csrf_field() ?>
        <div class="field"><label for="u_name">Nom</label><input id="u_name" name="name" required></div>
        <div class="field"><label for="u_email">E-mail</label><input id="u_email" type="email" name="email" required></div>
        <div class="field"><label for="u_pass">Mot de passe provisoire (10 caractères min.)</label><input id="u_pass" type="password" name="password" minlength="10" required autocomplete="new-password"></div>
        <button class="btn btn-primary" type="submit">Créer le compte</button>
        <small class="help">Communiquez le mot de passe de manière sécurisée ; la personne pourra le changer dans « Mon compte ».</small>
    </form>
</div>
