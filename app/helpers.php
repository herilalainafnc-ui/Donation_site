<?php
declare(strict_types=1);

const PROJECT_STATUSES = [
    'ongoing'   => 'En cours',
    'upcoming'  => 'À venir',
    'completed' => 'Achevé',
];

const DONOR_TYPES = [
    'individual'   => 'Particulier',
    'church'       => 'Église / District',
    'company'      => 'Entreprise',
    'organization' => 'Organisation / ONG',
    'alumni'       => 'Ancien étudiant',
];

const DONOR_TYPES_PLURAL = [
    'individual'   => 'Particuliers',
    'church'       => 'Églises et districts',
    'company'      => 'Entreprises',
    'organization' => 'Organisations et ONG',
    'alumni'       => 'Anciens étudiants',
];

const METHOD_ICONS = [
    'bank'   => 'Banque',
    'mobile' => 'Mobile Money',
    'heart'  => 'Cœur',
    'gift'   => 'Cadeau',
    'globe'  => 'En ligne',
    'hand'   => 'Main tendue',
    'grad'   => 'Étudiant',
];

/* ---------- Configuration ---------- */

function config(string $key, mixed $default = null): mixed
{
    return $GLOBALS['config'][$key] ?? $default;
}

function base_path(): string
{
    return rtrim((string) config('base_path', ''), '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    $file = PUBLIC_DIR . '/assets/' . ltrim($path, '/');
    $v = is_file($file) ? '?v=' . filemtime($file) : '';
    return url('assets/' . ltrim($path, '/')) . $v;
}

function media(?string $path): string
{
    return $path ? url($path) : '';
}

/** Logo téléversé dans l'admin, sinon le sceau officiel livré avec le site. */
function site_logo(): string
{
    return setting('logo') ? media(setting('logo')) : asset('img/logo.webp');
}

function absolute_url(string $path = ''): string
{
    return rtrim((string) config('site_url', ''), '/') . url($path);
}

/* ---------- Base de données ---------- */

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }
    $c = config('db');
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    if (($c['driver'] ?? 'mysql') === 'sqlite') {
        $pdo = new PDO('sqlite:' . $c['sqlite_path'], null, null, $opts);
        $pdo->exec('PRAGMA foreign_keys = ON');
    } else {
        $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'] ?? 3306, $c['name']);
        $pdo = new PDO($dsn, $c['user'], $c['pass'], $opts);
    }
    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

function fetch_all(string $sql, array $params = []): array
{
    return q($sql, $params)->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array
{
    $row = q($sql, $params)->fetch();
    return $row === false ? null : $row;
}

function fetch_value(string $sql, array $params = []): mixed
{
    $v = q($sql, $params)->fetchColumn();
    return $v === false ? null : $v;
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

/* ---------- Paramètres du site ---------- */

function settings(bool $reload = false): array
{
    static $cache = null;
    if ($cache === null || $reload) {
        $cache = [];
        foreach (fetch_all('SELECT skey, svalue FROM settings') as $row) {
            $cache[$row['skey']] = (string) $row['svalue'];
        }
    }
    return $cache;
}

function setting(string $key, string $default = ''): string
{
    $v = settings()[$key] ?? '';
    return $v !== '' ? $v : $default;
}

function save_setting(string $key, ?string $value): void
{
    if (fetch_value('SELECT COUNT(*) FROM settings WHERE skey = ?', [$key])) {
        q('UPDATE settings SET svalue = ? WHERE skey = ?', [$value, $key]);
    } else {
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$key, $value]);
    }
}

/* ---------- Affichage ---------- */

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Transforme un texte brut saisi dans l'admin en paragraphes / listes HTML sûrs. */
function rich_text(?string $text): string
{
    $text = trim(str_replace("\r\n", "\n", (string) $text));
    if ($text === '') {
        return '';
    }
    // Lecture ligne à ligne : « ## » = sous-titre, « - » = élément de liste, ligne vide = nouveau paragraphe.
    $html = '';
    $para = [];
    $list = [];
    $flush = function () use (&$html, &$para, &$list) {
        if ($para) {
            $html .= '<p>' . implode('<br>', array_map('inline_format', $para)) . '</p>';
            $para = [];
        }
        if ($list) {
            $html .= '<ul><li>' . implode('</li><li>', array_map('inline_format', $list)) . '</li></ul>';
            $list = [];
        }
    };
    foreach (explode("\n", $text) as $line) {
        $line = rtrim($line);
        if (trim($line) === '') {
            $flush();
        } elseif (preg_match('/^\s*##\s+(.+)$/', $line, $m)) {
            $flush();
            $html .= '<h3>' . inline_format($m[1]) . '</h3>';
        } elseif (preg_match('/^\s*[-•*]\s+(.+)$/', $line, $m)) {
            if ($para) {
                $flush();
            }
            $list[] = $m[1];
        } else {
            if ($list) {
                $flush();
            }
            $para[] = trim($line);
        }
    }
    $flush();
    return $html;
}

