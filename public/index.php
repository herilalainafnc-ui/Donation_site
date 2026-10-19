<?php
declare(strict_types=1);

$uriPath = rawurldecode(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/');

// Serveur de développement PHP (php -S) : servir les fichiers statiques et l'admin.
if (PHP_SAPI === 'cli-server') {
    $file = __DIR__ . $uriPath;
    if ($uriPath !== '/' && is_file($file) && !str_ends_with($file, '.php')) {
        return false;
    }
    if (preg_match('#^/admin(/|$)#', $uriPath)) {
        require __DIR__ . '/admin/index.php';
        return;
    }
}

require dirname(__DIR__) . '/app/bootstrap.php';
require APP . '/site.php';

$path = trim(substr($uriPath, strlen(base_path())), '/');

try {
    route_public($path);
} catch (Throwable $ex) {
    error_log((string) $ex);
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $ex) . '</pre>';
    } else {
        echo '<h1>Erreur interne</h1><p>Le site rencontre un problème momentané. Merci de réessayer plus tard.</p>';
    }
}
