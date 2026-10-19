#!/usr/bin/env bash
# Sauvegarde quotidienne : base de données + images téléversées. Conserve 14 jours.
# Installation : sudo crontab -e  →  30 2 * * * /var/www/uaz-don/deploy/backup.sh
set -euo pipefail

APP_DIR=/var/www/uaz-don
DEST=/var/backups/uaz-don
DB_NAME=uaz_donation
STAMP=$(date +%F)

mkdir -p "$DEST"
# Identifiants lus dans /root/.my.cnf ([mysqldump] user=… password=…)
mysqldump --single-transaction --default-character-set=utf8mb4 "$DB_NAME" | gzip > "$DEST/db-$STAMP.sql.gz"
tar -czf "$DEST/uploads-$STAMP.tar.gz" -C "$APP_DIR/public" uploads
find "$DEST" -type f -mtime +14 -delete
