<?php
declare(strict_types=1);

const STATUS_FILTERS = ['ongoing', 'upcoming', 'completed'];
// Anciennes valeurs françaises des filtres (liens déjà partagés).
const LEGACY_STATUS = ['en-cours' => 'ongoing', 'a-venir' => 'upcoming', 'acheves' => 'completed'];

function route_public(string $path): void
{
    // Fichiers techniques générés : sitemap et robots
    if ($path === 'sitemap.xml') {
        serve_sitemap();
        return;
    }
    if ($path === 'robots.txt') {
        serve_robots();
        return;
    }
    $segments = $path === '' ? [] : explode('/', $path);

    // Langue : /fr/… = français ; sans préfixe = anglais (par défaut).
    $lang = DEFAULT_LANG;
    if ($segments && isset(LANGS[$segments[0]]) && $segments[0] !== DEFAULT_LANG) {
        $lang = array_shift($segments);
    } elseif ($segments && $segments[0] === DEFAULT_LANG) {
        array_shift($segments);
        redirect(url(implode('/', $segments)) . query_suffix());
    }
    set_lang($lang);

    $slug = $segments[0] ?? '';
    $sub = $segments[1] ?? null;
    $third = $segments[2] ?? null;
    // Seule la fiche de suivi d'un projet a un 3e segment : /projects/{slug}/report.
    $report = $third !== null && in_array($third, REPORT_SEGMENT, true);
    if (count($segments) > 3 || ($third !== null && !$report)) {
        page_not_found();
        return;
    }

    foreach (ROUTES as $name => $paths) {
        if ($paths[$lang] === $slug) {
            dispatch_page($name, $sub, $report);
            return;
        }
        // Adresse d'une autre langue (ex. /a-propos sans /fr) : redirection permanente vers la bonne.
        foreach ($paths as $otherLang => $otherSlug) {
            if ($otherLang !== $lang && $otherSlug === $slug && $slug !== '') {
                header('Location: ' . route_url($name, ['lang' => $otherLang, 'slug' => $sub, 'report' => $report]) . query_suffix(), true, 301);
                exit;
            }
        }
    }
    page_not_found();
}

function query_suffix(): string
{
    $qs = (string) ($_SERVER['QUERY_STRING'] ?? '');
    return $qs === '' ? '' : '?' . $qs;
}

function dispatch_page(string $name, ?string $sub, bool $report = false): void
{
    $GLOBALS['current_route'] = ['name' => $name, 'slug' => $sub, 'report' => $report];
    // Pages de détail : /projects/{slug}[/report], /donors/{id}, /how-to-give/{id}
    if ($sub !== null) {
        $ok = match ($name) {
            'projects' => preg_match('/^[a-z0-9-]+$/', $sub) === 1,
            'donors', 'give' => ctype_digit($sub) && !$report,
            default => false,
        };
        if (!$ok) {
            page_not_found();
            return;
        }
        match ($name) {
            'projects' => $report ? page_project_report($sub) : page_project($sub),
            'donors'   => page_donor((int) $sub),
            'give'     => page_method((int) $sub),
        };
        return;
    }
    if ($report) {
        page_not_found();
        return;
    }
    match ($name) {
        'home'         => page_home(),
        'about'        => page_about(),
        'projects'     => page_projects(),
        'give'         => page_give(),
        'testimonials' => page_testimonials(),
        'donors'       => page_donors(),
        'contact'      => page_contact(),
        'privacy'      => page_legal('privacy'),
        'terms'        => page_legal('terms'),
    };
}

function published_projects(?string $status = null, int $limit = 0, bool $featuredFirst = false): array
{
    $sql = 'SELECT * FROM projects WHERE is_published = 1';
    $params = [];
    if ($status) {
        $sql .= ' AND status = ?';
        $params[] = $status;
    }
    $sql .= ' ORDER BY ' . ($featuredFirst ? 'is_featured DESC, ' : '') . 'sort_order ASC, id DESC';
    if ($limit > 0) {
        $sql .= ' LIMIT ' . $limit;
    }
    return fetch_all($sql, $params);
}

