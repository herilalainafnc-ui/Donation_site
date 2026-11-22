<?php
declare(strict_types=1);

require __DIR__ . '/fields.php';

const ADMIN_PER_PAGE = 25;

function admin_url(string $p = 'dashboard', array $params = []): string
{
    return url('admin/') . '?' . http_build_query(['p' => $p] + $params);
}

function admin_render(string $view, array $data = [], bool $withLayout = true): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    require APP . '/admin/views/' . $view . '.php';
    $content = ob_get_clean();
    if ($withLayout) {
        require APP . '/admin/views/layout.php';
    } else {
        echo $content;
    }
}

function admin_entities(): array
{
    static $e = null;
    if ($e === null) {
        $e = require APP . '/admin/entities.php';
        foreach ($e as &$ent) {
            $ent['fields'] = expand_i18n($ent['fields'], $ent['i18n'] ?? []);
            if (!empty($ent['filter']) && is_string($ent['filter'][1])) {
                $opts = [];
                foreach (fetch_all($ent['filter'][1]) as $r) {
                    $r = array_values($r);
                    $opts[(string) $r[0]] = (string) $r[1];
                }
                $ent['filter'][1] = $opts;
            }
        }
    }
    return $e;
}

function settings_schema(): array
{
    static $s = null;
    if ($s === null) {
        $s = require APP . '/admin/settings_schema.php';
        foreach ($s as &$tab) {
            $tab['fields'] = expand_i18n($tab['fields'], $tab['i18n'] ?? []);
        }
    }
    return $s;
}

/**
 * Double les champs traduisibles : « champ » (français) suivi de « champ_en » (anglais, facultatif).
 * Les champs texte courts sont placés côte à côte.
 */
function expand_i18n(array $fields, array $translatable): array
{
    $out = [];
    foreach ($fields as $name => $def) {
        if (!in_array($name, $translatable, true)) {
            $out[$name] = $def;
            continue;
        }
        $short = $def['type'] === 'text';
        $fr = $def;
        $fr['label'] = $def['label'] . ' — Français';
        $fr['half'] = $short ?: !empty($def['half']);
        $en = ['label' => $def['label'] . ' — English', 'type' => $def['type'], 'half' => $fr['half'], 'lang' => 'en']
            + array_intersect_key($def, array_flip(['rows']));
        $en['help'] = 'Version anglaise (langue par défaut du site). Si vide, le texte français est affiché.';
        $out[$name] = $fr;
        $out[$name . '_en'] = $en;
    }
    return $out;
}

function admin_not_found(): never
{
    http_response_code(404);
    admin_render('notfound', ['title' => 'Introuvable']);
    exit;
}

function admin_dispatch(): void
{
    set_lang('fr'); // l'administration est en français (libellés et format des montants)
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
    $p = (string) ($_GET['p'] ?? 'dashboard');
    $a = (string) ($_GET['a'] ?? 'list');

    if ($p === 'login') {
        admin_login();
        return;
    }
    if (!admin_user()) {
        redirect(admin_url('login'));
    }
    if ($p === 'logout') {
        if (is_post()) {
            csrf_verify();
            $_SESSION = [];
            session_regenerate_id(true);
        }
        redirect(admin_url('login'));
    }
    if (isset(admin_entities()[$p])) {
        admin_crud($p, admin_entities()[$p], $a);
        return;
    }
    match ($p) {
        'dashboard' => admin_dashboard(),
        'stats'     => admin_stats(),
        'donations' => admin_donations(),
        'settings'  => admin_settings(),
        'messages'  => admin_messages($a),
        'users'     => admin_users($a),
        'account'   => admin_account(),
        default     => admin_not_found(),
    };
}

/* ---------- Connexion ---------- */

