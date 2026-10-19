<?php
declare(strict_types=1);

require dirname(__DIR__, 2) . '/app/bootstrap.php';
require APP . '/admin/admin.php';

set_lang('fr'); // l'administration reste en français

try {
    admin_dispatch();
} catch (Throwable $ex) {
    error_log((string) $ex);
    http_response_code(500);
    echo config('debug') ? '<pre>' . e((string) $ex) . '</pre>' : '<h1>Erreur interne</h1><p>Consultez le journal d\'erreurs PHP du serveur.</p>';
}
