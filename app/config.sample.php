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

    // E-mails : notifications à l'équipe, reçus de don, rappels des dons réguliers.
    // false = aucun envoi (les messages sont seulement écrits dans storage/mail.log).
    'mail_notifications' => false,
    'mail_from'          => 'no-reply@zurcher.edu.mg',
    // Serveur d'envoi (recommandé : une boîte de la messagerie de l'université). Laisser 'host' vide pour utiliser mail().
    'smtp' => [
        'host'   => '',          // ex. smtp.gmail.com (Google Workspace) ou smtp.hostinger.com
        'port'   => 587,
        'secure' => 'tls',       // 'tls' (port 587) ou 'ssl' (port 465)
        'user'   => '',          // adresse complète de la boîte d'envoi
        'pass'   => '',          // mot de passe (ou mot de passe d'application)
    ],

    // Paiement en ligne. Les clés restent ici, jamais dans l'admin ni dans le code envoyé au navigateur.
    // Un prestataire sans clés est simplement inactif. Activation / désactivation : admin → Paramètres → Paiement en ligne.
    'payments' => [
        // Stripe (carte bancaire, dons réguliers prélevés automatiquement).
        // Webhook à créer dans le tableau de bord Stripe : https://<site>/paiement/stripe/webhook
        'stripe' => [
            'secret_key'     => '',  // sk_test_… (test) puis sk_live_… (production)
            'webhook_secret' => '',  // whsec_… affiché à la création du webhook
        ],
        // MVola (Telma) — API Merchant Pay. Numéros de test sandbox : 0343500003 et 0343500004.
        'mvola' => [
            'consumer_key'    => '',
            'consumer_secret' => '',
            'merchant_number' => '',     // numéro MVola marchand de l'université
            'partner_name'    => 'UAZ',
            'sandbox'         => true,   // false une fois le compte passé en production
        ],
        // Orange Money — API Web Payment (Orange Developer).
        'orange_money' => [
            'authorization' => '',       // en-tête « Basic … » de l'application Orange Developer
            'merchant_key'  => '',
            'sandbox'       => true,     // en test : pays « dev », devise « OUV »
            // 'country'  => 'mg',       // chemin pays de l'API en production
            // 'currency' => 'MGA',
        ],
        // Prestataire simulé, pour essayer tout le parcours en local sans aucun paiement réel. Jamais en production.
        'test' => false,
    ],
];