function admin_login(): void
{
    if (admin_user()) {
        redirect(admin_url());
    }
    $error = '';
    if (is_post()) {
        csrf_verify();
        $ip = client_ip();
        q('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
        $fails = (int) fetch_value('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?', [$ip, date('Y-m-d H:i:s', time() - 900)]);
        if ($fails >= 5) {
            $error = 'Trop de tentatives. Réessayez dans 15 minutes.';
        } else {
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
            $pass = (string) ($_POST['password'] ?? '');
            $user = fetch_one('SELECT * FROM admins WHERE LOWER(email) = ?', [$email]);
            if ($user && password_verify($pass, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_id'] = (int) $user['id'];
                $_SESSION['admin_seen'] = time();
                if (password_needs_rehash($user['password_hash'], PASSWORD_DEFAULT)) {
                    q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($pass, PASSWORD_DEFAULT), $user['id']]);
                }
                q('UPDATE admins SET last_login_at = ? WHERE id = ?', [now(), $user['id']]);
                q('DELETE FROM login_attempts WHERE ip = ?', [$ip]);
                redirect(admin_url());
            }
            q('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)', [$ip, now()]);
            usleep(random_int(200000, 500000));
            $error = 'E-mail ou mot de passe incorrect.';
        }
    }
    admin_render('login', ['error' => $error], false);
}

/* ---------- Dons en ligne ---------- */

/** Paiements en ligne et dons réguliers (onglets), avec totaux par devise. */
function admin_donations(): void
{
    $view = ($_GET['view'] ?? '') === 'plans' ? 'plans' : 'payments';
    if (is_post()) {
        csrf_verify();
        $plan = fetch_one('SELECT * FROM donation_plans WHERE id = ?', [(int) ($_POST['plan_id'] ?? 0)]);
        if ($plan && ($_POST['action'] ?? '') === 'cancel') {
            try {
                cancel_plan($plan);
                flash('success', 'Don régulier ' . $plan['reference'] . ' arrêté.');
            } catch (Throwable $ex) {
                flash('error', 'Arrêt impossible chez le prestataire : ' . $ex->getMessage());
            }
        }
        redirect(admin_url('donations', ['view' => 'plans']));
    }
    $status = (string) ($_GET['s'] ?? '');
    $provider = (string) ($_GET['m'] ?? '');
    $where = [];
    $params = [];
    if ($status !== '' && preg_match('/^[a-z]+$/', $status)) {
        $where[] = 'x.status = ?';
        $params[] = $status;
    }
    if ($provider !== '' && isset(payment_providers()[$provider])) {
        $where[] = 'x.provider = ?';
        $params[] = $provider;
    }
    $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $table = $view === 'plans' ? 'donation_plans' : 'donations';
    $rows = fetch_all("SELECT x.*, p.title AS project_title FROM $table x LEFT JOIN projects p ON p.id = x.project_id$sqlWhere ORDER BY x.created_at DESC, x.id DESC LIMIT 200", $params);
    $sum = fn (string $sql, array $p = []) => array_column(fetch_all($sql, $p), 'total', 'currency');
    admin_render('donations', [
        'title' => 'Dons en ligne', 'nav' => 'donations', 'donView' => $view, 'rows' => $rows,
        'status' => $status, 'provider' => $provider,
        'totalAll' => $sum("SELECT currency, SUM(amount) AS total FROM donations WHERE status = 'paid' GROUP BY currency"),
        'totalMonth' => $sum("SELECT currency, SUM(amount) AS total FROM donations WHERE status = 'paid' AND paid_at >= ? GROUP BY currency", [date('Y-m-01 00:00:00')]),
        'activePlans' => (int) fetch_value("SELECT COUNT(*) FROM donation_plans WHERE status = 'active'"),
        'monthlyPlanned' => $sum("SELECT currency, SUM(CASE frequency WHEN 'monthly' THEN amount WHEN 'quarterly' THEN amount / 3 ELSE amount / 12 END) AS total FROM donation_plans WHERE status = 'active' GROUP BY currency"),
    ]);
}
/* ---------- Statistiques de fréquentation ---------- */

