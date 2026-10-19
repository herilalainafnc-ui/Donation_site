# Prompt — Site web de donation de l'Université Adventiste Zurcher (UAZ)

## Contexte

L'**Université Adventiste Zurcher (UAZ)**, établissement d'enseignement supérieur adventiste situé à
Sambaina, PK 135 RN7, Antsirabe (Madagascar), mène de nombreux projets de construction et
d'équipement. Elle souhaite un site web dédié à la **collecte de dons** pour financer ces projets et
soutenir ses étudiants (programme de parrainage **SPONSOR**, code projet **UAZPAR**).

Un premier brouillon existe sur Webador (https://www.ckancel.net/). Le nouveau site le remplace par
une solution professionnelle, autonome et administrable.

## Objectif

Créer un site **professionnel, chaleureux et visuellement attractif** qui :

1. inspire confiance (institution sérieuse, transparence sur l'usage des fonds) ;
2. présente clairement les projets et leur avancement financier ;
3. explique simplement **comment donner** ;
4. valorise les **témoignages** et remercie les **donateurs** ;
5. permet à l'équipe UAZ de **gérer tout le contenu sans développeur** via un espace d'administration.

## Public cible

- Membres et églises de l'Église adventiste (Madagascar et diaspora)
- Anciens étudiants (alumni), parents, partenaires, entreprises et ONG
- Donateurs internationaux (le site doit rester lisible pour des non-Malgaches)

## Identité visuelle

| Rôle       | Couleur   | Usage                                                     |
|------------|-----------|-----------------------------------------------------------|
| Primaire   | `#183060` | En-tête, titres, fonds de sections fortes, pied de page   |
| Secondaire | `#f6f7fb` | Fonds de sections claires, cartes                         |
| Tertiaire  | `#907848` | Boutons d'appel au don, accents, barres de progression    |

- Logo : sceau officiel de l'UAZ (en-tête, pied de page, admin, favicon, filigrane des bannières).
- Typographie : **Raleway** pour les grands titres, **Roboto Slab** pour les contenus.
- Style « fondation premium » : mise en page éditoriale (sections numérotées, très grands titres,
  filets fins), bannières sombres plein écran avec aurore animée, grille et grain, sceau en orbite
  avec la devise, mot clé du titre en dégradé doré, en-tête transparent qui devient verre dépoli.
- **Animations** (GSAP + ScrollTrigger + défilement fluide Lenis) : intro du sceau à la première
  visite, transitions entre pages en rideau, titres révélés mot par mot, devise géante
  « Spiritus · Mens · Manus · Service » qui glisse au défilement, projets en défilement horizontal
  épinglé, étapes « collantes » avec ligne de progression, verset qui s'illumine mot par mot,
  carrousel de témoignages, double bandeau de donateurs, appel au don révélé par un cercle,
  boutons magnétiques, cartes inclinables, compteurs et barres animés, menu mobile plein écran.
  Tout reste lisible sans JavaScript et les animations se coupent si le visiteur demande à
  réduire les mouvements.
- 100 % responsive (mobile d'abord), accessible (contrastes, textes alternatifs, navigation clavier).

## Arborescence (site public, en français)

| Page               | URL                | Contenu                                                                                     |
|--------------------|--------------------|---------------------------------------------------------------------------------------------|
| Accueil            | `/`                | Bannière + appel au don, chiffres clés, aperçu UAZ, projets en vedette, étapes pour donner, témoignages, donateurs, citation, contact |
| À propos de nous   | `/a-propos`        | Présentation générale, histoire, mission, vision, valeurs, image                           |
| Nos projets        | `/projets`         | Filtres **Tous / En cours / À venir / Achevés**, cartes avec barre de progression          |
| Détail d'un projet | `/projets/{slug}`  | Description, lieu, dates, objectif / montant collecté, galerie photos, bouton « Soutenir ce projet » |
| Comment donner     | `/comment-donner`  | Moyens de don (virement, Mobile Money, parrainage…) avec bouton « copier », formulaire de promesse de don, lien de paiement en ligne optionnel |
| Témoignages        | `/temoignages`     | Témoignages d'étudiants / alumni avec photo et rôle                                         |
| Nos donateurs      | `/donateurs`       | Mur de remerciement : particuliers, églises, entreprises, organisations (montant visible si autorisé) |
| Contact            | `/contact`         | Coordonnées, formulaire de contact (anti-spam)                                              |

## Espace d'administration (`/admin`)

Accès sécurisé par identifiant / mot de passe. Fonctions :

- **Tableau de bord** : nombre de projets par statut, total collecté, messages non lus, derniers messages.
- **Projets** : ajouter / modifier / supprimer ; statut (à venir, en cours, achevé), objectif et montant
  collecté, dates, lieu, image de couverture, **galerie de photos multiples**, mise en vedette, publication, ordre.
- **Témoignages** : nom, rôle, texte, photo, publication, ordre.
- **Donateurs** : nom, type, pays, logo, site web, montant (affiché ou non), projet soutenu, message.
- **Moyens de don** : titre, type, description, coordonnées (RIB, numéros Mobile Money…), icône.
- **Messages** : promesses de don et messages de contact reçus, marquage lu / non lu, suppression.
- **Paramètres du site** : textes de la page d'accueil, image de bannière, « À propos », coordonnées,
  réseaux sociaux, devise, lien de paiement en ligne, citation.
- **Administrateurs** : création de comptes, changement de mot de passe.

## Exigences techniques

- **Hébergement : Hostinger VPS** (Ubuntu 22.04/24.04).
- Pile : **PHP 8.1+ (PHP-FPM) + MySQL/MariaDB + Nginx**, sans framework ni dépendance Composer
  (facile à maintenir et à déployer). SQLite possible pour les tests en local.
- Images téléversées validées (type réel, taille max), renommées aléatoirement, redimensionnées.
- Sécurité : mots de passe hachés (bcrypt/argon), protection CSRF, requêtes préparées (PDO),
  échappement HTML systématique, limitation des tentatives de connexion, sessions sécurisées,
  pas d'exécution PHP dans `/uploads`, HTTPS (Let's Encrypt).
- SEO : URL propres, balises `title` / `meta description` / Open Graph par page.
- Livrables : code source, script d'installation (création des tables + compte admin + contenu
  d'exemple), configuration Nginx, guide de déploiement pas à pas et procédure de sauvegarde.