function site_stats(): array
{
    $byStatus = [];
    foreach (fetch_all('SELECT status, COUNT(*) n FROM projects WHERE is_published = 1 GROUP BY status') as $r) {
        $byStatus[$r['status']] = (int) $r['n'];
    }
    return [
        'completed' => $byStatus['completed'] ?? 0,
        'ongoing'   => $byStatus['ongoing'] ?? 0,
        'upcoming'  => $byStatus['upcoming'] ?? 0,
        'donors'    => (int) fetch_value('SELECT COUNT(*) FROM donors WHERE is_published = 1'),
        'raised'    => (float) fetch_value('SELECT COALESCE(SUM(raised_amount), 0) FROM projects WHERE is_published = 1'),
        // Total collecté, devise par devise (les projets peuvent avoir des devises différentes)
        'raised_label' => money_totals(sum_by_currency(fetch_all('SELECT raised_amount, currency FROM projects WHERE is_published = 1'), 'raised_amount')),
    ];
}

function page_home(): void
{
    render_page('home', [
        'nav'          => 'home',
        'stats'        => site_stats(),
        'featured'     => published_projects('ongoing', 1, true) ?: published_projects(null, 1, true),
        'projects'     => published_projects(null, 8, true),
        'testimonials' => fetch_all('SELECT * FROM testimonials WHERE is_published = 1 ORDER BY sort_order, id DESC LIMIT 6'),
        'donors'       => fetch_all('SELECT * FROM donors WHERE is_published = 1 ORDER BY sort_order, id DESC LIMIT 24'),
    ]);
}

function page_about(): void
{
    render_page('about', [
        'nav' => 'about', 'pageTitle' => __('À propos de nous'),
        'metaDescription' => excerpt(setting_t('about_intro'), 160),
        'stats' => site_stats(),
    ]);
}

function page_projects(): void
{
    $status = (string) ($_GET['status'] ?? '');
    $status = LEGACY_STATUS[$_GET['statut'] ?? ''] ?? $status;
    $current = in_array($status, STATUS_FILTERS, true) ? $status : null;
    render_page('projects', [
        'nav' => 'projects', 'pageTitle' => __('Nos projets'),
        'metaDescription' => __('Découvrez les projets de construction et d\'équipement de l\'UAZ : achevés, en cours et à venir.'),
        'projects' => published_projects($current),
        'filter' => $current ?? '',
        'counts' => site_stats(),
    ]);
}

/** Chiffres du tableau de bord d'un projet (financement et calendrier). */
function project_dashboard(array $p, int $donorCount): array
{
    $goal = (float) $p['goal_amount'];
    $raised = (float) $p['raised_amount'];
    $d = [
        'goal' => $goal,
        'raised' => $raised,
        'missing' => max(0, $goal - $raised),
        'pct' => percent($raised, $goal),
        'donors' => $donorCount,
        'time_pct' => null,
        'days_left' => null,
    ];
    if ($p['start_date'] && $p['end_date']) {
        $start = strtotime($p['start_date']);
        $end = strtotime($p['end_date']);
        if ($start && $end && $end > $start) {
            $d['time_pct'] = (int) max(0, min(100, round((time() - $start) / ($end - $start) * 100)));
            $d['days_left'] = (int) max(0, ceil(($end - time()) / 86400));
        }
    }
    return $d;
}

function load_published_project(string $slug): ?array
{
    return fetch_one('SELECT * FROM projects WHERE slug = ? AND is_published = 1', [$slug]);
}

function project_donors(int $projectId): array
{
    return fetch_all('SELECT * FROM donors WHERE project_id = ? AND is_published = 1 ORDER BY sort_order, id DESC', [$projectId]);
}

function page_project(string $slug): void
{
    $project = load_published_project($slug);
    if (!$project) {
        page_not_found();
        return;
    }
    $donors = project_donors((int) $project['id']);
    render_page('project', [
        'nav' => 'projects', 'pageTitle' => loc($project, 'title'),
        'metaDescription' => excerpt(loc($project, 'summary') ?: loc($project, 'description'), 160),
        'ogImage' => $project['cover_image'],
        'project' => $project,
        'dash' => project_dashboard($project, count($donors)),
        'images' => fetch_all('SELECT * FROM project_images WHERE project_id = ? ORDER BY sort_order, id', [$project['id']]),
        'donors' => $donors,
        'others' => fetch_all('SELECT * FROM projects WHERE is_published = 1 AND id <> ? AND status <> ? ORDER BY is_featured DESC, sort_order LIMIT 3', [$project['id'], 'completed']),
    ]);
}