/** Pages vues sur une période (anonymes, sans cookie). */
function admin_stats(): void
{
    $days = (int) ($_GET['d'] ?? 30);
    $days = in_array($days, [7, 30, 90, 365], true) ? $days : 30;
    $from = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $prevFrom = date('Y-m-d', strtotime('-' . (2 * $days - 1) . ' days'));
    $sum = fn(string $where, array $p) => (int) fetch_value("SELECT COALESCE(SUM(views), 0) FROM page_views WHERE $where", $p);

    $perDay = array_fill_keys(array_map(fn($i) => date('Y-m-d', strtotime("-$i days")), range($days - 1, 0)), 0);
    foreach (fetch_all('SELECT day, SUM(views) n FROM page_views WHERE day >= ? GROUP BY day', [$from]) as $r) {
        $perDay[substr((string) $r['day'], 0, 10)] = (int) $r['n'];
    }
    $group = fn(string $col, int $limit = 10) => fetch_all("SELECT $col AS label, SUM(views) n FROM page_views WHERE day >= ? GROUP BY $col ORDER BY n DESC LIMIT $limit", [$from]);

    admin_render('stats', [
        'title' => 'Statistiques',
        'nav' => 'stats',
        'days' => $days,
        'total' => $sum('day >= ?', [$from]),
        'previous' => $sum('day >= ? AND day < ?', [$prevFrom, $from]),
        'today' => $sum('day = ?', [date('Y-m-d')]),
        'perDay' => $perDay,
        'pages' => $group('path', 15),
        'referrers' => fetch_all("SELECT referrer AS label, SUM(views) n FROM page_views WHERE day >= ? AND referrer <> '' GROUP BY referrer ORDER BY n DESC LIMIT 10", [$from]),
        'devices' => $group('device'),
        'langs' => $group('lang'),
    ]);
}

/* ---------- Tableau de bord ---------- */

function admin_dashboard(): void
{
    $byStatus = array_fill_keys(array_keys(PROJECT_STATUSES), 0);
    foreach (fetch_all('SELECT status, COUNT(*) n FROM projects GROUP BY status') as $r) {
        $byStatus[$r['status']] = (int) $r['n'];
    }
    admin_render('dashboard', [
        'title' => 'Tableau de bord',
        'byStatus' => $byStatus,
        // Totaux par devise : chaque projet peut avoir sa propre devise
        'raised' => sum_by_currency(fetch_all('SELECT raised_amount, currency FROM projects'), 'raised_amount'),
        'goal' => sum_by_currency($open = fetch_all("SELECT goal_amount, raised_amount, currency FROM projects WHERE status <> 'completed'"), 'goal_amount'),
        'raisedOpen' => sum_by_currency($open, 'raised_amount'),
        'donors' => (int) fetch_value('SELECT COUNT(*) FROM donors'),
        'testimonials' => (int) fetch_value('SELECT COUNT(*) FROM testimonials'),
        'unread' => (int) fetch_value('SELECT COUNT(*) FROM messages WHERE is_read = 0'),
        'views' => (int) fetch_value('SELECT COALESCE(SUM(views), 0) FROM page_views WHERE day >= ?', [date('Y-m-d', strtotime('-29 days'))]),
        'messages' => fetch_all('SELECT * FROM messages ORDER BY created_at DESC, id DESC LIMIT 6'),
        'projects' => fetch_all("SELECT * FROM projects WHERE status = 'ongoing' ORDER BY sort_order, id DESC LIMIT 5"),
    ]);
}

/* ---------- CRUD générique ---------- */

function field_options(array $def): array
{
    if (isset($def['options_sql'])) {
        $opts = [];
        foreach (fetch_all($def['options_sql']) as $r) {
            $opts[(string) reset($r)] = (string) next($r);
        }
        return $opts;
    }
    return $def['options'] ?? [];
}

