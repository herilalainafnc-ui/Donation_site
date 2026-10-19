<?php
/** Onglets et champs de la page « Paramètres du site ». « i18n » : champs saisis en français et en anglais. */
return [
    'general' => [
        'label' => 'Général',
        'i18n' => ['site_name', 'tagline', 'meta_description'],
        'fields' => [
            'site_name' => ['label' => 'Nom du site', 'type' => 'text', 'half' => true],
            'site_short' => ['label' => 'Sigle', 'type' => 'text', 'half' => true],
            'tagline' => ['label' => 'Slogan', 'type' => 'text'],
            'meta_description' => ['label' => 'Description pour Google (≈ 155 caractères)', 'type' => 'textarea', 'rows' => 2],
            'logo' => ['label' => 'Logo (laisser vide pour utiliser le sceau officiel de l\'UAZ)', 'type' => 'image', 'dir' => 'site'],
            'currency' => ['label' => 'Devise affichée', 'type' => 'text', 'half' => true, 'help' => 'ex. Ar, MGA, €, $'],
            'notify_email' => ['label' => 'E-mail qui reçoit les notifications', 'type' => 'email', 'half' => true],
        ],
    ],
    'home' => [
        'label' => 'Accueil',
        'i18n' => ['hero_kicker', 'hero_title', 'hero_subtitle', 'about_teaser', 'quote_text', 'quote_author', 'cta_title', 'cta_text'],
        'fields' => [
            'hero_kicker' => ['label' => 'Surtitre de la bannière', 'type' => 'text'],
            'hero_title' => ['label' => 'Titre principal', 'type' => 'text', 'help' => 'Entourez un ou deux mots de ** pour les faire ressortir en doré, ex. Chaque don construit **l\'avenir** d\'un étudiant'],
            'hero_subtitle' => ['label' => 'Sous-titre', 'type' => 'textarea', 'rows' => 3],
            'hero_image' => ['label' => 'Image de la bannière (paysage, 1920 px conseillé)', 'type' => 'image', 'dir' => 'site'],
            'about_teaser' => ['label' => 'Texte « Qui sommes-nous ? »', 'type' => 'textarea', 'rows' => 6, 'format_help' => true],
            'quote_text' => ['label' => 'Citation', 'type' => 'textarea', 'rows' => 3],
            'quote_author' => ['label' => 'Auteur / référence de la citation', 'type' => 'text'],
            'cta_title' => ['label' => 'Titre du bandeau d\'appel au don', 'type' => 'text'],
            'cta_text' => ['label' => 'Texte du bandeau d\'appel au don', 'type' => 'textarea', 'rows' => 2],
        ],
    ],
    'about' => [
        'label' => 'À propos',
        'i18n' => ['about_intro', 'about_history', 'about_mission', 'about_vision', 'about_values'],
        'fields' => [
            'about_intro' => ['label' => 'Présentation générale', 'type' => 'textarea', 'rows' => 6, 'format_help' => true],
            'about_history' => ['label' => 'Histoire', 'type' => 'textarea', 'rows' => 10, 'format_help' => true],
            'about_mission' => ['label' => 'Mission', 'type' => 'textarea', 'rows' => 3],
            'about_vision' => ['label' => 'Vision', 'type' => 'textarea', 'rows' => 3],
            'about_values' => ['label' => 'Valeurs', 'type' => 'textarea', 'rows' => 5, 'format_help' => true],
            'about_image' => ['label' => 'Photo du campus', 'type' => 'image', 'dir' => 'site'],
        ],
    ],
    'give' => [
        'label' => 'Dons',
        'i18n' => ['give_intro', 'give_after', 'online_donation_label'],
        'fields' => [
            'give_intro' => ['label' => 'Introduction de la page « Comment donner »', 'type' => 'textarea', 'rows' => 3],
            'give_after' => ['label' => 'Texte au-dessus du formulaire de don', 'type' => 'textarea', 'rows' => 3],
            'online_donation_url' => ['label' => 'Lien de paiement en ligne (PayPal, HelloAsso, Stripe…)', 'type' => 'url', 'help' => 'Laissez vide pour masquer le bloc « Don en ligne ».'],
            'online_donation_label' => ['label' => 'Texte du bouton de paiement en ligne', 'type' => 'text'],
        ],
    ],
    'contact' => [
        'label' => 'Contact',
        'i18n' => ['office_hours'],
        'fields' => [
            'contact_address' => ['label' => 'Adresse postale', 'type' => 'textarea', 'rows' => 3],
            'contact_email' => ['label' => 'E-mail public', 'type' => 'email', 'half' => true],
            'contact_phone' => ['label' => 'Téléphone', 'type' => 'text', 'half' => true],
            'contact_whatsapp' => ['label' => 'Numéro WhatsApp (format international)', 'type' => 'text', 'half' => true],
            'office_hours' => ['label' => 'Horaires', 'type' => 'text', 'half' => true],
            'website_url' => ['label' => 'Site officiel de l\'université', 'type' => 'url'],
            'facebook_url' => ['label' => 'Page Facebook', 'type' => 'url', 'half' => true],
            'youtube_url' => ['label' => 'Chaîne YouTube', 'type' => 'url', 'half' => true],
            'map_embed_url' => ['label' => 'Lien d\'intégration Google Maps', 'type' => 'url', 'help' => 'Google Maps → Partager → Intégrer une carte → copiez uniquement l\'adresse contenue dans src="…".'],
        ],
    ],
];