/** Fiche de suivi : le PDF déposé dans l'admin s'il existe, sinon une fiche générée (téléchargeable en PDF). */
function page_project_report(string $slug): void
{
    $project = load_published_project($slug);
    if (!$project) {
        page_not_found();
        return;
    }
    if ($project['report_file'] && is_file(PUBLIC_DIR . '/' . $project['report_file'])) {
        $name = 'fiche-de-suivi-' . slugify((string) $project['code'] ?: $project['slug']) . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Content-Length: ' . filesize(PUBLIC_DIR . '/' . $project['report_file']));
        readfile(PUBLIC_DIR . '/' . $project['report_file']);
        exit;
    }
    $donors = project_donors((int) $project['id']);
    header('X-Robots-Tag: noindex');
    view('report', [
        'p' => $project,
        'dash' => project_dashboard($project, count($donors)),
        'donors' => $donors,
        'autoDownload' => isset($_GET['download']),
    ]);
}

function page_donor(int $id): void
{
    $donor = fetch_one('SELECT d.*, p.title AS project_title, p.title_en AS project_title_en, p.slug AS project_slug, p.code AS project_code,
        p.status AS project_status, p.cover_image AS project_cover, p.goal_amount AS project_goal, p.raised_amount AS project_raised
        FROM donors d LEFT JOIN projects p ON p.id = d.project_id AND p.is_published = 1 WHERE d.id = ? AND d.is_published = 1', [$id]);
    if (!$donor) {
        page_not_found();
        return;
    }
    render_page('donor', [
        'nav' => 'donors', 'pageTitle' => donor_name($donor),
        'metaDescription' => __('Merci à :name pour son soutien à l\'Université Adventiste Zurcher.', ['name' => donor_name($donor)]),
        'ogImage' => empty($donor['mask_name']) ? $donor['logo'] : null,
        'd' => $donor,
    ]);
}

function page_method(int $id): void
{
    $method = fetch_one('SELECT * FROM donation_methods WHERE id = ? AND is_published = 1', [$id]);
    if (!$method) {
        page_not_found();
        return;
    }
    if (is_post()) {
        handle_message_form('pledge', route_url('give', ['slug' => $id, 'hash' => 'promesse']));
    }
    render_page('method', [
        'nav' => 'give', 'pageTitle' => loc($method, 'title'),
        'metaDescription' => excerpt(loc($method, 'description'), 160),
        'ogImage' => $method['logo'],
        'm' => $method,
        'others' => fetch_all('SELECT * FROM donation_methods WHERE is_published = 1 AND id <> ? ORDER BY sort_order, id', [$id]),
        'projectOptions' => give_project_options(),
        'selected' => (string) ($_GET['project'] ?? ''),
    ]);
}

function give_project_options(): array
{
    return fetch_all("SELECT id, title, title_en, slug, code, currency FROM projects WHERE is_published = 1 AND status <> 'completed' ORDER BY sort_order, title");
}

function page_give(): void
{
    if (is_post()) {
        handle_message_form('pledge', route_url('give', ['hash' => 'promesse']));
    }
    render_page('give', [
        'nav' => 'give', 'pageTitle' => __('Comment donner'),
        'metaDescription' => __('Virement bancaire, Mobile Money, parrainage d\'étudiants : découvrez comment soutenir l\'Université Adventiste Zurcher.'),
        'methods' => fetch_all('SELECT * FROM donation_methods WHERE is_published = 1 ORDER BY sort_order, id'),
        'projectOptions' => give_project_options(),
        'selected' => (string) ($_GET['project'] ?? $_GET['projet'] ?? ''),
    ]);
}

function page_testimonials(): void
{
    render_page('testimonials', [
        'nav' => 'testimonials', 'pageTitle' => __('Témoignages'),
        'metaDescription' => __('Ils ont étudié à l\'UAZ grâce à la générosité de donateurs : découvrez leurs témoignages.'),
        'testimonials' => fetch_all('SELECT * FROM testimonials WHERE is_published = 1 ORDER BY sort_order, id DESC'),
    ]);
}

function page_donors(): void
{
    $donors = fetch_all('SELECT d.*, p.title AS project_title, p.title_en AS project_title_en, p.slug AS project_slug FROM donors d
        LEFT JOIN projects p ON p.id = d.project_id WHERE d.is_published = 1 ORDER BY d.sort_order, d.id DESC');
    $groups = [];
    foreach ($donors as $d) {
        $groups[$d['donor_type']][] = $d;
    }
    render_page('donors', [
        'nav' => 'donors', 'pageTitle' => __('Nos donateurs'),
        'metaDescription' => __('Merci à tous les donateurs qui soutiennent l\'Université Adventiste Zurcher.'),
        'groups' => $groups, 'total' => count($donors),
    ]);
}

function page_legal(string $which): void
{
    render_page('legal', [
        'nav' => $which,
        'pageTitle' => $which === 'privacy' ? __('Politique de confidentialité') : __('Conditions générales'),
        'metaDescription' => $which === 'privacy'
            ? __('Comment l\'Université Adventiste Zurcher collecte, utilise et protège vos données sur son site de dons.')
            : __('Conditions générales d\'utilisation du site de dons de l\'Université Adventiste Zurcher.'),
        'which' => $which,
    ]);
}

function page_contact(): void
{
    if (is_post()) {
        handle_message_form('contact', route_url('contact', ['hash' => 'formulaire']));
    }
    render_page('contact', [
        'nav' => 'contact', 'pageTitle' => __('Contact'),
        'metaDescription' => __('Contactez l\'Université Adventiste Zurcher pour toute question sur les dons et les projets.'),
    ]);
}

function page_not_found(): void
{
    http_response_code(404);
    render_page('404', ['pageTitle' => __('Page introuvable'), 'noindex' => true]);
}

/** Traite le formulaire de contact ou de promesse de don, puis redirige (PRG). */
function handle_message_form(string $kind, string $back): never
{
    csrf_verify();
    $in = fn (string $k, int $max = 190) => mb_substr(trim((string) ($_POST[$k] ?? '')), 0, $max);
    $data = [
        'name' => $in('name', 150), 'email' => $in('email'), 'phone' => $in('phone', 50),
        'country' => $in('country', 100), 'subject' => $in('subject', 255), 'method' => $in('method'),
        'body' => $in('body', 5000), 'amount' => $in('amount', 30), 'project_id' => $in('project_id', 10),
    ];
    $_SESSION['_old'] = $data;

    // Pot de miel anti-robots : ce champ invisible doit rester vide.
    if (($_POST['website'] ?? '') !== '') {
        redirect($back);
    }
    // Envoi trop rapide (robot) ou formulaire trop ancien : on demande de réessayer.
    if (!form_time_ok()) {
        flash('error', __('Merci de vérifier vos informations puis de renvoyer le formulaire.'));
        redirect($back);
    }
    $errors = [];
    if (mb_strlen($data['name']) < 2 || preg_match('#https?://|www\.|[<>]#i', $data['name'])) {
        $errors[] = __('Indiquez votre nom.');
    }
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL) || !preg_match('/@[^@]+\.[a-z]{2,}$/i', $data['email'])) {
        $errors[] = __('Indiquez une adresse e-mail valide.');
    }
    if ($data['phone'] !== '' && !preg_match('/^\+?[0-9 ().\/-]{6,25}$/', $data['phone'])) {
        $errors[] = __('Indiquez un numéro de téléphone valide (chiffres, espaces et + uniquement).');
    }
    if ($kind === 'contact' && mb_strlen($data['body']) < 5) {
        $errors[] = __('Écrivez votre message.');
    }
    $amount = null;
    if ($data['amount'] !== '') {
        $digits = preg_replace('/\D/', '', preg_replace('/[.,]\d{1,2}$/', '', $data['amount']));
        if ($digits === '' || !preg_match('/^[0-9 .,\x{00A0}\x{202F}]+$/u', $data['amount']) || strlen($digits) > 13 || (int) $digits <= 0) {
            $errors[] = __('Indiquez un montant valide, en chiffres (ex. 100 000).');
        } else {
            $amount = (float) $digits;
        }
    }
    if (looks_like_spam($data['body'], $data['subject'], $data['country'])) {
        $errors[] = __('Votre message contient trop de liens. Retirez-les puis renvoyez-le.');
    }
    $projectId = ctype_digit($data['project_id'])
        ? (fetch_value('SELECT id FROM projects WHERE id = ?', [(int) $data['project_id']]) ?: null)
        : null;
    $recent = (int) fetch_value('SELECT COUNT(*) FROM messages WHERE ip = ? AND created_at > ?', [client_ip(), date('Y-m-d H:i:s', time() - 3600)]);
    if ($recent >= 5) {
        $errors[] = __('Trop d\'envois depuis votre connexion. Merci de réessayer plus tard.');
    }
    if ($errors) {
        foreach ($errors as $err) {
            flash('error', $err);
        }
        redirect($back);
    }

    q('INSERT INTO messages (kind, name, email, phone, country, subject, project_id, amount, method, body, ip, created_at)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', [
        $kind, $data['name'], $data['email'], $data['phone'] ?: null, $data['country'] ?: null,
        $data['subject'] ?: null, $projectId, $amount, $data['method'] ?: null, $data['body'] ?: null, client_ip(), now(),
    ]);
    clear_old();

    $label = $kind === 'pledge' ? 'Nouvelle promesse de don' : 'Nouveau message de contact';
    notify_admin($label . ' — ' . $data['name'], "Nom : {$data['name']}\nE-mail : {$data['email']}\nTéléphone : {$data['phone']}\n"
        . 'Langue du site : ' . LANGS[lang()] . "\n"
        . ($amount ? 'Montant : ' . money($amount, $projectId ? currency_for_project($projectId) : null) . "\n" : '') . "\n{$data['body']}\n\n" . absolute_url('admin/?p=messages'));

    flash('success', $kind === 'pledge'
        ? __('Merci pour votre générosité ! Nous avons bien reçu votre promesse de don et vous contacterons rapidement.')
        : __('Merci ! Votre message a bien été envoyé. Nous vous répondrons dans les meilleurs délais.'));
    redirect($back);
}