function admin_crud(string $key, array $ent, string $a): void
{
    $id = (int) ($_GET['id'] ?? 0);
    if ($a === 'save' && is_post()) {
        csrf_verify();
        crud_save($key, $ent, $id);
    }
    if ($a === 'delete' && is_post()) {
        csrf_verify();
        crud_delete($key, $ent, $id);
    }
    if ($a === 'edit' || $a === 'new') {
        $row = $id ? fetch_one("SELECT * FROM {$ent['table']} WHERE id = ?", [$id]) : null;
        if ($id && !$row) {
            admin_not_found();
        }
        $values = $row ?? [];
        if (!$row) {
            foreach ($ent['fields'] as $name => $def) {
                if (isset($_GET[$name]) && is_scalar($_GET[$name])) {
                    $values[$name] = (string) $_GET[$name];
                } elseif (isset($def['default']) && !in_array($def['type'], ['checkbox', 'number', 'money'], true)) {
                    $values[$name] = $def['default'];
                }
            }
        }
        $fromOld = isset($_SESSION['_old']);
        if ($fromOld) {
            $old = $_SESSION['_old'];
            foreach ($ent['fields'] as $name => $def) {
                if ($def['type'] === 'checkbox') {
                    $old[$name] = isset($old[$name]) ? 1 : 0;
                } elseif (in_array($def['type'], ['image', 'file'], true)) {
                    unset($old[$name]);
                }
            }
            $values = array_merge($values, $old);
            clear_old();
        }
        admin_render('form', [
            'title' => ($row ? 'Modifier ' : 'Ajouter ') . $ent['singular'],
            'key' => $key, 'ent' => $ent, 'row' => $row, 'values' => $values, 'fromOld' => $fromOld,
            'images' => (!empty($ent['gallery']) && $row) ? fetch_all('SELECT * FROM ' . gallery_conf($ent)['table'] . ' WHERE ' . gallery_conf($ent)['fk'] . ' = ? ORDER BY sort_order, id', [$row['id']]) : [],
            'gallery' => !empty($ent['gallery']) ? gallery_conf($ent) : null,
            'updates' => ($key === 'projects' && $row) ? fetch_all('SELECT u.*, (SELECT COUNT(*) FROM update_images i WHERE i.update_id = u.id) AS photos FROM project_updates u WHERE u.project_id = ? ORDER BY update_date DESC, id DESC', [$row['id']]) : [],
            'nav' => $key,
        ]);
        return;
    }

    $where = [];
    $params = [];
    $search = trim((string) ($_GET['q'] ?? ''));
    if ($search !== '' && !empty($ent['search'])) {
        $where[] = '(' . implode(' OR ', array_map(fn ($c) => "$c LIKE ?", $ent['search'])) . ')';
        foreach ($ent['search'] as $_) {
            $params[] = '%' . $search . '%';
        }
    }
    $filter = (string) ($_GET['f'] ?? '');
    if (!empty($ent['filter']) && isset($ent['filter'][1][$filter])) {
        $where[] = $ent['filter'][0] . ' = ?';
        $params[] = $filter;
    }
    $sqlWhere = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $total = (int) fetch_value("SELECT COUNT(*) FROM {$ent['table']}$sqlWhere", $params);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $offset = ($page - 1) * ADMIN_PER_PAGE;
    $rows = fetch_all("SELECT * FROM {$ent['table']}$sqlWhere ORDER BY {$ent['order']} LIMIT " . ADMIN_PER_PAGE . " OFFSET $offset", $params);

    $options = [];
    foreach ($ent['list'] as $col => $_) {
        if (($ent['fields'][$col]['type'] ?? '') === 'select') {
            $options[$col] = field_options($ent['fields'][$col]);
        }
    }
    admin_render('list', [
        'title' => $ent['label'], 'key' => $key, 'ent' => $ent, 'rows' => $rows, 'options' => $options,
        'total' => $total, 'page' => $page, 'pages' => (int) ceil($total / ADMIN_PER_PAGE),
        'search' => $search, 'filter' => $filter, 'nav' => $key,
    ]);
}

