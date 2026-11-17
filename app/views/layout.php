<?php
$siteName = setting_t('site_name', 'Université Adventiste Zurcher');
$short = setting('site_short', 'UAZ');
// Titre court (≈ 60 caractères max.) et description (≈ 155 caractères) pour Google et les réseaux sociaux
$title = isset($pageTitle) ? $pageTitle . ' — ' . $short : $siteName . ' — ' . __('Campagne de dons');
$desc = trim((string) ($metaDescription ?? '')) ?: setting_t('meta_description')
    ?: __('Soutenez les projets de construction et d\'équipement de l\'Université Adventiste Zurcher à Madagascar : chaque don construit l\'avenir d\'un étudiant.');
$desc = excerpt($desc, 160);
$og = !empty($ogImage) ? absolute_url($ogImage) : (setting('og_image') ? absolute_url(setting('og_image')) : absolute_url('assets/img/og-image.jpg'));
$siteUrl = rtrim((string) config('site_url', ''), '/');
$canonical = $siteUrl . current_url_in(lang());
$gaId = preg_match('/^G-[A-Z0-9]{4,15}$/', setting('ga_measurement_id')) ? setting('ga_measurement_id') : '';
$nav ??= '';
$menu = [
    'home' => __('Accueil'),
    'about' => __('À propos'),
    'projects' => __('Nos projets'),
    'give' => __('Comment donner'),
    'testimonials' => __('Témoignages'),
    'donors' => __('Donateurs'),
    'contact' => __('Contact'),
];
$flashes = take_flashes();
$langSwitch = function (string $class) {
    $html = '<div class="' . $class . '" role="group" aria-label="' . e(__('Langue')) . '">';
    foreach (LANGS as $code => $name) {
        $html .= '<a href="' . e(current_url_in($code)) . '" hreflang="' . $code . '" lang="' . $code . '" title="' . e($name) . '"'
            . ($code === lang() ? ' class="is-active" aria-current="true"' : '') . '>' . strtoupper($code) . '</a>';
    }
    return $html . '</div>';
};
?><!doctype html>
<html lang="<?= lang() ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?></title>
    <meta name="description" content="<?= e($desc) ?>">
    <link rel="canonical" href="<?= e($canonical) ?>">
    <?php if (!empty($noindex)): ?><meta name="robots" content="noindex"><?php endif; ?>
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="<?= e($siteName) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta property="og:title" content="<?= e($title) ?>">
    <meta property="og:description" content="<?= e($desc) ?>">
    <meta property="og:image" content="<?= e($og) ?>">
    <meta property="og:image:alt" content="<?= e($siteName) ?>">
    <meta property="og:locale" content="<?= lang() === 'fr' ? 'fr_FR' : 'en_US' ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= e($title) ?>">
    <meta name="twitter:description" content="<?= e($desc) ?>">
    <meta name="twitter:image" content="<?= e($og) ?>">
    <?php foreach (LANGS as $code => $_): ?>
        <link rel="alternate" hreflang="<?= $code ?>" href="<?= e(rtrim((string) config('site_url', ''), '/') . current_url_in($code)) ?>">
    <?php endforeach; ?>
    <link rel="alternate" hreflang="x-default" href="<?= e(rtrim((string) config('site_url', ''), '/') . current_url_in(DEFAULT_LANG)) ?>">
    <meta name="theme-color" content="#0b1834">
    <link rel="icon" href="<?= url('favicon.ico') ?>" sizes="48x48">
    <link rel="icon" type="image/png" sizes="32x32" href="<?= asset('img/favicon-32.png') ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?= asset('img/icon-192.png') ?>">
    <link rel="apple-touch-icon" href="<?= asset('img/apple-touch-icon.png') ?>">
    <link rel="manifest" href="<?= url('site.webmanifest') ?>">
    <link rel="preload" href="<?= url('assets/fonts/raleway-normal-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="<?= url('assets/fonts/roboto-slab-normal-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="<?= asset('css/fonts.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
    <script>
        // Animations activées seulement si le visiteur ne demande pas de réduire les mouvements.
        // Filet de sécurité : si les scripts ne se chargent pas, le contenu redevient visible.
        (function (d) {
            var r = d.documentElement;
            r.classList.add('js');
            if (!matchMedia('(prefers-reduced-motion: reduce)').matches) {
                r.classList.add('anim');
                setTimeout(function () { if (!r.classList.contains('anim-ready')) r.classList.remove('anim'); }, 3500);
            }
            try { if (sessionStorage.getItem('uaz-intro')) r.classList.add('no-intro'); } catch (e) {}
        })(document);
    </script>
</head>
<body>
<a class="skip-link" href="#contenu"><?= e(__('Aller au contenu')) ?></a>

<div class="intro" aria-hidden="true">
    <div class="intro-inner">
        <img src="<?= e(site_logo()) ?>" alt="" width="120" height="123">
        <span class="intro-bar"><span></span></span>
    </div>
</div>
<div class="curtain" aria-hidden="true"></div>
<div class="scroll-progress" aria-hidden="true"></div>

<header class="site-header on-dark" id="top">
    <div class="header-inner">
        <a class="brand" href="<?= route_url('home') ?>" aria-label="<?= e($siteName) ?> — <?= e(__('accueil')) ?>">
            <img src="<?= e(site_logo()) ?>" alt="<?= e(__('Sceau de l\'Université Adventiste Zurcher')) ?>" class="brand-logo" width="48" height="49">
            <span class="brand-text">
                <strong><?= e($siteName) ?></strong>
                <small><?= e(__('Campagne de dons')) ?></small>
            </span>
        </a>
        <nav class="main-nav" id="main-nav" aria-label="<?= e(__('Navigation principale')) ?>">
            <ul>
                <?php foreach (['home', 'projects', 'give', 'testimonials', 'donors'] as $key): $label = $menu[$key]; ?>
                    <li><a href="<?= route_url($key) ?>" <?= $nav === $key ? 'aria-current="page"' : '' ?>><span class="roll"><span data-hover="<?= e($label) ?>"><?= e($label) ?></span></span></a></li>
                <?php endforeach; ?>
                <?php $aboutOpen = in_array($nav, ['about', 'contact'], true); ?>
                <li class="has-sub<?= $aboutOpen ? ' is-current' : '' ?>">
                    <button type="button" class="sub-toggle" aria-expanded="false" aria-controls="sub-about">
                        <span class="roll"><span data-hover="<?= e($menu['about']) ?>"><?= e($menu['about']) ?></span></span>
                        <svg class="chevron" width="12" height="12" viewBox="0 0 12 12" aria-hidden="true"><path d="M2.5 4.5 6 8l3.5-3.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <ul class="sub-menu" id="sub-about">
                        <li><a href="<?= route_url('about') ?>" <?= $nav === 'about' ? 'aria-current="page"' : '' ?>><?= icon('building', 18) ?> <?= e(__('À propos de nous')) ?></a></li>
                        <li><a href="<?= route_url('contact') ?>" <?= $nav === 'contact' ? 'aria-current="page"' : '' ?>><?= icon('mail', 18) ?> <?= e($menu['contact']) ?></a></li>
                    </ul>
                </li>
            </ul>
            <a class="btn btn-gold btn-lg nav-cta" href="<?= route_url('give') ?>"><span><?= icon('heart', 20) ?> <?= e(__('Faire un don')) ?></span></a>
            <?= $langSwitch('lang-switch lang-switch-menu') ?>
        </nav>
        <?= $langSwitch('lang-switch lang-switch-header') ?>
        <a class="btn btn-gold header-cta magnetic" href="<?= route_url('give') ?>"><span><?= icon('heart', 18) ?> <?= e(__('Faire un don')) ?></span></a>
        <button class="nav-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="<?= e(__('Ouvrir le menu')) ?>" data-label-open="<?= e(__('Ouvrir le menu')) ?>" data-label-close="<?= e(__('Fermer le menu')) ?>"><span></span><span></span></button>
    </div>
</header>

<?php if ($flashes): ?>
    <div class="flash-zone" role="status">
        <?php foreach ($flashes as $f): ?>
            <div class="flash flash-<?= e($f['type']) ?>"><?= e($f['message']) ?></div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<main id="contenu">
<?= $content ?>
</main>

<footer class="site-footer" data-header="dark">
    <div class="container">
        <div class="footer-top">
            <a class="footer-big" href="<?= route_url('give') ?>">
                <span><?= e(__('Ensemble, bâtissons')) ?></span>
                <span><em><?= e(__('l\'avenir')) ?></em> <?= icon('arrow', 64) ?></span>
            </a>
        </div>
        <div class="footer-grid">
            <div>
                <div class="brand brand-light">
                    <img src="<?= e(site_logo()) ?>" alt="<?= e(__('Sceau de l\'Université Adventiste Zurcher')) ?>" class="footer-logo" width="64" height="65" loading="lazy">
                    <span class="brand-text"><strong><?= e($siteName) ?></strong><small><?= e(setting_t('tagline')) ?></small></span>
                </div>
                <div class="socials">
                    <?php foreach (['facebook_url' => 'facebook', 'youtube_url' => 'youtube'] as $k => $ic): if (setting($k)): ?>
                        <a href="<?= e(setting($k)) ?>" target="_blank" rel="noopener" aria-label="<?= e(ucfirst($ic)) ?>"><?= icon($ic, 20) ?></a>
                    <?php endif; endforeach; ?>
                    <?php if (setting('contact_whatsapp')): ?>
                        <a href="https://wa.me/<?= e(preg_replace('/\D/', '', setting('contact_whatsapp'))) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><?= icon('whatsapp', 20) ?></a>
                    <?php endif; ?>
                </div>
            </div>
            <div>
                <h4><?= e(__('Navigation')) ?></h4>
                <ul class="footer-links">
                    <?php foreach ($menu as $key => $label): ?><li><a href="<?= route_url($key) ?>"><?= e($label) ?></a></li><?php endforeach; ?>
                </ul>
            </div>
            <div>
                <h4><?= e(__('Nos projets')) ?></h4>
                <ul class="footer-links">
                    <li><a href="<?= route_url('projects', ['query' => ['status' => 'ongoing']]) ?>"><?= e(__('Projets en cours')) ?></a></li>
                    <li><a href="<?= route_url('projects', ['query' => ['status' => 'upcoming']]) ?>"><?= e(__('Projets à venir')) ?></a></li>
                    <li><a href="<?= route_url('projects', ['query' => ['status' => 'completed']]) ?>"><?= e(__('Projets achevés')) ?></a></li>
                </ul>
            </div>
            <div>
                <h4><?= e(__('Contact')) ?></h4>
                <p class="footer-contact"><?= nl2br(e(setting('contact_address'))) ?></p>
                <?php if (setting('contact_email')): ?><p class="footer-contact"><a href="mailto:<?= e(setting('contact_email')) ?>"><?= e(setting('contact_email')) ?></a></p><?php endif; ?>
                <?php if (setting('contact_phone')): ?><p class="footer-contact"><?= e(setting('contact_phone')) ?></p><?php endif; ?>
                <?php if (setting('website_url')): ?><p class="footer-contact"><a href="<?= e(setting('website_url')) ?>" target="_blank" rel="noopener"><?= e(preg_replace('#^https?://#', '', setting('website_url'))) ?> ↗</a></p><?php endif; ?>
            </div>
        </div>
        <div class="footer-bottom">
            <span>© <?= date('Y') ?> <?= e($siteName) ?>. <?= e(__('Tous droits réservés.')) ?></span>
            <nav class="footer-legal" aria-label="<?= e(__('Informations légales')) ?>">
                <a href="<?= route_url('privacy') ?>"><?= e(__('Confidentialité')) ?></a>
                <a href="<?= route_url('terms') ?>"><?= e(__('Conditions générales')) ?></a>
                <button type="button" class="link-btn" data-cookie-open><?= e(__('Gérer les cookies')) ?></button>
            </nav>
            <?= $langSwitch('lang-switch lang-switch-footer') ?>
        </div>
    </div>
</footer>

<a href="#top" class="to-top" aria-label="<?= e(__('Revenir en haut de la page')) ?>"><?= icon('arrow', 20) ?></a>

<?php if ($nav !== 'give'): ?>
<div class="mobile-give" data-mobile-give>
    <a class="btn btn-gold" href="<?= route_url('give') ?>"><span><?= icon('heart', 18) ?> <?= e(__('Faire un don')) ?></span></a>
</div>
<?php endif; ?>

<div class="cookie-banner" id="cookie-banner" role="region" aria-label="<?= e(__('Cookies')) ?>" data-ga="<?= e($gaId) ?>" hidden>
    <p>
        <?php if ($gaId): ?>
            <?= e(__('Nous utilisons un cookie indispensable au fonctionnement du site et, avec votre accord, Google Analytics pour mesurer l\'audience.')) ?>
        <?php else: ?>
            <?= e(__('Ce site utilise uniquement un cookie indispensable à son fonctionnement. Les statistiques de visite sont anonymes et sans cookie.')) ?>
        <?php endif; ?>
        <a href="<?= route_url('privacy') ?>"><?= e(__('En savoir plus')) ?></a>
    </p>
    <div class="cookie-actions">
        <?php if ($gaId): ?>
            <button type="button" class="btn btn-outline btn-sm" data-consent="denied"><?= e(__('Refuser')) ?></button>
            <button type="button" class="btn btn-primary btn-sm" data-consent="granted"><?= e(__('Accepter')) ?></button>
        <?php else: ?>
            <button type="button" class="btn btn-primary btn-sm" data-consent="denied"><?= e(__('J\'ai compris')) ?></button>
        <?php endif; ?>
    </div>
</div>

<script src="<?= asset('vendor/gsap.min.js') ?>" defer></script>
<script src="<?= asset('vendor/ScrollTrigger.min.js') ?>" defer></script>
<script src="<?= asset('vendor/lenis.min.js') ?>" defer></script>
<script src="<?= asset('js/main.js') ?>" defer></script>
</body>
</html>
