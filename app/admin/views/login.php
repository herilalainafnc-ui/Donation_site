<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>Connexion — Admin <?= e(setting('site_short', 'UAZ')) ?></title>
    <link rel="icon" type="image/png" href="<?= asset('img/favicon.png') ?>">
    <link rel="stylesheet" href="<?= asset("css/fonts.css") ?>">
    <link rel="stylesheet" href="<?= asset('css/admin.css') ?>">
</head>
<body class="login-page">
<main class="login-card">
    <div class="login-head">
        <img class="brand-mark" src="<?= e(site_logo()) ?>" alt="">
        <h1>Espace administration</h1>
        <p><?= e(setting('site_name')) ?></p>
    </div>
    <?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= admin_url('login') ?>" class="stack">
        <?= csrf_field() ?>
        <div class="field"><label for="email">E-mail</label><input id="email" type="email" name="email" required autofocus autocomplete="username" value="<?= e($_POST['email'] ?? '') ?>"></div>
        <div class="field"><label for="password">Mot de passe</label><input id="password" type="password" name="password" required autocomplete="current-password"></div>
        <button class="btn btn-primary btn-block" type="submit">Se connecter</button>
    </form>
    <a class="back" href="<?= url() ?>">← Retour au site</a>
</main>
</body>
</html>