function crud_save(string $key, array $ent, int $id): never
{
    $table = $ent['table'];
    $row = $id ? fetch_one("SELECT * FROM $table WHERE id = ?", [$id]) : null;
    if ($id && !$row) {
        admin_not_found();
    }
    $data = [];
    $errors = [];
    $newUploads = [];
    $oldFiles = [];

    foreach ($ent['fields'] as $name => $def) {
        $label = $def['label'];
        $raw = $_POST[$name] ?? null;
        if ($def['type'] === 'heading') {
            continue;
        }
        switch ($def['type']) {
            case 'checkbox':
                $data[$name] = isset($_POST[$name]) ? 1 : 0;
                break;
            case 'image':
                try {
                    $up = upload_image($_FILES[$name] ?? null, $def['dir']);
                } catch (RuntimeException $ex) {
                    $errors[] = "$label : " . $ex->getMessage();
                    break;
                }
                if ($up) {
                    $data[$name] = $up;
                    $newUploads[] = $up;
                    $oldFiles[] = $row[$name] ?? null;
                } elseif (!empty($_POST[$name . '_remove'])) {
                    $data[$name] = null;
                    $oldFiles[] = $row[$name] ?? null;
                }
                break;
            case 'file':
                try {
                    $up = upload_document($_FILES[$name] ?? null, $def['dir']);
                } catch (RuntimeException $ex) {
                    $errors[] = "$label : " . $ex->getMessage();
                    break;
                }
                if ($up) {
                    $data[$name] = $up;
                    $newUploads[] = $up;
                    $oldFiles[] = $row[$name] ?? null;
                } elseif (!empty($_POST[$name . '_remove'])) {
                    $data[$name] = null;
                    $oldFiles[] = $row[$name] ?? null;
                }
                break;
            case 'money':
            case 'number':
                $v = str_replace([' ', "\u{a0}", "\u{202f}", ','], ['', '', '', '.'], trim((string) $raw));
                if ($v === '') {
                    $data[$name] = $def['type'] === 'number' ? (int) ($def['default'] ?? 0) : null;
                } elseif (!is_numeric($v)) {
                    $errors[] = "$label : nombre invalide.";
                } else {
                    $data[$name] = $def['type'] === 'number' ? (int) $v : round((float) $v, 2);
                }
                break;
            case 'date':
                $v = trim((string) $raw);
                if ($v !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
                    $errors[] = "$label : date invalide.";
                }
                $data[$name] = $v === '' ? null : $v;
                break;
            case 'select':
                $opts = field_options($def);
                $v = (string) $raw;
                if ($v === '' && isset($def['empty'])) {
                    $data[$name] = null;
                } elseif (array_key_exists($v, $opts)) {
                    $data[$name] = isset($def['options_sql']) ? (int) $v : $v;
                } else {
                    $data[$name] = array_key_first($opts);
                }
                break;
            case 'slug':
                break;
            case 'email':
            case 'url':
                $v = trim((string) $raw);
                $ok = $def['type'] === 'email' ? filter_var($v, FILTER_VALIDATE_EMAIL) : (filter_var($v, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $v));
                if ($v !== '' && !$ok) {
                    $errors[] = "$label : adresse invalide.";
                }
                $data[$name] = $v === '' ? null : $v;
                break;
            default:
                $v = trim((string) $raw);
                if (!empty($def['uppercase'])) {
                    $v = mb_strtoupper(preg_replace('/\s+/', '-', $v));
                }
                if (!empty($def['maxlength']) && mb_strlen($v) > $def['maxlength']) {
                    $errors[] = "$label : {$def['maxlength']} caractères maximum.";
                }
                $data[$name] = $v === '' ? null : $v;
        }
        if (!empty($def['required']) && trim((string) ($data[$name] ?? '')) === '') {
            $errors[] = "$label : champ obligatoire.";
        }
    }

    foreach ($ent['fields'] as $name => $def) {
        if ($def['type'] === 'slug') {
            $base = slugify(trim((string) ($_POST[$name] ?? '')) ?: (string) ($data[$def['from']] ?? ''));
            $slug = $base;
            for ($i = 2; fetch_value("SELECT COUNT(*) FROM $table WHERE $name = ? AND id <> ?", [$slug, $id]); $i++) {
                $slug = $base . '-' . $i;
            }
            $data[$name] = $slug;
        }
    }

    foreach ($ent['unique'] ?? [] as $col => $message) {
        if (!empty($data[$col]) && fetch_value("SELECT COUNT(*) FROM $table WHERE $col = ? AND id <> ?", [$data[$col], $id])) {
            $errors[] = $message;
        }
    }

    $formUrl = admin_url($key, $id ? ['a' => 'edit', 'id' => $id] : ['a' => 'new']);
    if ($errors) {
        array_map('delete_upload', $newUploads);
        foreach ($errors as $err) {
            flash('error', $err);
        }
        $_SESSION['_old'] = array_diff_key($_POST, ['_csrf' => 1]);
        redirect($formUrl);
    }

    if ($row) {
        $data['updated_at'] = now();
        $sets = implode(', ', array_map(fn ($c) => "$c = ?", array_keys($data)));
        q("UPDATE $table SET $sets WHERE id = ?", [...array_values($data), $id]);
    } else {
        $data['created_at'] = now();
        $cols = implode(', ', array_keys($data));
        $marks = implode(', ', array_fill(0, count($data), '?'));
        q("INSERT INTO $table ($cols) VALUES ($marks)", array_values($data));
        $id = (int) db()->lastInsertId();
    }
    array_map('delete_upload', array_filter($oldFiles));

    if ($table === 'projects' && empty($data['code'])) {
        q('UPDATE projects SET code = ? WHERE id = ?', [default_project_code($id), $id]);
    }
    if (!empty($ent['gallery'])) {
        save_gallery($id, gallery_conf($ent));
    }
    flash('success', 'Enregistré avec succès.');
    redirect(admin_url($key, ['a' => 'edit', 'id' => $id]));
}