/* ---------- Référencement : sitemap.xml et robots.txt ---------- */

/** Sitemap de toutes les pages publiques, dans les deux langues, avec leurs équivalents (hreflang). */
function serve_sitemap(): never
{
    $site = rtrim((string) config('site_url', ''), '/');
    $pages = [];
    foreach (['home', 'projects', 'give', 'donors', 'testimonials', 'about', 'contact', 'privacy', 'terms'] as $name) {
        $pages[] = ['name' => $name, 'slug' => null, 'lastmod' => null, 'priority' => $name === 'home' ? '1.0' : (in_array($name, ['privacy', 'terms'], true) ? '0.3' : '0.8')];
    }
    foreach (fetch_all('SELECT slug, COALESCE(updated_at, created_at) AS d FROM projects WHERE is_published = 1') as $p) {
        $pages[] = ['name' => 'projects', 'slug' => $p['slug'], 'lastmod' => substr((string) $p['d'], 0, 10), 'priority' => '0.9'];
    }
    foreach (fetch_all('SELECT id FROM donation_methods WHERE is_published = 1') as $m) {
        $pages[] = ['name' => 'give', 'slug' => (string) $m['id'], 'lastmod' => null, 'priority' => '0.7'];
    }
    foreach (fetch_all('SELECT id FROM donors WHERE is_published = 1') as $d) {
        $pages[] = ['name' => 'donors', 'slug' => (string) $d['id'], 'lastmod' => null, 'priority' => '0.4'];
    }
    header('Content-Type: application/xml; charset=UTF-8');
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";
    foreach ($pages as $pg) {
        foreach (array_keys(LANGS) as $lang) {
            echo "  <url>\n    <loc>" . e($site . route_url($pg['name'], ['slug' => $pg['slug'], 'lang' => $lang])) . "</loc>\n";
            foreach (array_keys(LANGS) as $alt) {
                echo '    <xhtml:link rel="alternate" hreflang="' . $alt . '" href="' . e($site . route_url($pg['name'], ['slug' => $pg['slug'], 'lang' => $alt])) . "\"/>\n";
            }
            if ($pg['lastmod']) {
                echo '    <lastmod>' . e($pg['lastmod']) . "</lastmod>\n";
            }
            echo '    <priority>' . $pg['priority'] . "</priority>\n  </url>\n";
        }
    }
    echo "</urlset>\n";
    exit;
}

function serve_robots(): never
{
    $site = rtrim((string) config('site_url', ''), '/');
    header('Content-Type: text/plain; charset=UTF-8');
    echo "User-agent: *\n";
    echo "Allow: /\n";
    echo "Disallow: /admin/\n";
    echo "Disallow: /*/report\n";
    echo "Disallow: /*/fiche-de-suivi\n";
    echo "Disallow: /*?project=\n";
    echo "\nSitemap: {$site}/sitemap.xml\n";
    exit;
}
