<?php
declare(strict_types=1);

/*
 * Bilinguisme du site public : anglais (langue par défaut, sans préfixe d'URL) et français (/fr/…).
 * - Les textes de l'interface sont écrits en français dans les gabarits et traduits via __() à partir
 *   du dictionnaire app/lang/en.php (un texte absent du dictionnaire reste en français).
 * - Les contenus saisis dans l'admin ont une version française (colonne / paramètre « x ») et
 *   anglaise (« x_en ») ; si la version anglaise est vide, la française est affichée.
 */

const LANGS = ['en' => 'English', 'fr' => 'Français'];
const DEFAULT_LANG = 'en';

/** Adresse de chaque page dans chaque langue. */
const ROUTES = [
    'home'         => ['en' => '', 'fr' => ''],
    'about'        => ['en' => 'about', 'fr' => 'a-propos'],
    'projects'     => ['en' => 'projects', 'fr' => 'projets'],
    'give'         => ['en' => 'how-to-give', 'fr' => 'comment-donner'],
    'testimonials' => ['en' => 'testimonials', 'fr' => 'temoignages'],
    'donors'       => ['en' => 'donors', 'fr' => 'donateurs'],
    'contact'      => ['en' => 'contact', 'fr' => 'contact'],
    'privacy'      => ['en' => 'privacy-policy', 'fr' => 'politique-de-confidentialite'],
    'terms'        => ['en' => 'terms', 'fr' => 'conditions-generales'],
    'donate'       => ['en' => 'donate', 'fr' => 'faire-un-don'],
];

/** Dernier segment de l'adresse de la fiche de suivi d'un projet. */
const REPORT_SEGMENT = ['en' => 'report', 'fr' => 'fiche-de-suivi'];

function lang(): string
{
    return $GLOBALS['lang'] ?? DEFAULT_LANG;
}

function set_lang(string $lang): void
{
    $GLOBALS['lang'] = isset(LANGS[$lang]) ? $lang : DEFAULT_LANG;
}

/** Traduit un texte d'interface (source en français). Variables : __('Il reste :n', ['n' => 3]). */
function __(string $fr, array $vars = []): string
{
    static $dict = null;
    $text = $fr;
    if (lang() === 'en') {
        $dict ??= require APP . '/lang/en.php';
        $text = $dict[$fr] ?? $fr;
    }
    foreach ($vars as $k => $v) {
        $text = str_replace(':' . $k, (string) $v, $text);
    }
    return $text;
}

/** Champ d'un contenu dans la langue courante, avec repli sur le français. */
function loc(array $row, string $field): string
{
    if (lang() === 'en') {
        $en = trim((string) ($row[$field . '_en'] ?? ''));
        if ($en !== '') {
            return $en;
        }
    }
    return (string) ($row[$field] ?? '');
}

/** Paramètre du site dans la langue courante, avec repli sur le français. */
function setting_t(string $key, string $default = ''): string
{
    if (lang() === 'en') {
        $en = setting($key . '_en');
        if ($en !== '') {
            return $en;
        }
    }
    return setting($key, $default);
}

/**
 * URL d'une page du site public.
 * Options : slug (fiche projet), query (tableau), hash, lang (par défaut : langue courante).
 */
function route_url(string $name, array $opts = []): string
{
    $lang = $opts['lang'] ?? lang();
    $path = ROUTES[$name][$lang] ?? '';
    if (!empty($opts['slug'])) {
        $path .= '/' . $opts['slug'];
        if (!empty($opts['report'])) {
            $path .= '/' . REPORT_SEGMENT[$lang];
        }
    }
    $prefix = $lang === DEFAULT_LANG ? '' : $lang . '/';
    $url = url(rtrim($prefix . $path, '/'));
    if (!empty($opts['query'])) {
        $url .= '?' . http_build_query($opts['query']);
    }
    if (!empty($opts['hash'])) {
        $url .= '#' . $opts['hash'];
    }
    return $url;
}

/** URL de la page courante dans une autre langue (sélecteur de langue, balises hreflang). */
function current_url_in(string $lang): string
{
    $r = $GLOBALS['current_route'] ?? ['name' => 'home'];
    $query = array_intersect_key($_GET, array_flip(['status', 'project']));
    return route_url($r['name'], ['slug' => $r['slug'] ?? null, 'report' => !empty($r['report']), 'query' => $query, 'lang' => $lang]);
}

/** Libellés traduits des statuts et types. */
function status_label(string $status): string
{
    return __(PROJECT_STATUSES[$status] ?? $status);
}

function donor_type_label(string $type): string
{
    return __(DONOR_TYPES[$type] ?? '');
}

/** Bouton principal d'un projet selon son statut. */
function project_cta_label(string $status): string
{
    return match ($status) {
        'ongoing'   => __('Voir l\'avancement'),
        'completed' => __('Consulter le bilan'),
        default     => __('En savoir plus'),
    };
}