/** Réglages de la galerie d'une entité (« true » = galerie photos d'un projet). */
function gallery_conf(array $ent): array
{
    $g = is_array($ent['gallery']) ? $ent['gallery'] : [];
    return $g + ['table' => 'project_images', 'fk' => 'project_id', 'dir' => 'projects', 'caption_required' => false, 'caption_en' => false,
        'legend' => 'Galerie photos', 'help' => ''];
}

function save_gallery(int $parentId, array $g): void
{
    $t = $g['table'];
    $fk = $g['fk'];
    $remove = array_map('intval', (array) ($_POST['gallery_delete'] ?? []));
    foreach ($remove as $imgId) {
        $img = fetch_one("SELECT * FROM $t WHERE id = ? AND $fk = ?", [$imgId, $parentId]);
        if ($img) {
            delete_upload($img['path']);
            q("DELETE FROM $t WHERE id = ?", [$imgId]);
        }
    }
    foreach ((array) ($_POST['gallery_caption'] ?? []) as $imgId => $caption) {
        $order = (int) ($_POST['gallery_order'][$imgId] ?? 0);
        q("UPDATE $t SET caption = ?, sort_order = ? WHERE id = ? AND $fk = ?",
            [mb_substr(trim((string) $caption), 0, 255) ?: null, $order, (int) $imgId, $parentId]);
        if ($g['caption_en']) {
            q("UPDATE $t SET caption_en = ? WHERE id = ? AND $fk = ?", [mb_substr(trim((string) ($_POST['gallery_caption_en'][$imgId] ?? '')), 0, 255) ?: null, (int) $imgId, $parentId]);
        }
    }
    $next = (int) fetch_value("SELECT COALESCE(MAX(sort_order), 0) FROM $t WHERE $fk = ?", [$parentId]);
    foreach (normalize_files($_FILES['gallery'] ?? null) as $file) {
        try {
            $path = upload_image($file, $g['dir']);
            if ($path) {
                q("INSERT INTO $t ($fk, path, sort_order) VALUES (?, ?, ?)", [$parentId, $path, ++$next]);
            }
        } catch (RuntimeException $ex) {
            flash('error', 'Photos — ' . $file['name'] . ' : ' . $ex->getMessage());
        }
    }
    if ($g['caption_required']) {
        $missing = (int) fetch_value("SELECT COUNT(*) FROM $t WHERE $fk = ? AND (caption IS NULL OR caption = '')", [$parentId]);
        if ($missing) {
            flash('error', $missing . ' photo(s) sans légende : ajoutez un sous-titre à chaque photo puis enregistrez.');
        }
    }
}

function crud_delete(string $key, array $ent, int $id): never
{
    $row = fetch_one("SELECT * FROM {$ent['table']} WHERE id = ?", [$id]);
    if ($row) {
        foreach ($ent['fields'] as $name => $def) {
            if (in_array($def['type'], ['image', 'file'], true)) {
                delete_upload($row[$name]);
            }
        }
        if (!empty($ent['gallery'])) {
            $g = gallery_conf($ent);
            foreach (fetch_all("SELECT path FROM {$g['table']} WHERE {$g['fk']} = ?", [$id]) as $img) {
                delete_upload($img['path']);
            }
            q("DELETE FROM {$g['table']} WHERE {$g['fk']} = ?", [$id]);
        }
        if ($ent['table'] === 'projects') {
            foreach (fetch_all('SELECT i.path FROM update_images i JOIN project_updates u ON u.id = i.update_id WHERE u.project_id = ?', [$id]) as $img) {
                delete_upload($img['path']);
            }
            q('DELETE FROM update_images WHERE update_id IN (SELECT id FROM project_updates WHERE project_id = ?)', [$id]);
            q('DELETE FROM project_updates WHERE project_id = ?', [$id]);
        }
        q("DELETE FROM {$ent['table']} WHERE id = ?", [$id]);
        flash('success', 'Élément supprimé.');
    }
    redirect(admin_url($key));
}