/** **gras** uniquement, après échappement. */
function inline_format(string $line): string
{
    return preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', e($line));
}

function excerpt(?string $text, int $length = 160): string
{
    $text = trim(preg_replace('/\s+/', ' ', str_replace(['**', '##'], '', (string) $text)));
    if (mb_strlen($text) <= $length) {
        return $text;
    }
    return rtrim(mb_substr($text, 0, $length - 1)) . '…';
}

function money(mixed $amount, ?string $currency = null): string
{
    $currency ??= setting('currency', 'Ar');
    $sep = lang() === 'en' ? ',' : ' ';
    return number_format((float) $amount, 0, '.', $sep) . ' ' . $currency;
}

function percent(mixed $raised, mixed $goal): int
{
    $goal = (float) $goal;
    if ($goal <= 0) {
        return 0;
    }
    return (int) max(0, min(100, round((float) $raised / $goal * 100)));
}

function format_date(?string $date): string
{
    if (!$date) {
        return '';
    }
    $t = strtotime($date);
    if (!$t) {
        return '';
    }
    if (lang() === 'en') {
        return date('F j, Y', $t);
    }
    $months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return date('j', $t) . ' ' . $months[(int) date('n', $t) - 1] . ' ' . date('Y', $t);
}

function initials(string $name): string
{
    $parts = preg_split('/\s+/', trim($name));
    $out = '';
    foreach (array_slice($parts, 0, 2) as $p) {
        $out .= mb_strtoupper(mb_substr($p, 0, 1));
    }
    return $out;
}

/** Nom public d'un donateur : « Jea******* » si son nom est masqué. */
function donor_name(array $d): string
{
    $name = trim((string) $d['name']);
    if (empty($d['mask_name'])) {
        return $name;
    }
    $len = mb_strlen($name);
    return mb_substr($name, 0, 3) . str_repeat('*', max(3, $len - 3));
}

/** Initiales pour l'avatar ; une seule lettre si le nom est masqué. */
function donor_initials(array $d): string
{
    return empty($d['mask_name']) ? initials($d['name']) : mb_strtoupper(mb_substr(trim($d['name']), 0, 1));
}

function slugify(string $text): string
{
    $text = mb_strtolower(trim($text));
    $map = ['à' => 'a', 'â' => 'a', 'ä' => 'a', 'á' => 'a', 'ã' => 'a', 'ç' => 'c', 'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e',
        'î' => 'i', 'ï' => 'i', 'í' => 'i', 'ô' => 'o', 'ö' => 'o', 'ó' => 'o', 'ù' => 'u', 'û' => 'u', 'ü' => 'u', 'ú' => 'u',
        'ÿ' => 'y', 'ñ' => 'n', 'œ' => 'oe', 'æ' => 'ae', "'" => '-', '’' => '-'];
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'element';
}

function view(string $name, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP . '/views/' . $name . '.php';
}

function partial(string $name, array $data = []): string
{
    ob_start();
    view('partials/' . $name, $data);
    return (string) ob_get_clean();
}

function render_page(string $view, array $data = []): void
{
    ob_start();
    view('pages/' . $view, $data);
    $data['content'] = ob_get_clean();
    view('layout', $data);
}

