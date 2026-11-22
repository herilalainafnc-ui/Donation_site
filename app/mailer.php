<?php
declare(strict_types=1);

/*
 * Envoi d'e-mails (reçus de don, rappels d'échéance, notifications).
 * - Si « smtp » est renseigné dans app/config.php, l'envoi passe par ce serveur SMTP
 *   (ex. la messagerie @zurcher.edu.mg) : c'est la méthode recommandée.
 * - Sinon, repli sur la fonction mail() de PHP (nécessite un serveur mail sur la machine).
 * Aucun e-mail n'est envoyé si « mail_notifications » vaut false (mode test local) :
 * le message est alors seulement écrit dans storage/mail.log.
 */

/** Envoie un e-mail texte (et HTML facultatif). Renvoie true si le message est parti. */
function send_mail(string $to, string $subject, string $text, ?string $html = null): bool
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    if (!config('mail_notifications')) {
        mail_log($to, $subject, $text);
        return false;
    }
    $from = (string) config('mail_from', 'no-reply@localhost');
    $fromName = setting_t('site_name', 'UAZ');
    $smtp = (array) config('smtp', []);
    try {
        if (!empty($smtp['host'])) {
            smtp_send($smtp, $from, $fromName, $to, $subject, $text, $html);
            return true;
        }
        [$headers, $body] = mime_message($from, $fromName, $to, $subject, $text, $html, false);
        return @mail($to, mime_header($subject), $body, $headers);
    } catch (Throwable $ex) {
        error_log('Envoi e-mail impossible : ' . $ex->getMessage());
        mail_log($to, $subject, $text, $ex->getMessage());
        return false;
    }
}

/** Trace locale des e-mails non envoyés (mode test ou erreur). */
function mail_log(string $to, string $subject, string $text, string $error = ''): void
{
    $line = '[' . date('Y-m-d H:i:s') . "] À : $to — $subject" . ($error ? " — ERREUR : $error" : '') . "\n" . $text . "\n" . str_repeat('-', 60) . "\n";
    @file_put_contents(ROOT . '/storage/mail.log', $line, FILE_APPEND | LOCK_EX);
}

function mime_header(string $s): string
{
    return preg_match('/[^\x20-\x7e]/', $s) ? '=?UTF-8?B?' . base64_encode($s) . '?=' : $s;
}

/** Construit les en-têtes et le corps MIME. $full = true : en-têtes complets pour SMTP (To, Subject, Date). */
function mime_message(string $from, string $fromName, string $to, string $subject, string $text, ?string $html, bool $full): array
{
    $h = [];
    if ($full) {
        $h[] = 'Date: ' . date('r');
        $h[] = 'To: <' . $to . '>';
        $h[] = 'Subject: ' . mime_header($subject);
        $h[] = 'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . (substr(strrchr($from, '@'), 1) ?: 'localhost') . '>';
    }
    $h[] = 'From: ' . mime_header($fromName) . ' <' . $from . '>';
    $h[] = 'MIME-Version: 1.0';
    if ($html === null) {
        $h[] = 'Content-Type: text/plain; charset=UTF-8';
        $h[] = 'Content-Transfer-Encoding: base64';
        return [implode("\r\n", $h), chunk_split(base64_encode($text))];
    }
    $b = 'b' . bin2hex(random_bytes(8));
    $h[] = 'Content-Type: multipart/alternative; boundary="' . $b . '"';
    $body = "--$b\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($text))
        . "--$b\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n" . chunk_split(base64_encode($html))
        . "--$b--\r\n";
    return [implode("\r\n", $h), $body];
}

/** Client SMTP minimal : SSL (465) ou STARTTLS (587), authentification LOGIN. */
function smtp_send(array $c, string $from, string $fromName, string $to, string $subject, string $text, ?string $html): void
{
    $secure = strtolower((string) ($c['secure'] ?? 'tls'));
    $port = (int) ($c['port'] ?? ($secure === 'ssl' ? 465 : 587));
    $host = ($secure === 'ssl' ? 'ssl://' : '') . $c['host'];
    $fp = @stream_socket_client($host . ':' . $port, $errno, $errstr, 20);
    if (!$fp) {
        throw new RuntimeException("connexion SMTP impossible ($errstr)");
    }
    stream_set_timeout($fp, 20);
    $read = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }
        return $data;
    };
    $cmd = function (string $line, array $ok) use ($fp, $read): string {
        if ($line !== '') {
            fwrite($fp, $line . "\r\n");
        }
        $resp = $read();
        if (!in_array((int) substr($resp, 0, 3), $ok, true)) {
            throw new RuntimeException('SMTP : ' . trim($resp));
        }
        return $resp;
    };
    $me = parse_url((string) config('site_url', 'http://localhost'), PHP_URL_HOST) ?: 'localhost';
    $cmd('', [220]);
    $cmd('EHLO ' . $me, [250]);
    if ($secure === 'tls') {
        $cmd('STARTTLS', [220]);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
            throw new RuntimeException('STARTTLS refusé');
        }
        $cmd('EHLO ' . $me, [250]);
    }
    if (!empty($c['user'])) {
        $cmd('AUTH LOGIN', [334]);
        $cmd(base64_encode((string) $c['user']), [334]);
        $cmd(base64_encode((string) ($c['pass'] ?? '')), [235]);
    }
    $cmd('MAIL FROM:<' . $from . '>', [250]);
    $cmd('RCPT TO:<' . $to . '>', [250, 251]);
    $cmd('DATA', [354]);
    [$headers, $body] = mime_message($from, $fromName, $to, $subject, $text, $html, true);
    $data = $headers . "\r\n\r\n" . $body;
    $data = preg_replace('/^\./m', '..', str_replace(["\r\n", "\n"], ["\n", "\r\n"], $data));
    fwrite($fp, $data . "\r\n.\r\n");
    $cmd('', [250]);
    fwrite($fp, "QUIT\r\n");
    fclose($fp);
}
