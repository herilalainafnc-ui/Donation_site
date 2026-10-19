# Site de donation — Université Adventiste Zurcher (UAZ)

Site public + espace d'administration en **PHP 8.1+ / MySQL (ou MariaDB)**, sans framework ni
dépendance externe, conçu pour un **VPS Hostinger** (Ubuntu + Nginx + PHP-FPM).
Le cahier des charges complet se trouve dans [PROMPT.md](PROMPT.md).

## Fonctionnalités

**Site public** : accueil, à propos, projets (filtres en cours / à venir / achevés), fiche projet
avec barre de progression et galerie, comment donner (coordonnées avec bouton « copier » et
formulaire de promesse de don), témoignages, mur des donateurs, contact.

**Admin (`/admin/`)** : tableau de bord, projets (galerie multi-photos), témoignages, donateurs,
moyens de don, messages reçus (une promesse de don peut être convertie en donateur en un clic),
paramètres du site (textes, images, coordonnées, réseaux sociaux, lien de paiement en ligne),
administrateurs et changement de mot de passe.

**Bilingue** : anglais par défaut (`/`, `/about`, `/projects`…) et français (`/fr`, `/fr/a-propos`,
`/fr/projets`…), avec un sélecteur EN | FR. Les textes de l'interface sont dans
[app/lang/en.php](app/lang/en.php) ; chaque contenu de l'admin a un champ « — Français » et
« — English » (si l'anglais est vide, le français s'affiche). Les anciennes adresses françaises sans
`/fr` redirigent automatiquement.

## Arborescence

```
app/            code PHP (hors de la racine web)
  admin/        contrôleur, écrans et définition des contenus de l'admin
  views/        gabarits du site public
  config.sample.php
install/        schémas SQL + script d'installation
public/         RACINE WEB (index.php, admin/, assets/, uploads/)
deploy/         configuration Nginx + script de sauvegarde
storage/        base SQLite (tests locaux uniquement)
```

## Essai rapide en local

Nécessite PHP 8.1+ avec les extensions `pdo_sqlite`, `mbstring` et `gd`.

```bash
cp app/config.sample.php app/config.php
```

Dans `app/config.php`, mettez `'driver' => 'sqlite'`, `'debug' => true` et `'site_url' => 'http://localhost:8000'`, puis :

```bash
php install/install.php --email=admin@example.com --name="Admin" --password="motdepasse-local"
```

```bash
php -S localhost:8000 -t public public/index.php
```

Site : http://localhost:8000, admin : http://localhost:8000/admin/

---

## Mise en ligne sur Hostinger VPS

Toute la procédure (VPS, sous-domaine, sécurité, Nginx, PHP, MariaDB, HTTPS, sauvegardes,
e-mails, mises à jour, dépannage) est détaillée dans [DEPLOIEMENT.md](DEPLOIEMENT.md).

## Sécurité intégrée

Mots de passe hachés (bcrypt), jetons CSRF sur tous les formulaires, requêtes préparées PDO,
échappement HTML systématique, blocage après 5 échecs de connexion (15 min), session admin
expirant après 2 h d'inactivité, pot de miel et limite d'envois anti-spam, validation du type réel
des images, noms de fichiers aléatoires, aucune exécution PHP dans `/uploads`, code hors de la
racine web.