function icon(string $name, int $size = 24): string
{
    $paths = [
        'bank'   => '<path d="M3 10h18M5 10v8m4-8v8m6-8v8m4-8v8M3 21h18M12 3l9 5H3z"/>',
        'mobile' => '<rect x="7" y="2" width="10" height="20" rx="2"/><path d="M11 18h2"/>',
        'heart'  => '<path d="M12 21s-7.5-4.6-9.6-9.3C.9 8.2 3.1 4.5 6.7 4.5c2.1 0 3.6 1.2 5.3 3.2 1.7-2 3.2-3.2 5.3-3.2 3.6 0 5.8 3.7 4.3 7.2C19.5 16.4 12 21 12 21z"/>',
        'gift'   => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M5 12v9h14v-9M12 8v13M12 8S10.5 3 8 3a2.5 2.5 0 0 0 0 5zm0 0s1.5-5 4-5a2.5 2.5 0 0 1 0 5z"/>',
        'globe'  => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3z"/>',
        'hand'   => '<path d="M7 11V5.5a1.5 1.5 0 0 1 3 0V10m0-5.5V4a1.5 1.5 0 0 1 3 0v6m0-4.5a1.5 1.5 0 0 1 3 0V11m0-3a1.5 1.5 0 0 1 3 0v6a8 8 0 0 1-8 8h-.5a7 7 0 0 1-5.6-2.8L3 15.5a1.6 1.6 0 0 1 2.4-2L7 15"/>',
        'grad'   => '<path d="M2 9l10-5 10 5-10 5z"/><path d="M6 11v5c0 1.7 2.7 3 6 3s6-1.3 6-3v-5M22 9v6"/>',
        'pin'    => '<path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z"/><circle cx="12" cy="9.5" r="2.5"/>',
        'mail'   => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 7l9 6 9-6"/>',
        'phone'  => '<path d="M5 3h3l2 5-2.5 1.5a11 11 0 0 0 5 5L14 12l5 2v3a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 10h18M8 3v4m8-4v4"/>',
        'copy'   => '<rect x="9" y="9" width="12" height="12" rx="2"/><path d="M5 15V5a2 2 0 0 1 2-2h8"/>',
        'check'  => '<path d="M5 12.5l4.5 4.5L19 7.5"/>',
        'arrow'  => '<path d="M5 12h14m-6-6l6 6-6 6"/>',
        'quote'  => '<path d="M9 7H5a2 2 0 0 0-2 2v4h6V7zm0 6c0 3-1.5 4.5-4 5M21 7h-4a2 2 0 0 0-2 2v4h6V7zm0 6c0 3-1.5 4.5-4 5"/>',
        'building' => '<path d="M4 21V5l8-3 8 3v16M9 21v-5h6v5M8 8h2m4 0h2M8 12h2m4 0h2M2 21h20"/>',
        'users'  => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M16 4.5a3.5 3.5 0 0 1 0 7M18 14a6 6 0 0 1 3.5 6"/>',
        'target' => '<circle cx="12" cy="12" r="9"/><circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
        'facebook' => '<path d="M14 8h3V4h-3a4 4 0 0 0-4 4v2H7v4h3v7h4v-7h3l1-4h-4V8z"/>',
        'youtube'  => '<rect x="2" y="5" width="20" height="14" rx="4"/><path d="M10 9l5 3-5 3z"/>',
        'whatsapp' => '<path d="M4 20l1.3-4A8.5 8.5 0 1 1 8.4 19z"/><path d="M9 8.5c0 3.5 3 6.5 6.5 6.5l1-1.5-2-1-1 .8a4.5 4.5 0 0 1-2.3-2.3l.8-1-1-2z"/>',
        'menu'   => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'close'  => '<path d="M6 6l12 12M18 6L6 18"/>',
    ];
    $p = $paths[$name] ?? $paths['heart'];
    return '<svg class="icon" width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $p . '</svg>';
}

/* ---------- Requêtes / sécurité ---------- */

function redirect(string $to): never
{
    header('Location: ' . $to);
    exit;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0'), 0, 45);
}

function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    if (!hash_equals($_SESSION['_csrf'] ?? '', (string) ($_POST['_csrf'] ?? ''))) {
        http_response_code(419);
        exit('La session a expiré. Revenez en arrière, rechargez la page et réessayez.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $f = $_SESSION['_flash'] ?? [];
    unset($_SESSION['_flash']);
    return $f;
}

function old(string $key, string $default = ''): string
{
    return (string) ($_SESSION['_old'][$key] ?? $default);
}

function clear_old(): void
{
    unset($_SESSION['_old']);
}

/* ---------- Téléversement d'images ---------- */

function upload_image(?array $file, string $dir): ?string
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $msg = in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)
            ? 'Image trop lourde pour le serveur.'
            : 'Échec du téléversement (code ' . $file['error'] . ').';
        throw new RuntimeException($msg);
    }
    $max = (int) config('upload_max_mb', 8) * 1024 * 1024;
    if ($file['size'] > $max) {
        throw new RuntimeException('Image trop lourde (maximum ' . config('upload_max_mb', 8) . ' Mo).');
    }
    $info = @getimagesize($file['tmp_name']);
    $types = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
    if (!$info || !isset($types[$info[2]])) {
        throw new RuntimeException('Format non pris en charge : utilisez JPG, PNG, WEBP ou GIF.');
    }
    // Une image décompressée occupe ~5 octets par pixel : au-delà de 60 Mpx, le serveur ne peut pas la traiter.
    $megapixels = $info[0] * $info[1] / 1e6;
    if ($megapixels > 60) {
        throw new RuntimeException(sprintf('Image trop grande (%d × %d pixels). Réduisez-la sous 6 000 pixels de large avant de l\'envoyer.', $info[0], $info[1]));
    }
    $dir =preg_replace('/[^a-z0-9_-]/', '', $dir);
    $rel = 'uploads/' . $dir . '/' . date('Y/m');
    $abs = PUBLIC_DIR . '/' . $rel;
    if (!is_dir($abs) && !mkdir($abs, 0755, true) && !is_dir($abs)) {
        throw new RuntimeException('Impossible de créer le dossier ' . $rel . ' (droits d\'écriture ?).');
    }
    $name = bin2hex(random_bytes(12)) . '.' . $types[$info[2]];
    if (!move_uploaded_file($file['tmp_name'], $abs . '/' . $name)) {
        throw new RuntimeException('Impossible d\'enregistrer l\'image sur le serveur.');
    }
    resize_image($abs . '/' . $name, $info);
    return $rel . '/' . $name;
}

