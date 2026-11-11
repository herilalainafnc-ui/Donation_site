<?php
/**
 * Installation : crée les tables, les paramètres par défaut, un compte administrateur
 * et (optionnellement) du contenu d'exemple.
 *
 *   php install/install.php
 *   php install/install.php --email=admin@zurcher.edu.mg --name="Admin UAZ" --password="..." [--no-demo]
 */
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('À lancer en ligne de commande uniquement.');
}

require dirname(__DIR__) . '/app/bootstrap.php';

$opts = getopt('', ['email::', 'name::', 'password::', 'no-demo']);

function ask(string $label, bool $hidden = false): string
{
    echo $label . ' : ';
    if ($hidden && DIRECTORY_SEPARATOR === '/') {
        system('stty -echo');
        $v = trim((string) fgets(STDIN));
        system('stty echo');
        echo PHP_EOL;
        return $v;
    }
    return trim((string) fgets(STDIN));
}

$driver = config('db')['driver'] ?? 'mysql';
if ($driver === 'sqlite') {
    $dir = dirname(config('db')['sqlite_path']);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

echo "→ Création des tables ($driver)…" . PHP_EOL;
$sql = file_get_contents(__DIR__ . '/schema.' . ($driver === 'sqlite' ? 'sqlite' : 'mysql') . '.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
    db()->exec($statement);
}

// Mises à jour des bases déjà installées : colonnes ajoutées après la première version.
$newColumns = [
    ['donors', 'mask_name', 'TINYINT(1) NOT NULL DEFAULT 0', 'INTEGER NOT NULL DEFAULT 0'],
    // Versions anglaises des contenus (le site est bilingue : anglais par défaut, français).
    ['projects', 'title_en', 'VARCHAR(255) NULL', 'TEXT'],
    ['projects', 'summary_en', 'TEXT NULL', 'TEXT'],
    ['projects', 'description_en', 'MEDIUMTEXT NULL', 'TEXT'],
    ['projects', 'location_en', 'VARCHAR(255) NULL', 'TEXT'],
    ['testimonials', 'role_en', 'VARCHAR(190) NULL', 'TEXT'],
    ['testimonials', 'quote_en', 'TEXT NULL', 'TEXT'],
    ['donation_methods', 'title_en', 'VARCHAR(190) NULL', 'TEXT'],
    ['donation_methods', 'description_en', 'TEXT NULL', 'TEXT'],
    ['donation_methods', 'details_en', 'TEXT NULL', 'TEXT'],
    ['donors', 'message_en', 'TEXT NULL', 'TEXT'],
    // Code projet et fiche de suivi (PDF facultatif)
    ['projects', 'code', 'VARCHAR(30) NULL', 'TEXT'],
    ['projects', 'report_file', 'VARCHAR(255) NULL', 'TEXT'],
    // Coordonnées des donateurs (privées sauf si « afficher les coordonnées » est activé)
    ['donors', 'email', 'VARCHAR(190) NULL', 'TEXT'],
    ['donors', 'phone', 'VARCHAR(50) NULL', 'TEXT'],
    ['donors', 'whatsapp', 'VARCHAR(50) NULL', 'TEXT'],
    ['donors', 'show_contact', 'TINYINT(1) NOT NULL DEFAULT 0', 'INTEGER NOT NULL DEFAULT 0'],
    // Moyens de don : logo, lien de paiement, instructions détaillées
    ['donation_methods', 'logo', 'VARCHAR(255) NULL', 'TEXT'],
    ['donation_methods', 'payment_url', 'VARCHAR(255) NULL', 'TEXT'],
    ['donation_methods', 'payment_label', 'VARCHAR(100) NULL', 'TEXT'],
    ['donation_methods', 'payment_label_en', 'VARCHAR(100) NULL', 'TEXT'],
    ['donation_methods', 'instructions', 'TEXT NULL', 'TEXT'],
    ['donation_methods', 'instructions_en', 'TEXT NULL', 'TEXT'],
];
foreach ($newColumns as [$table, $column, $mysqlDef, $sqliteDef]) {
    try {
        db()->query("SELECT $column FROM $table LIMIT 1");
    } catch (PDOException) {
        db()->exec("ALTER TABLE $table ADD COLUMN $column " . ($driver === 'sqlite' ? $sqliteDef : $mysqlDef));
        echo "  Colonne $table.$column ajoutée." . PHP_EOL;
    }
}

echo '→ Paramètres par défaut…' . PHP_EOL;
$defaults = [
    'site_name'         => 'Université Adventiste Zurcher',
    'site_short'        => 'UAZ',
    'tagline'           => 'Ensemble, bâtissons l\'avenir de nos étudiants',
    'meta_description'  => 'Soutenez les projets de construction et d\'équipement de l\'Université Adventiste Zurcher (UAZ) à Madagascar, et offrez un avenir meilleur à ses étudiants.',
    'currency'          => 'Ar',
    'logo'              => '',
    'online_donation_url'   => '',
    'online_donation_label' => 'Donner en ligne',

    'hero_kicker'   => 'Campagne de dons UAZ',
    'hero_title'    => 'Chaque don construit **l\'avenir** d\'un étudiant',
    'hero_subtitle' => 'Salles de classe, résidences, bibliothèque, équipements : aidez l\'Université Adventiste Zurcher à offrir à chaque étudiant un lieu digne pour apprendre, grandir et servir.',
    'hero_image'    => '',
    'about_teaser'  => "L'Université Adventiste Zurcher est un établissement d'enseignement supérieur de l'Église adventiste du septième jour, situé à Sambaina, près d'Antsirabe, à Madagascar.\n\nNous formons des femmes et des hommes compétents, intègres et engagés au service de Dieu et de leur communauté, dans une vision d'éducation holistique : l'esprit, le corps et l'âme.",
    'quote_text'    => 'Que chacun donne comme il l\'a résolu en son cœur, sans tristesse ni contrainte ; car Dieu aime celui qui donne avec joie.',
    'quote_author'  => '2 Corinthiens 9:7',
    'cta_title'     => 'Prêt à faire **la différence** ?',
    'cta_text'      => 'Votre générosité, même modeste, se transforme en briques, en livres et en bourses pour nos étudiants.',

    'about_intro'   => "L'Université Adventiste Zurcher (UAZ) est une institution d'enseignement supérieur de l'Église adventiste du septième jour à Madagascar. Située à Sambaina, au PK 135 de la RN7, près d'Antsirabe, elle accueille des étudiants venus de toute l'île et d'ailleurs.",
    'about_history' => "Complétez ici l'histoire de l'université : sa fondation, ses grandes étapes, ses facultés et ses réussites.\n\nCe texte se modifie depuis l'espace d'administration, rubrique « Paramètres → À propos ».",
    'about_mission' => 'Soutenir l\'éducation et donner à chaque étudiant la possibilité de développer tout son potentiel, en formant les leaders de demain dans le respect des valeurs chrétiennes.',
    'about_vision'  => 'Être une université de référence offrant un développement holistique — intellectuel, physique, social et spirituel — à chaque étudiant.',
    'about_values'  => "- **Excellence** académique et professionnelle\n- **Intégrité** et transparence\n- **Service** de Dieu et de la communauté\n- **Foi** et valeurs chrétiennes",
    'about_image'   => '',

    'give_intro'    => 'Il existe plusieurs façons de soutenir l\'UAZ. Choisissez celle qui vous convient : chaque contribution est utilisée avec rigueur et transparence pour le projet que vous désignez.',
    'give_after'    => 'Après votre don, remplissez le formulaire ci-dessous afin que nous puissions vous remercier, vous envoyer un reçu et affecter votre don au bon projet.',

    'contact_address'  => "Université Adventiste Zurcher\nB.P. 325, PK 135 RN7\nAntsirabe 2 — Madagascar",
    'contact_email'    => '',
    'contact_phone'    => '',
    'contact_whatsapp' => '',
    'office_hours'     => 'Du lundi au jeudi, 8 h – 17 h ; vendredi, 8 h – 12 h',
    'website_url'      => 'https://zurcher.edu.mg',
    'facebook_url'     => '',
    'youtube_url'      => '',
    'map_embed_url'    => '',
    'notify_email'     => '',

    // Versions anglaises (langue par défaut du site public)
    'site_name_en'        => 'Zurcher Adventist University',
    'tagline_en'          => 'Together, let\'s build our students\' future',
    'meta_description_en' => 'Support the construction and equipment projects of Zurcher Adventist University (UAZ) in Madagascar and give its students a better future.',
    'online_donation_label_en' => 'Give online',
    'hero_kicker_en'   => 'UAZ giving campaign',
    'hero_title_en'    => 'Every gift builds a student\'s **future**',
    'hero_subtitle_en' => 'Classrooms, residences, library, equipment: help Zurcher Adventist University give every student a worthy place to learn, grow and serve.',
    'about_teaser_en'  => "Zurcher Adventist University is a higher-education institution of the Seventh-day Adventist Church, located in Sambaina, near Antsirabe, Madagascar.\n\nWe train competent, honest and committed women and men to serve God and their community, with a holistic vision of education: mind, body and spirit.",
    'quote_text_en'    => 'Every man according as he purposeth in his heart, so let him give; not grudgingly, or of necessity: for God loveth a cheerful giver.',
    'quote_author_en'  => '2 Corinthians 9:7',
    'cta_title_en'     => 'Ready to **make a difference**?',
    'cta_text_en'      => 'Your generosity, however modest, becomes bricks, books and scholarships for our students.',
    'about_intro_en'   => 'Zurcher Adventist University (UAZ) is a higher-education institution of the Seventh-day Adventist Church in Madagascar. Located in Sambaina, at kilometre 135 of the RN7 near Antsirabe, it welcomes students from all over the island and beyond.',
    'about_history_en' => "Add the history of the university here: its founding, key milestones, faculties and achievements.\n\nThis text can be edited in the administration area, under “Paramètres → À propos”.",
    'about_mission_en' => 'To support education and give every student the opportunity to reach their full potential, training tomorrow\'s leaders in keeping with Christian values.',
    'about_vision_en'  => 'To be a leading university offering holistic development — intellectual, physical, social and spiritual — to every student.',
    'about_values_en'  => "- Academic and professional **excellence**\n- **Integrity** and transparency\n- **Service** to God and the community\n- **Faith** and Christian values",
    'give_intro_en'    => 'There are several ways to support UAZ. Choose the one that suits you: every contribution is used carefully and transparently for the project you choose.',
    'give_after_en'    => 'After your gift, fill in the form below so that we can thank you, send you a receipt and assign your gift to the right project.',
    'office_hours_en'  => 'Monday to Thursday, 8 am – 5 pm; Friday, 8 am – 12 pm',
];
foreach ($defaults as $k => $v) {
    if (!fetch_value('SELECT COUNT(*) FROM settings WHERE skey = ?', [$k])) {
        q('INSERT INTO settings (skey, svalue) VALUES (?, ?)', [$k, $v]);
    }
}

if (!isset($opts['no-demo']) && !fetch_value('SELECT COUNT(*) FROM projects')) {
    echo '→ Contenu d\'exemple (à remplacer depuis l\'admin)…' . PHP_EOL;
    $t = now();
    $projects = [
        ['nouvelle-bibliotheque', 'Nouvelle bibliothèque universitaire', 'upcoming', 'Un espace moderne de lecture et de recherche, connecté, pour accueillir des centaines d\'étudiants chaque jour.',
            "**Contenu d'exemple** — remplacez ce texte depuis l'espace d'administration.\n\nLa future bibliothèque offrira des salles de lecture, un espace numérique et des salles de travail en groupe.\n\n## Ce que votre don finance\n- Gros œuvre et toiture\n- Mobilier et rayonnages\n- Ordinateurs et connexion internet", 'Campus de Sambaina', 450000000, 0, 1, 1],
        ['residence-etudiante', 'Résidence pour étudiants', 'ongoing', 'Achever la construction d\'une résidence afin d\'offrir un logement sûr et digne aux étudiants venant de loin.',
            "**Contenu d'exemple** — remplacez ce texte depuis l'espace d'administration.\n\nLes travaux ont commencé : il reste à financer la finition, l'électricité et l'équipement des chambres.\n\n## Prochaines étapes\n- Finitions intérieures\n- Installation électrique et sanitaire\n- Lits, bureaux et armoires", 'Campus de Sambaina', 300000000, 168000000, 1, 2],
        ['equipement-laboratoires', 'Équipement des laboratoires', 'ongoing', 'Doter nos laboratoires d\'équipements essentiels pour un enseignement scientifique pratique et de qualité.',
            "**Contenu d'exemple** — remplacez ce texte depuis l'espace d'administration.\n\nAidez-nous à finaliser le financement des équipements essentiels : microscopes, matériel informatique et paillasses.", 'Faculté des sciences', 120000000, 54000000, 0, 3],
        ['renovation-salles-de-classe', 'Rénovation des salles de classe', 'completed', 'Grâce à nos donateurs, des salles de classe ont été entièrement rénovées : toiture, peinture, éclairage et mobilier.',
            "**Contenu d'exemple** — remplacez ce texte depuis l'espace d'administration.\n\nMerci à tous les donateurs qui ont rendu ce projet possible. Les étudiants profitent aujourd'hui de salles lumineuses et confortables.", 'Bâtiment principal', 80000000, 80000000, 1, 4],
    ];
    foreach ($projects as [$slug, $title, $status, $summary, $desc, $loc, $goal, $raised, $feat, $order]) {
        q('INSERT INTO projects (slug, title, status, summary, description, location, goal_amount, raised_amount, is_featured, is_published, sort_order, created_at)
           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?)', [$slug, $title, $status, $summary, $desc, $loc, $goal, $raised, $feat, $order, $t]);
    }

    $testimonials = [
        ['Marc D.', 'Pasteur, ancien étudiant', 'Grâce au programme SPONSOR, j\'ai pu réaliser mon rêve de devenir pasteur. Je ne remercierai jamais assez ceux qui ont cru en moi.'],
        ['Sophie L.', 'Diplômée', 'Je remercie SPONSOR pour son aide précieuse : sans ce soutien, je n\'aurais jamais pu obtenir mon diplôme.'],
        ['Antoine R.', 'Ancien étudiant (4 ans à l\'UAZ)', 'Pendant quatre années à l\'UAZ, j\'ai reçu bien plus qu\'une formation. Merci à SPONSOR pour la confiance accordée.'],
    ];
    foreach ($testimonials as $i => [$n, $r, $qt]) {
        q('INSERT INTO testimonials (name, role, quote, is_published, sort_order, created_at) VALUES (?, ?, ?, 1, ?, ?)', [$n, $r, $qt, $i + 1, $t]);
    }

    $methods = [
        ['Virement bancaire', 'bank', 'Effectuez un virement depuis Madagascar ou l\'étranger, en précisant le projet en référence.',
            "Banque : [À COMPLÉTER]\nTitulaire : Université Adventiste Zurcher\nIBAN / RIB : [À COMPLÉTER]\nCode SWIFT : [À COMPLÉTER]\nRéférence : DON + nom du projet"],
        ['Mobile Money', 'mobile', 'Envoyez votre don par MVola, Orange Money ou Airtel Money, puis remplissez le formulaire pour nous prévenir.',
            "MVola : [À COMPLÉTER]\nOrange Money : [À COMPLÉTER]\nAirtel Money : [À COMPLÉTER]\nNom du bénéficiaire : UAZ"],
        ['Parrainer un étudiant (SPONSOR)', 'grad', 'Le contexte économique rend difficile le paiement des frais de scolarité. Avec SPONSOR, vous permettez à un étudiant de poursuivre ses études.',
            "Programme : SPONSOR\nCode projet : UAZPAR"],
        ['Don en nature', 'gift', 'Matériaux de construction, livres, ordinateurs, mobilier… Contactez-nous pour organiser votre don matériel.', ''],
    ];
    foreach ($methods as $i => [$title, $icon, $desc, $det]) {
        q('INSERT INTO donation_methods (title, icon, description, details, is_published, sort_order, created_at) VALUES (?, ?, ?, ?, 1, ?, ?)', [$title, $icon, $desc, $det, $i + 1, $t]);
    }

    $donors = [
        ['Donateur exemple', 'individual', 'Madagascar', 'Fier de soutenir l\'UAZ !'],
        ['Église exemple', 'church', 'Madagascar', ''],
        ['Entreprise exemple', 'company', 'France', ''],
    ];
    foreach ($donors as $i => [$n, $ty, $c, $m]) {
        q('INSERT INTO donors (name, donor_type, country, message, is_published, sort_order, created_at) VALUES (?, ?, ?, ?, 1, ?, ?)', [$n, $ty, $c, $m, $i + 1, $t]);
    }
}

// Versions anglaises du contenu d'exemple : ne remplit que les champs anglais encore vides
// des éléments d'exemple (reconnus par leur identifiant ou leur nom d'origine).
$demoEn = [
    'projects' => ['slug', [
        'nouvelle-bibliotheque' => [
            'title_en' => 'New university library',
            'summary_en' => 'A modern, connected space for reading and research, welcoming hundreds of students every day.',
            'description_en' => "**Sample content** — replace this text from the administration area.\n\nThe future library will offer reading rooms, a digital space and group study rooms.\n\n## What your gift funds\n- Structural work and roofing\n- Furniture and shelving\n- Computers and internet access",
            'location_en' => 'Sambaina campus',
        ],
        'residence-etudiante' => [
            'title_en' => 'Student residence',
            'summary_en' => 'Completing a residence to provide safe and decent housing for students who come from far away.',
            'description_en' => "**Sample content** — replace this text from the administration area.\n\nConstruction has begun: the finishing work, electrical installation and room furnishings still need funding.\n\n## Next steps\n- Interior finishing\n- Electrical and plumbing installation\n- Beds, desks and wardrobes",
            'location_en' => 'Sambaina campus',
        ],
        'equipement-laboratoires' => [
            'title_en' => 'Laboratory equipment',
            'summary_en' => 'Equipping our laboratories with the essentials for practical, high-quality science teaching.',
            'description_en' => "**Sample content** — replace this text from the administration area.\n\nHelp us complete the funding of essential equipment: microscopes, computer equipment and lab benches.",
            'location_en' => 'Faculty of Science',
        ],
        'renovation-salles-de-classe' => [
            'title_en' => 'Classroom renovation',
            'summary_en' => 'Thanks to our donors, classrooms have been fully renovated: roofing, paint, lighting and furniture.',
            'description_en' => "**Sample content** — replace this text from the administration area.\n\nThank you to all the donors who made this project possible. Students now enjoy bright and comfortable classrooms.",
            'location_en' => 'Main building',
        ],
    ]],
    'testimonials' => ['name', [
        'Marc D.' => ['role_en' => 'Pastor, alumnus', 'quote_en' => 'Thanks to the SPONSOR programme, I was able to fulfil my dream of becoming a pastor. I can never thank enough those who believed in me.'],
        'Sophie L.' => ['role_en' => 'Graduate', 'quote_en' => 'I thank SPONSOR for its invaluable help: without this support, I could never have earned my degree.'],
        'Antoine R.' => ['role_en' => 'Alumnus (4 years at UAZ)', 'quote_en' => 'During four years at UAZ, I received far more than an education. Thank you, SPONSOR, for the trust you placed in me.'],
    ]],
    'donation_methods' => ['title', [
        'Virement bancaire' => [
            'title_en' => 'Bank transfer',
            'description_en' => 'Make a transfer from Madagascar or abroad, quoting the project as the reference.',
            'details_en' => "Bank: [TO BE COMPLETED]\nAccount holder: Université Adventiste Zurcher\nIBAN / account no.: [TO BE COMPLETED]\nSWIFT code: [TO BE COMPLETED]\nReference: GIFT + project name",
        ],
        'Mobile Money' => [
            'title_en' => 'Mobile Money',
            'description_en' => 'Send your gift with MVola, Orange Money or Airtel Money, then fill in the form to let us know.',
            'details_en' => "MVola: [TO BE COMPLETED]\nOrange Money: [TO BE COMPLETED]\nAirtel Money: [TO BE COMPLETED]\nBeneficiary name: UAZ",
        ],
        'Parrainer un étudiant (SPONSOR)' => [
            'title_en' => 'Sponsor a student (SPONSOR)',
            'description_en' => 'The economic situation makes tuition fees hard to pay. With SPONSOR, you enable a student to continue their studies.',
            'details_en' => "Programme: SPONSOR\nProject code: UAZPAR",
        ],
        'Don en nature' => [
            'title_en' => 'In-kind gifts',
            'description_en' => 'Building materials, books, computers, furniture… Contact us to arrange your in-kind gift.',
        ],
    ]],
    'donors' => ['name', [
        'Donateur exemple' => ['message_en' => 'Proud to support UAZ!'],
    ]],
];
foreach ($demoEn as $table => [$keyCol, $rows]) {
    foreach ($rows as $key => $fields) {
        foreach ($fields as $col => $value) {
            q("UPDATE $table SET $col = ? WHERE $keyCol = ? AND ($col IS NULL OR $col = '')", [$value, $key]);
        }
    }
}

// Code projet par défaut pour les projets qui n'en ont pas encore
foreach (fetch_all("SELECT id FROM projects WHERE code IS NULL OR code = ''") as $p) {
    q('UPDATE projects SET code = ? WHERE id = ?', [default_project_code((int) $p['id']), $p['id']]);
}

$hasAdmin = (int) fetch_value('SELECT COUNT(*) FROM admins');
if (!$hasAdmin || isset($opts['email'])) {
    echo '→ Création du compte administrateur' . PHP_EOL;
    $email = $opts['email'] ?? ask('E-mail');
    $name = $opts['name'] ?? (ask('Nom affiché') ?: 'Administrateur');
    $pass = $opts['password'] ?? ask('Mot de passe (10 caractères min.)', true);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($pass) < 10) {
        fwrite(STDERR, '✗ E-mail invalide ou mot de passe trop court (10 caractères minimum).' . PHP_EOL);
        exit(1);
    }
    if (fetch_value('SELECT COUNT(*) FROM admins WHERE email = ?', [$email])) {
        q('UPDATE admins SET name = ?, password_hash = ? WHERE email = ?', [$name, password_hash($pass, PASSWORD_DEFAULT), $email]);
        echo "  Compte $email mis à jour." . PHP_EOL;
    } else {
        q('INSERT INTO admins (name, email, password_hash, created_at) VALUES (?, ?, ?, ?)', [$name, $email, password_hash($pass, PASSWORD_DEFAULT), now()]);
        echo "  Compte $email créé." . PHP_EOL;
    }
}

echo '✓ Installation terminée. Espace admin : ' . absolute_url('admin/') . PHP_EOL;
