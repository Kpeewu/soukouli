#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"

BACKUP_DIR="./backups"
KEEP=14
TIMESTAMP=$(date +%Y%m%d-%H%M%S)

if [ ! -f .env ]; then
  echo "ERREUR: fichier .env introuvable. Copiez .env.example vers .env et configurez-le avant de continuer."
  exit 1
fi

# Optionnel : ./update.sh v1.3.0  -> fixe APP_VERSION avant de recuperer l'image
if [ "${1:-}" != "" ]; then
  NEW_VERSION="$1"
  echo "Fixation de la version cible sur ${NEW_VERSION}..."
  if grep -q '^APP_VERSION=' .env; then
    sed -i.bak "s/^APP_VERSION=.*/APP_VERSION=${NEW_VERSION}/" .env && rm -f .env.bak
  else
    echo "APP_VERSION=${NEW_VERSION}" >> .env
  fi
fi

mkdir -p "$BACKUP_DIR"
source .env

echo "==> Sauvegarde de la base de donnees avant mise a jour..."
docker compose exec -T db pg_dump -U "${DB_USERNAME}" "${DB_DATABASE}" \
  | gzip > "${BACKUP_DIR}/pre-update-${TIMESTAMP}.sql.gz"
echo "    Sauvegarde ecrite: ${BACKUP_DIR}/pre-update-${TIMESTAMP}.sql.gz"

echo "==> Purge des sauvegardes de plus de ${KEEP} jours..."
find "$BACKUP_DIR" -name '*.sql.gz' -mtime +"${KEEP}" -delete

echo "==> Telechargement de la nouvelle version..."
docker compose pull

echo "==> Redemarrage des services (migrations appliquees automatiquement)..."
docker compose up -d

echo "==> Verification de l'etat des services (jusqu'a 90s)..."
for i in $(seq 1 30); do
  APP_CID=$(docker compose ps -q app)
  if [ -n "$APP_CID" ] && [ "$(docker inspect -f '{{.State.Health.Status}}' "$APP_CID" 2>/dev/null)" = "healthy" ]; then
    echo "OK: mise a jour terminee, l'application repond."
    exit 0
  fi
  sleep 3
done

echo "ATTENTION: l'application ne repond pas apres la mise a jour."
echo "En cas de probleme, restaurez la sauvegarde precedente:"
echo "  gunzip -c ${BACKUP_DIR}/pre-update-${TIMESTAMP}.sql.gz | docker compose exec -T db psql -U ${DB_USERNAME} ${DB_DATABASE}"
echo "puis revenez a l'ancienne image dans .env (APP_VERSION=...) et relancez: docker compose up -d"
exit 1
