<form method="post" action="<?= admin_url('account') ?>" class="panel form-grid narrow-panel">
    <?= csrf_field() ?>
    <div class="field half"><label for="a_name">Nom</label><input id="a_name" name="name" value="<?= e($me['name']) ?>" required></div>
    <div class="field half"><label for="a_email">E-mail de connexion</label><input id="a_email" type="email" name="email" value="<?= e($me['email']) ?>" required></div>
    <div class="field half"><label for="a_new">Nouveau mot de passe</label><input id="a_new" type="password" name="new_password" minlength="10" autocomplete="new-password"><small class="help">Laissez vide pour le conserver.</small></div>
    <div class="field half"><label for="a_conf">Confirmer le nouveau mot de passe</label><input id="a_conf" type="password" name="confirm_password" autocomplete="new-password"></div>
    <div class="field"><label for="a_cur">Mot de passe actuel <span class="req">*</span></label><input id="a_cur" type="password" name="current_password" required autocomplete="current-password"><small class="help">Requis pour confirmer toute modification.</small></div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Mettre à jour</button></div>
</form>