function memory_limit_bytes(): int
{
    $v = trim((string) ini_get('memory_limit'));
    if ($v === '' || $v === '-1') {
        return PHP_INT_MAX;
    }
    $n = (int) $v;
    return match (strtolower(substr($v, -1))) {
        'g' => $n * 1024 ** 3,
        'm' => $n * 1024 ** 2,
        'k' => $n * 1024,
        default => $n,
    };
}

/** Réduit la largeur des images trop grandes (et supprime les métadonnées EXIF). */
function resize_image(string $path, array $info): void
{
    $maxW = (int) config('image_max_width', 1920);
    [$w, $h, $type] = $info;
    if ($w <= $maxW || !function_exists('imagecreatetruecolor') || $type === IMAGETYPE_GIF) {
        return;
    }
    // Les photos d'appareil (24-48 Mpx) dépassent la limite mémoire par défaut (128 Mo) une fois décompressées.
    $needed = (int) ($w * $h * 5 + $maxW * ($h * $maxW / $w) * 5) + 32 * 1024 * 1024;
    if ($needed > memory_limit_bytes()) {
        @ini_set('memory_limit', (string) max($needed, 256 * 1024 * 1024));
    }
    $src = match ($type) {
        IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
        IMAGETYPE_PNG  => @imagecreatefrompng($path),
        IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default        => false,
    };
    if (!$src) {
        return;
    }
    $nh = (int) round($h * $maxW / $w);
    $dst = imagecreatetruecolor($maxW, $nh);
    if ($type !== IMAGETYPE_JPEG) {
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
    }
    imagecopyresampled($dst, $src, 0, 0, 0, 0, $maxW, $nh, $w, $h);
    match ($type) {
        IMAGETYPE_JPEG => imagejpeg($dst, $path, 85),
        IMAGETYPE_PNG  => imagepng($dst, $path, 6),
        IMAGETYPE_WEBP => imagewebp($dst, $path, 85),
    };
}

function delete_upload(?string $rel): void
{
    if (!$rel || !str_starts_with($rel, 'uploads/')) {
        return;
    }
    $base = realpath(PUBLIC_DIR . '/uploads');
    $file = realpath(PUBLIC_DIR . '/' . $rel);
    if ($base && $file && str_starts_with($file, $base . DIRECTORY_SEPARATOR) && is_file($file)) {
        @unlink($file);
    }
}

/** Convertit $_FILES['x'] (multiple) en liste de fichiers individuels. */
function normalize_files(?array $files): array
{
    if (!$files || !is_array($files['name'] ?? null)) {
        return $files ? [$files] : [];
    }
    $out = [];
    foreach ($files['name'] as $i => $name) {
        $out[] = [
            'name' => $name, 'type' => $files['type'][$i], 'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i], 'size' => $files['size'][$i],
        ];
    }
    return $out;
}

/* ---------- Administration ---------- */

function admin_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['admin_id'])) {
            if (time() - (int) ($_SESSION['admin_seen'] ?? 0) > 2 * 3600) {
                unset($_SESSION['admin_id']);
            } else {
                $_SESSION['admin_seen'] = time();
                $user = fetch_one('SELECT id, name, email FROM admins WHERE id = ?', [$_SESSION['admin_id']]);
            }
        }
    }
    return $user;
}

/* ---------- E-mail ---------- */

function notify_admin(string $subject, string $body): void
{
    $to = setting('notify_email', setting('contact_email'));
    if (!config('mail_notifications') || !$to) {
        return;
    }
    $headers = 'From: ' . config('mail_from', 'no-reply@localhost') . "\r\nContent-Type: text/plain; charset=UTF-8";
    @mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, $headers);
}
