<?php
declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', ROOT . '/app');
define('PUBLIC_DIR', ROOT . '/public');

if (PHP_VERSION_ID < 80100) {
    http_response_code(500);
    exit('PHP 8.1 ou supérieur est requis.');
}

$configFile = APP . '/config.php';
if (!is_file($configFile)) {
    http_response_code(500);
    exit('Configuration manquante : copiez app/config.sample.php vers app/config.php, puis lancez « php install/install.php ».');
}
$GLOBALS['config'] = require $configFile;

date_default_timezone_set($GLOBALS['config']['timezone'] ?? 'UTC');

if (!empty($GLOBALS['config']['debug'])) {
    error_reporting(E_ALL);
    ini_set('display_errors', '1');
} else {
    ini_set('display_errors', '0');
}

require APP . '/helpers.php';
require APP . '/i18n.php';
require APP . '/payments.php';

if (PHP_SAPI !== 'cli') {
    $secure = (($_SERVER['HTTPS'] ?? '') !== '' && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('uaz_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => base_path() . '/',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
    if ($secure) {
        // HTTPS imposé par le navigateur pendant un an (ce sous-domaine uniquement)
        header('Strict-Transport-Security: max-age=31536000');
    }
}
