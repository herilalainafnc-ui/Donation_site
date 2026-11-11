# Mise en ligne sur un VPS Hostinger — guide pas à pas

Ce guide met le site en ligne sur un **sous-domaine**, par exemple `don.zurcher.edu.mg`.
Remplacez partout `don.zurcher.edu.mg` par votre vrai sous-domaine et `IP_DU_VPS` par l'adresse
IP de votre serveur.

**Durée estimée** : 1 h 30 à 2 h (plus le délai de propagation DNS, de quelques minutes à quelques heures).

**Pile installée** : Ubuntu 24.04 · Nginx · PHP 8.3 (PHP-FPM) · MariaDB · certificat HTTPS Let's Encrypt.

---

## Étape 0 — Ce qu'il vous faut

- L'accès à **hPanel** (le tableau de bord Hostinger) avec le VPS déjà acheté.
- L'accès à la **gestion DNS du domaine** `zurcher.edu.mg` (chez Hostinger ou chez le registraire
  du domaine — voir l'étape 3).
- Sur votre PC Windows 11 : le **Terminal** (PowerShell) — les commandes `ssh`, `scp` et `tar` y sont
  déjà incluses.
- Le dossier du projet `K:\Projects\Donation_site`.

Conventions de ce guide :

- Les blocs marqués *(sur votre PC)* se tapent dans PowerShell sur votre ordinateur.
- Les blocs marqués *(sur le serveur)* se tapent après connexion SSH au VPS.

---

## Étape 1 — Préparer le VPS dans hPanel

1. **hPanel → VPS → Gérer** (votre serveur).
2. **Système d'exploitation** : choisissez **Ubuntu 24.04 64 bits** (version « simple », *sans* panneau
   de contrôle).
   > ⚠️ **Si le VPS héberge déjà un site** (par exemple `zurcher.edu.mg`), **ne changez surtout pas
   > d'OS** : cela efface tout le serveur. Gardez le système en place et passez directement à l'étape 2.
3. Définissez un **mot de passe root** solide (gardez-le dans un gestionnaire de mots de passe).
4. Notez l'**adresse IP** du VPS, visible dans **Aperçu**.
5. (Recommandé) **Paramètres → Clés SSH** : ajoutez votre clé publique (voir encadré ci-dessous).

> **Créer une clé SSH sur votre PC** *(sur votre PC)* — une seule fois :
> ```bash
> ssh-keygen -t ed25519 -C "uaz-vps"
> ```
> Appuyez sur Entrée pour accepter l'emplacement, choisissez une phrase de passe.
> Affichez ensuite la clé publique à copier dans hPanel :
> ```bash
> type $env:USERPROFILE\.ssh\id_ed25519.pub
> ```

### Première connexion *(sur votre PC)*

```bash
ssh root@IP_DU_VPS
```

Répondez `yes` à la question sur l'empreinte du serveur la première fois.

---

## Étape 2 — Sécuriser le serveur *(sur le serveur)*

```bash
apt update && apt upgrade -y
timedatectl set-timezone Indian/Antananarivo
```

### 2.1 Créer un utilisateur d'administration (ne plus travailler en root)

```bash
adduser uazadmin
usermod -aG sudo uazadmin
rsync --archive --chown=uazadmin:uazadmin ~/.ssh /home/uazadmin
```

Testez dans une **nouvelle fenêtre** PowerShell *(sur votre PC)* avant de fermer la session root :

```bash
ssh uazadmin@IP_DU_VPS
```

À partir d'ici, connectez-vous avec `uazadmin` et préfixez les commandes d'administration par `sudo`.

### 2.2 Pare-feu

```bash
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw --force enable
sudo ufw status
```

Dans hPanel, **VPS → Sécurité → Pare-feu** : si un pare-feu Hostinger est actif, autorisez aussi
les ports **22, 80 et 443**.

### 2.3 Protection contre les attaques par force brute et mises à jour automatiques

```bash
sudo apt install -y fail2ban unattended-upgrades
sudo systemctl enable --now fail2ban
sudo dpkg-reconfigure -plow unattended-upgrades
```

(Répondez « Oui » pour activer les mises à jour de sécurité automatiques.)

---

## Étape 3 — Créer le sous-domaine (DNS)

Le sous-domaine doit pointer vers l'IP du VPS grâce à un **enregistrement A**.

### Cas A — Le domaine est géré chez Hostinger

1. **hPanel → Domaines → zurcher.edu.mg → DNS / Serveurs de noms**.
2. **Ajouter un enregistrement** :

   | Type | Nom   | Pointe vers  | TTL   |
   |------|-------|--------------|-------|
   | A    | `don` | `IP_DU_VPS`  | 3600  |

