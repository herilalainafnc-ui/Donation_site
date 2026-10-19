<?php
/**
 * Copiez ce fichier vers app/config.php puis adaptez les valeurs.
 * Ne versionnez jamais app/config.php (il contient les mots de passe).
 */
return [
    // URL publique complète du site (sans / final) — utilisée pour les balises Open Graph.
    'site_url'  => 'https://don.zurcher.edu.mg',
    // Sous-dossier si le site n'est pas à la racine du domaine (ex. '/don'). Laisser '' sinon.
    'base_path' => '',

    'debug'    => false,
    'timezone' => 'Indian/Antananarivo',

    'db' => [
        // 'mysql' en production (MySQL/MariaDB), 'sqlite' pour un test rapide en local.
        'driver'      => 'mysql',
        'host'        => '127.0.0.1',
        'port'        => 3306,
        'name'        => 'uaz_donation',
        'user'        => 'uaz_donation',
        'pass'        => 'CHANGEZ-MOI',
        'sqlite_path' => __DIR__ . '/../storage/database.sqlite',
    ],

    // Taille maximale d'une image téléversée (Mo). Gardez nginx client_max_body_size au-dessus.
    'upload_max_mb' => 8,
    // Largeur maximale des images après redimensionnement (nécessite l'extension GD).
    'image_max_width' => 1920,

    // Notification e-mail à chaque message / promesse de don (nécessite un serveur mail sur le VPS).
    'mail_notifications' => false,
    'mail_from'          => 'no-reply@zurcher.edu.mg',
];