/* ---------- Paramètres ---------- */

function admin_settings(): void
{
    $schema = settings_schema();
    $tab = (string) ($_GET['tab'] ?? 'general');
    if (!isset($schema[$tab])) {
        $tab = 'general';
    }
    if (is_post()) {
        csrf_verify();
        $errors = 0;
        foreach ($schema[$tab]['fields'] as $name => $def) {
            if ($def['type'] === 'image') {
                try {
                    $up = upload_image($_FILES[$name] ?? null, $def['dir']);
                } catch (RuntimeException $ex) {
                    flash('error', $def['label'] . ' : ' . $ex->getMessage());
                    $errors++;
                    continue;
                }
                if ($up || !empty($_POST[$name . '_remove'])) {
                    delete_upload(setting($name));
                    save_setting($name, $up ?: '');
                }
                continue;
            }
            if ($def['type'] === 'heading') {
                continue;
            }
            if ($def['type'] === 'checkbox') {
                // « 0 » explicite : une valeur vide reprendrait la valeur par défaut (activé)
                save_setting($name, empty($_POST[$name]) ? '0' : '1');
                continue;
            }
            $v = trim(str_replace("\r\n", "\n", (string) ($_POST[$name] ?? '')));
            if ($v !== '' && $def['type'] === 'url' && !(filter_var($v, FILTER_VALIDATE_URL) && preg_match('#^https?://#i', $v))) {
                flash('error', $def['label'] . ' : adresse web invalide (doit commencer par https://).');
                $errors++;
                continue;
            }
            if ($v !== '' && $def['type'] === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                flash('error', $def['label'] . ' : e-mail invalide.');
                $errors++;
                continue;
            }
            save_setting($name, $v);
        }
        if (!$errors) {
            flash('success', 'Paramètres enregistrés.');
        }
        redirect(admin_url('settings', ['tab' => $tab]));
    }
    admin_render('settings', ['title' => 'Paramètres du site', 'schema' => $schema, 'tab' => $tab, 'values' => settings(), 'nav' => 'settings']);
}

/* ---------- Messages ---------- */

function admin_messages(string $a): void
{
    $id = (int) ($_GET['id'] ?? 0);
    if (is_post()) {
        csrf_verify();
        $msg = fetch_one('SELECT * FROM messages WHERE id = ?', [$id]);
        if ($msg && $a === 'delete') {
            q('DELETE FROM messages WHERE id = ?', [$id]);
            flash('success', 'Message supprimé.');
            redirect(admin_url('messages'));
        }
        if ($msg && $a === 'unread') {
            q('UPDATE messages SET is_read = 0 WHERE id = ?', [$id]);
            redirect(admin_url('messages'));
        }
        if ($msg && $a === 'to_donor') {
            q('INSERT INTO donors (name, donor_type, country, amount, project_id, message, donated_on, is_published, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)',
                [$msg['name'], 'individual', $msg['country'], $msg['amount'], $msg['project_id'], null, substr($msg['created_at'], 0, 10), now()]);
            flash('success', 'Donateur créé (non publié). Vérifiez les informations puis cochez « Afficher sur le mur des donateurs ».');
            redirect(admin_url('donors', ['a' => 'edit', 'id' => (int) db()->lastInsertId()]));
        }
        redirect(admin_url('messages'));
    }
    if ($a === 'view') {
        $msg = fetch_one('SELECT m.*, p.title AS project_title FROM messages m LEFT JOIN projects p ON p.id = m.project_id WHERE m.id = ?', [$id]);
        if (!$msg) {
            admin_not_found();
        }
        if (!$msg['is_read']) {
            q('UPDATE messages SET is_read = 1 WHERE id = ?', [$id]);
        }
        admin_render('message', ['title' => 'Message de ' . $msg['name'], 'msg' => $msg, 'nav' => 'messages']);
        return;
    }
    $kind = in_array($_GET['kind'] ?? '', ['pledge', 'contact'], true) ? $_GET['kind'] : '';
    $where = $kind ? ' WHERE m.kind = ?' : '';
    $params = $kind ? [$kind] : [];
    $total = (int) fetch_value('SELECT COUNT(*) FROM messages m' . $where, $params);
    $page = max(1, (int) ($_GET['page'] ?? 1));
    $rows = fetch_all('SELECT m.*, p.title AS project_title FROM messages m LEFT JOIN projects p ON p.id = m.project_id' . $where
        . ' ORDER BY m.created_at DESC, m.id DESC LIMIT ' . ADMIN_PER_PAGE . ' OFFSET ' . (($page - 1) * ADMIN_PER_PAGE), $params);
    admin_render('messages', ['title' => 'Messages reçus', 'rows' => $rows, 'kind' => $kind, 'total' => $total, 'page' => $page,
        'pages' => (int) ceil($total / ADMIN_PER_PAGE), 'nav' => 'messages']);
}

