#!/bin/sh
set -eu

cd /var/www/html

wait_for_db() {
  echo "En attente de la base de donnees sur ${DB_HOST}:${DB_PORT:-5432}..."
  until PGPASSWORD="${DB_PASSWORD}" pg_isready -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -q; do
    sleep 2
  done
  echo "Base de donnees disponible."
}

ensure_storage_skeleton() {
  mkdir -p storage/app/public storage/app/recus \
           storage/framework/cache/data storage/framework/sessions \
           storage/framework/testing storage/framework/views \
           storage/logs bootstrap/cache
  chmod -R 775 storage bootstrap/cache || true
}

ensure_app_key() {
  # .env est monte depuis l'hote : une cle generee ici persiste a travers
  # tous les futurs `docker compose pull && up -d`. Ne jamais regenerer si
  # une cle existe deja, sous peine de casser le chiffrement des donnees
  # existantes du client.
  if ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    echo "Aucune APP_KEY trouvee - generation (premiere installation uniquement)..."
    php artisan key:generate --force
  fi
  if grep -q '^HASHIDS_SALT=changeme' .env 2>/dev/null || ! grep -q '^HASHIDS_SALT=' .env 2>/dev/null; then
    echo "Generation du HASHIDS_SALT (premiere installation uniquement)..."
    SALT=$(openssl rand -hex 32)
    if grep -q '^HASHIDS_SALT=' .env; then
      # .env est un bind mount fichier-unique : `sed -i` echoue avec "Device
      # or resource busy" car son remplacement atomique repose sur un rename()
      # par-dessus le point de montage. On ecrit plutot dans un fichier
      # temporaire puis on tronque/reecrit le fichier monte en place (cat >),
      # ce qui reste une simple ecriture, autorisee sur un bind mount.
      sed "s/^HASHIDS_SALT=.*/HASHIDS_SALT=${SALT}/" .env > /tmp/env.new
      cat /tmp/env.new > .env
      rm -f /tmp/env.new
    else
      echo "HASHIDS_SALT=${SALT}" >> .env
    fi
  fi
}

refresh_public_assets() {
  # Resynchronise la copie figee de public/ (assets compiles + symlink
  # storage) dans le volume partage avec nginx, pour qu'aucune ancienne
  # version de JS/CSS ne persiste apres une mise a jour.
  rsync -a --delete /opt/public-dist/ /var/www/html/public/
}

ROLE="${CONTAINER_ROLE:-app}"

ensure_storage_skeleton
ensure_app_key
wait_for_db

if [ "$ROLE" = "app" ]; then
  refresh_public_assets

  echo "Execution des migrations..."
  php artisan migrate --force

  # Detection d'une installation vierge via l'etat de la base elle-meme
  # (plus fiable qu'un fichier sentinelle si seul un des deux volumes,
  # BD ou storage, est reinitialise independamment de l'autre).
  USER_COUNT=$(PGPASSWORD="${DB_PASSWORD}" psql -h "${DB_HOST}" -p "${DB_PORT:-5432}" -U "${DB_USERNAME}" -d "${DB_DATABASE}" -tAc "SELECT COUNT(*) FROM users" 2>/dev/null || echo 0)

  # Le defaut ":-false" est obligatoire : `set -eu` fait echouer le conteneur
  # sur une variable non definie, et DEMO_MODE est absent des .env existants.
  if [ "${DEMO_MODE:-false}" = "true" ]; then
    if [ "${USER_COUNT}" = "0" ]; then
      # DemoSeeder cree lui-meme les roles, l'annee scolaire et les cycles :
      # il remplace ProductionSeeder, on n'enchaine pas les deux.
      echo ">>> DEMO_MODE actif - chargement des donnees de demonstration..."
      php artisan demo:seed --force
    else
      echo ">>> DEMO_MODE actif - base deja peuplee, seeding ignore."
      echo ">>> Pour repartir a neuf : docker compose exec app php artisan demo:reset --force"
    fi
  elif [ "${USER_COUNT}" = "0" ]; then
    echo ">>> Installation vierge detectee - creation des donnees de reference et du compte admin..."
    php artisan db:seed --class="Database\\Seeders\\ProductionSeeder" --force
  fi

  echo "Mise en cache config/routes/vues..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache
  php artisan event:cache || true

  exec "$@"
else
  # Role scheduler : l'installation, les migrations et le seeding sont deja
  # geres par le conteneur "app" (depends_on: service_healthy garantit
  # l'ordre). Pas de mise en cache ici : schedule:run tourne une fois par
  # minute, le gain de perf ne justifie pas un second jeu de caches a
  # synchroniser entre conteneurs.
  exec "$@"
fi
