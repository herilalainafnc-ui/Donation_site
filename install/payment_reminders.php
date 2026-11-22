<?php
/**
 * Tâche quotidienne des dons en ligne (à lancer par cron, une fois par jour) :
 * - rappel par e-mail des échéances des dons réguliers payés par Mobile Money
 *   (le jour de l'échéance, puis 7 jours après si l'échéance n'est toujours pas réglée) ;
 * - paiements restés « en attente » plus de 48 h marqués « expirés ».
 *
 *   php install/payment_reminders.php
 *
 * Exemple de crontab (8 h, heure de Madagascar = 5 h UTC) :
 *   0 5 * * * cd /var/www/funding && sudo -u uazfunding php install/payment_reminders.php >> /var/log/uaz-funding-paiements.log 2>&1
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('À lancer en ligne de commande uniquement.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$result = run_payment_reminders();
echo '[' . date('Y-m-d H:i:s') . '] rappels envoyés : ' . $result['reminders'] . ', paiements expirés : ' . $result['expired'] . PHP_EOL;