/* ---------- Administrateurs ---------- */

function admin_users(string $a): void
{
    $me = admin_user();
    if (is_post()) {
        csrf_verify();
        if ($a === 'create') {
            $name = trim((string) ($_POST['name'] ?? ''));
            $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
            $pass = (string) ($_POST['password'] ?? '');
            if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) {
                flash('error', 'Nom, e-mail valide et mot de passe de 10 caractères minimum requis.');
            } elseif (fetch_value('SELECT COUNT(*) FROM admins WHERE LOWER(email) = ?', [$email])) {
                flash('error', 'Cet e-mail est déjà utilisé.');
            } else {
                q('INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT), now()]);
                flash('success', "Compte créé pour $email.");
            }
        }
        if ($a === 'delete') {
            $id = (int) ($_GET['id'] ?? 0);
            if ($id === (int) $me['id']) {
                flash('error', 'Vous ne pouvez pas supprimer votre propre compte.');
            } else {
                q('DELETE FROM admins WHERE id = ?', [$id]);
                flash('success', 'Compte supprimé.');
            }
        }
        redirect(admin_url('users'));
    }
    admin_render('users', ['title' => 'Administrateurs', 'users' => fetch_all('SELECT id, name, email, last_login_at, created_at FROM admins ORDER BY name'), 'me' => $me, 'nav' => 'users']);
}

function admin_account(): void
{
    $me = admin_user();
    if (is_post()) {
        csrf_verify();
        $full = fetch_one('SELECT * FROM admins WHERE id = ?', [$me['id']]);
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = mb_strtolower(trim((string) ($_POST['email'] ?? '')));
        $new = (string) ($_POST['new_password'] ?? '');
        if (!password_verify((string) ($_POST['current_password'] ?? ''), $full['password_hash'])) {
            flash('error', 'Mot de passe actuel incorrect.');
        } elseif ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Nom et e-mail valides requis.');
        } elseif (fetch_value('SELECT COUNT(*) FROM admins WHERE LOWER(email) = ? AND id <> ?', [$email, $me['id']])) {
            flash('error', 'Cet e-mail est déjà utilisé par un autre compte.');
        } elseif ($new !== '' && mb_strlen($new) < 10) {
            flash('error', 'Le nouveau mot de passe doit contenir au moins 10 caractères.');
        } elseif ($new !== '' && $new !== ($_POST['confirm_password'] ?? '')) {
            flash('error', 'La confirmation ne correspond pas au nouveau mot de passe.');
        } else {
            q('UPDATE admins SET name = ?, email = ? WHERE id = ?', [$name, $email, $me['id']]);
            if ($new !== '') {
                q('UPDATE admins SET password_hash = ? WHERE id = ?', [password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                session_regenerate_id(true);
            }
            flash('success', 'Compte mis à jour.');
        }
        redirect(admin_url('account'));
    }
    admin_render('account', ['title' => 'Mon compte', 'me' => $me, 'nav' => 'account']);
}