3. Si votre VPS a une adresse IPv6 (visible dans l'aperçu du VPS), ajoutez aussi un enregistrement
   **AAAA** `don` → adresse IPv6. Sinon, ne mettez pas d'AAAA.

### Cas B — Le domaine est géré ailleurs (registraire du `.mg`, service informatique de l'université…)

Demandez à la personne qui gère la zone DNS de `zurcher.edu.mg` d'ajouter exactement :

```
don.zurcher.edu.mg.   3600   IN   A   IP_DU_VPS
```

> Ne modifiez **pas** les serveurs de noms du domaine principal : le site actuel de l'université
> (`zurcher.edu.mg`) doit continuer à fonctionner. On ajoute seulement un enregistrement pour `don`.

### Vérifier la propagation *(sur votre PC)*

```bash
nslookup don.zurcher.edu.mg 8.8.8.8
```

Quand la réponse affiche l'IP du VPS, c'est bon (cela peut prendre de 5 minutes à quelques heures).
Vous pouvez continuer les étapes 4 à 8 pendant ce temps.

---

## Étape 4 — Installer Nginx, PHP et MariaDB *(sur le serveur)*

```bash
sudo apt install -y nginx mariadb-server unzip certbot python3-certbot-nginx \
  php8.3-fpm php8.3-mysql php8.3-mbstring php8.3-gd php8.3-xml php8.3-curl php8.3-intl
```

Vérifiez les versions :

```bash
php -v
nginx -v
```

### 4.1 Réglages PHP

```bash
sudo nano /etc/php/8.3/fpm/php.ini
```

Cherchez (Ctrl+W) et modifiez ces lignes :

```ini
upload_max_filesize = 10M
post_max_size = 40M
max_file_uploads = 30
memory_limit = 256M
expose_php = Off
date.timezone = Indian/Antananarivo
```

Enregistrez (Ctrl+O, Entrée) et quittez (Ctrl+X), puis :

```bash
sudo systemctl restart php8.3-fpm
```

---

## Étape 5 — Créer la base de données *(sur le serveur)*

```bash
sudo mysql_secure_installation
```

Réponses conseillées : *Switch to unix_socket authentication* → **n** · *Change the root password* → **n**
(root se connecte déjà via `sudo`) · *Remove anonymous users* → **Y** · *Disallow root login remotely* → **Y** ·
*Remove test database* → **Y** · *Reload privilege tables* → **Y**.

Générez un mot de passe solide et notez-le :

```bash
openssl rand -base64 24
```

Créez la base et son utilisateur (remplacez `MOT_DE_PASSE_BDD`) :

```bash
sudo mysql
```

```sql
CREATE DATABASE uaz_donation CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'uaz_donation'@'localhost' IDENTIFIED BY 'MOT_DE_PASSE_BDD';
GRANT ALL PRIVILEGES ON uaz_donation.* TO 'uaz_donation'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

---

## Étape 6 — Envoyer les fichiers du site

### 6.1 Créer l'archive *(sur votre PC)*

Depuis le dossier du projet, en excluant les fichiers propres à votre PC (configuration locale,
base de test, images de test, outils de développement) :

```bash
cd K:\Projects\Donation_site
```

```bash
tar -a -c -f uaz-don.zip --exclude=app/config.php --exclude=storage/database.sqlite --exclude=".claude" --exclude="public/uploads/*/*" app install public deploy storage
```

### 6.2 L'envoyer sur le serveur *(sur votre PC)*

```bash
scp uaz-don.zip uazadmin@IP_DU_VPS:/tmp/
```

### 6.3 L'installer au bon endroit *(sur le serveur)*

```bash
sudo mkdir -p /var/www/uaz-don
sudo unzip -o /tmp/uaz-don.zip -d /var/www/uaz-don
rm /tmp/uaz-don.zip
ls /var/www/uaz-don
```

Vous devez voir : `app  deploy  install  public  storage`.

> **Alternative** : si le projet est sur GitHub, `sudo git clone <url> /var/www/uaz-don` remplace les
> étapes 6.1 à 6.3 et simplifie les mises à jour (`git pull`).

---

## Étape 7 — Configurer et installer l'application *(sur le serveur)*

```bash
cd /var/www/uaz-don
sudo cp app/config.sample.php app/config.php
sudo nano app/config.php
```

Valeurs à renseigner :

```php
'site_url'  => 'https://don.zurcher.edu.mg',
'base_path' => '',
'debug'     => false,
'db' => [
    'driver' => 'mysql',
    'host'   => '127.0.0.1',
    'port'   => 3306,
    'name'   => 'uaz_donation',
    'user'   => 'uaz_donation',
    'pass'   => 'MOT_DE_PASSE_BDD',
    ...
],
```

Lancez l'installation (création des tables + **votre compte administrateur**) :

```bash
sudo php install/install.php
```

Le script demande l'e-mail, le nom et le mot de passe de l'administrateur (10 caractères minimum).

- Pour démarrer **sans** le contenu d'exemple : `sudo php install/install.php --no-demo`
- Pour créer un autre administrateur plus tard, ou changer un mot de passe oublié :
  `sudo php install/install.php --email=autre@zurcher.edu.mg --name="Nom" --password="..."`

### Droits sur les fichiers

```bash
sudo chown -R root:root /var/www/uaz-don
sudo chown -R www-data:www-data /var/www/uaz-don/public/uploads /var/www/uaz-don/storage
sudo chown root:www-data /var/www/uaz-don/app/config.php
sudo chmod 640 /var/www/uaz-don/app/config.php
```

(Le serveur web peut lire le code mais ne peut écrire que dans `uploads/` et `storage/`.)

---

## Étape 8 — Configurer Nginx *(sur le serveur)*

```bash
sudo cp /var/www/uaz-don/deploy/nginx-uaz.conf /etc/nginx/sites-available/uaz-don
sudo nano /etc/nginx/sites-available/uaz-don
```

Vérifiez la ligne `server_name don.zurcher.edu.mg;` (votre sous-domaine) et que `php8.3-fpm.sock`
correspond bien à votre version de PHP (`ls /run/php/`).

Activez le site :

```bash
sudo ln -s /etc/nginx/sites-available/uaz-don /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

> ⚠️ **Si le serveur héberge déjà d'autres sites** (par exemple le site principal `zurcher.edu.mg`) :
> ne supprimez **aucun** fichier de `/etc/nginx/sites-enabled/`, et ne lancez jamais `systemctl reload`
> si `nginx -t` signale une erreur. Le fichier `default` ne se supprime que sur un serveur neuf qui
> n'héberge rien d'autre (`ls /etc/nginx/sites-enabled/` pour vérifier).

`nginx -t` doit répondre *syntax is ok* et *test is successful*.

**Test** : une fois le DNS propagé, ouvrez `http://don.zurcher.edu.mg` — le site doit s'afficher
(encore sans cadenas).

---

## Étape 9 — Activer le HTTPS (gratuit) *(sur le serveur)*

```bash
sudo certbot --nginx -d don.zurcher.edu.mg --redirect -m informatique@zurcher.edu.mg --agree-tos -n
```

- Certbot obtient le certificat, l'ajoute à la configuration Nginx et redirige automatiquement
  `http://` vers `https://`.
- Le renouvellement est automatique ; vérifiez-le avec :

```bash
sudo certbot renew --dry-run
```

Ouvrez `https://don.zurcher.edu.mg` : le cadenas doit apparaître.

---

## Étape 10 — Premiers pas dans l'admin

1. Ouvrez `https://don.zurcher.edu.mg/admin/` et connectez-vous.
2. **Paramètres du site** :
   - *Général* : e-mail de notification, devise.
   - *Accueil* : titre (entourez un mot de `**` pour le mettre en doré), image de bannière.
   - *À propos* : présentation, histoire, mission, photo du campus.
   - *Contact* : adresse, e-mail, téléphone, WhatsApp, Facebook, carte Google Maps.
   - *Dons* : lien de paiement en ligne (PayPal, Stripe…) si vous en avez un.
3. **Moyens de don** : remplacez tous les `[À COMPLÉTER]` (français) et `[TO BE COMPLETED]`
   (anglais) par les vraies coordonnées bancaires et numéros Mobile Money.

> **Site bilingue** : l'anglais est la langue par défaut (`https://don.zurcher.edu.mg/`), le français
> est sous `/fr`. Chaque texte de l'admin a un champ « — Français » et un champ « — English » :
> pensez à remplir les deux (si l'anglais est vide, le texte français s'affiche sur le site anglais).
4. **Projets / Témoignages / Donateurs** : remplacez ou supprimez le contenu d'exemple.
   Pour un donateur qui souhaite la discrétion, activez **« Masquer le nom sur le site »**.
5. **Administrateurs** : créez un compte personnel pour chaque membre de l'équipe.

---

## Étape 11 — Sauvegardes automatiques *(sur le serveur)*

Donnez à `mysqldump` les identifiants de la base :

```bash
sudo nano /root/.my.cnf
```

```ini
[mysqldump]
user=uaz_donation
password=MOT_DE_PASSE_BDD
```

```bash
sudo chmod 600 /root/.my.cnf
sudo chmod +x /var/www/uaz-don/deploy/backup.sh
sudo /var/www/uaz-don/deploy/backup.sh && sudo ls -lh /var/backups/uaz-don
```

Planifiez-la chaque nuit à 2 h 30 :

```bash
(sudo crontab -l 2>/dev/null; echo "30 2 * * * /var/www/uaz-don/deploy/backup.sh") | sudo crontab -
```

Les sauvegardes (base + images) sont gardées 14 jours dans `/var/backups/uaz-don`.

En complément :

- hPanel → **VPS → Sauvegardes / Instantanés** : activez les sauvegardes hebdomadaires et créez un
  instantané avant chaque grosse modification.
- Une fois par mois, rapatriez une copie sur votre PC *(sur votre PC)* :
  ```bash
  scp -r uazadmin@IP_DU_VPS:/var/backups/uaz-don .
  ```
  (Si `scp` refuse l'accès, lancez d'abord sur le serveur : `sudo chmod -R o+rX /var/backups/uaz-don`.)

---

## Étape 12 (facultatif) — Notifications par e-mail

Pour recevoir un e-mail à chaque message ou promesse de don, le serveur doit pouvoir envoyer des
e-mails. Exemple avec une boîte e-mail Hostinger (`no-reply@zurcher.edu.mg`) :

```bash
sudo apt install -y msmtp-mta
sudo nano /etc/msmtprc
```

```ini
defaults
auth on
tls on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile /var/log/msmtp.log

account hostinger
host smtp.hostinger.com
port 465
tls_starttls off
from no-reply@zurcher.edu.mg
user no-reply@zurcher.edu.mg
password MOT_DE_PASSE_DE_LA_BOITE

account default : hostinger
```

```bash
sudo chown root:www-data /etc/msmtprc
sudo chmod 640 /etc/msmtprc
sudo touch /var/log/msmtp.log
sudo chown www-data:www-data /var/log/msmtp.log
```

Puis dans `app/config.php` : `'mail_notifications' => true,` et `'mail_from' => 'no-reply@zurcher.edu.mg',`
et renseignez l'e-mail destinataire dans l'admin (*Paramètres → Général*).

Sans cette étape, tous les messages restent consultables dans l'admin (rubrique *Messages*).

---

## Mettre à jour le site plus tard

1. *(sur votre PC)* Recréez l'archive (étape 6.1) et envoyez-la (étape 6.2).
2. *(sur le serveur)* :

```bash
cd /var/www/uaz-don
sudo unzip -o /tmp/uaz-don.zip -d /var/www/uaz-don
sudo php install/install.php
sudo chown -R www-data:www-data public/uploads storage
sudo systemctl reload php8.3-fpm
```

`install.php` ajoute les nouvelles tables, colonnes et paramètres **sans toucher aux données
existantes** ; l'archive n'écrase ni `app/config.php` ni les images téléversées (elles en sont exclues).

---

## Dépannage

| Symptôme | Cause probable | Solution |
|---|---|---|
| Le sous-domaine ne répond pas | DNS pas encore propagé ou mauvais enregistrement A | `nslookup don.zurcher.edu.mg 8.8.8.8` doit renvoyer l'IP du VPS |
| **502 Bad Gateway** | PHP-FPM arrêté ou mauvais nom de socket | `sudo systemctl status php8.3-fpm` ; vérifier `ls /run/php/` et la config Nginx |
| **413 Request Entity Too Large** | Envoi de photos trop lourd | `client_max_body_size` (Nginx) et `post_max_size` (PHP) |
| Page « Erreur interne » | Erreur PHP | `sudo tail -n 50 /var/log/nginx/error.log` ; mettre temporairement `'debug' => true` |
| « Configuration manquante » | `app/config.php` absent | Refaire l'étape 7 |
| Erreur de connexion à la base | Identifiants incorrects dans `app/config.php` | Tester : `mysql -u uaz_donation -p uaz_donation` |
| Impossible d'envoyer une image | Droits d'écriture | `sudo chown -R www-data:www-data /var/www/uaz-don/public/uploads` |
| « Image trop grande » | Photo de plus de 60 mégapixels | La réduire avant l'envoi (largeur < 6 000 px) |
| Certbot échoue | DNS pas propagé, ou port 80 fermé | Vérifier `nslookup`, `sudo ufw status` et le pare-feu hPanel |
| Mot de passe admin oublié | — | `sudo php install/install.php --email=... --password="nouveau"` |
| Animations absentes | GSAP/Lenis non chargés (CDN bloqué) | Le site reste utilisable ; vérifier la console du navigateur |

Journaux utiles :

```bash
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/nginx/access.log
sudo journalctl -u php8.3-fpm -n 50
```
