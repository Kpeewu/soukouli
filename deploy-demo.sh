#!/usr/bin/env bash
#
# Deploiement automatise d'une instance de DEMONSTRATION de Soukouli.
#
# Demande (ou recoit en option) le nom de domaine, le port de l'application et
# le port de la base de donnees ; le reste de la configuration est lu depuis
# .env. Le script force DEMO_MODE=true, charge le jeu de donnees fictives et
# branche la reinitialisation nocturne.
#
#   ATTENTION - Ce script est reserve aux vitrines publiques.
#   En mode demo, `php artisan demo:reset` EFFACE INTEGRALEMENT la base et
#   tourne automatiquement chaque nuit. Ne jamais l'executer sur
#   l'installation d'un etablissement : utilisez le guide INSTALLATION.md.
#
# Usage :
#   ./deploy-demo.sh                                  # mode interactif
#   ./deploy-demo.sh -d demo.soukouli.com -p 8080 --https
#   ./deploy-demo.sh --help
#
set -euo pipefail
cd "$(dirname "$0")"

ENV_FILE=".env"
ENV_EXAMPLE=".env.example"
OVERRIDE_FILE="docker-compose.override.yml"
NGINX_DIR="docker/nginx"
NGINX_BASE_CONF="${NGINX_DIR}/default.conf"
NGINX_VHOST=""   # docker/nginx/<domaine>.conf, connu apres saisie du domaine
MARKER="Genere par deploy-demo.sh - ne pas editer a la main."
BACKUP_DIR="./backups"
HEALTH_TIMEOUT=240

DOMAIN=""
APP_PORT=""
DB_PORT_HOST=""
SCHEME=""
APP_VERSION_ARG=""
RESET_TIME=""
AUTO_RESET=""
RESET_NOW=0
ASSUME_YES=0
FORCE_DEMO=0
EXPOSE_DB=""
BEHIND_PROXY=""
VPS_CONF_DIR="deploy/nginx-vps"
VPS_VHOST=""

# --- Sortie ---------------------------------------------------------------

if [ -t 1 ]; then
  C_RESET=$'\033[0m'; C_BOLD=$'\033[1m'; C_RED=$'\033[31m'
  C_GREEN=$'\033[32m'; C_YELLOW=$'\033[33m'; C_BLUE=$'\033[34m'
else
  C_RESET=""; C_BOLD=""; C_RED=""; C_GREEN=""; C_YELLOW=""; C_BLUE=""
fi

step()  { echo "${C_BLUE}${C_BOLD}==>${C_RESET} ${C_BOLD}$*${C_RESET}"; }
info()  { echo "    $*"; }
ok()    { echo "${C_GREEN}OK${C_RESET}  $*"; }
warn()  { echo "${C_YELLOW}ATTENTION${C_RESET} $*" >&2; }
fail()  { echo "${C_RED}ERREUR${C_RESET} $*" >&2; exit 1; }

usage() {
  cat <<'EOF'
Deploiement d'une instance de DEMONSTRATION de Soukouli.

Ce script force DEMO_MODE=true : donnees fictives, comptes de test publics
affiches dans une banniere, et base de donnees effacee/rechargee chaque nuit.
Ne jamais l'utiliser pour l'installation d'un etablissement (voir
INSTALLATION.md).

Usage : ./deploy-demo.sh [options]

Options :
  -d, --domain <domaine>   Nom de domaine ou adresse IP de la demo
                           (ex: demo.soukouli.com, 192.168.1.50, localhost)
  -p, --app-port <port>    Port HTTP publie pour l'application       (defaut 80)
      --db-port <port>     Port hote publie pour PostgreSQL, sur 127.0.0.1
                           uniquement (defaut 5432). NON NECESSAIRE au
                           fonctionnement : l'application joint la base par
                           le reseau Docker interne. A n'utiliser que pour
                           brancher un outil d'administration externe.
      --no-db-port         Ne publie aucun port pour la base (DEFAUT, et
                           recommande pour une vitrine exposee sur internet).
                           Pour une inspection ponctuelle, preferer :
                             docker compose exec db psql -U <user> <base>
      --https              Construit APP_URL en https:// au lieu de http://
                           (a utiliser derriere un reverse proxy TLS)
      --behind-proxy       Publie l'application sur 127.0.0.1 uniquement, et
                           genere le vhost nginx a installer sur l'hote
                           (deploy/nginx-vps/<domaine>.conf). A utiliser sur
                           un VPS ou nginx fait deja face a internet.
      --no-behind-proxy    Publie sur toutes les interfaces (defaut en local)
      --reset-time <HH:MM> Heure de la reinitialisation nocturne     (defaut 03:00)
      --no-auto-reset      Desactive la reinitialisation nocturne
      --reset              Recharge immediatement les donnees de demo
                           (efface la base existante)
  -v, --version <tag>      Version de l'image a deployer             (defaut latest)
  -y, --yes                Mode non interactif : aucune question, valeurs
                           fournies / existantes / par defaut
      --force              Autorise le passage en mode demo d'une instance
                           existante non-demo (DESTRUCTIF, voir ci-dessus)
  -h, --help               Affiche cette aide

Les autres valeurs (mot de passe PostgreSQL, SMTP, volumetrie de la demo...)
sont lues depuis .env, cree depuis .env.example au premier deploiement.
EOF
}

# --- Arguments ------------------------------------------------------------

while [ $# -gt 0 ]; do
  case "$1" in
    -d|--domain)     DOMAIN="${2:-}";          shift 2 ;;
    -p|--app-port)   APP_PORT="${2:-}";        shift 2 ;;
    --db-port)       DB_PORT_HOST="${2:-}"; EXPOSE_DB=1; shift 2 ;;
    --no-db-port)    EXPOSE_DB=0;              shift ;;
    --https)         SCHEME="https";           shift ;;
    --behind-proxy)    BEHIND_PROXY=1;          shift ;;
    --no-behind-proxy) BEHIND_PROXY=0;          shift ;;
    --reset-time)    RESET_TIME="${2:-}"; AUTO_RESET=1; shift 2 ;;
    --no-auto-reset) AUTO_RESET=0;             shift ;;
    --reset)         RESET_NOW=1;              shift ;;
    -v|--version)    APP_VERSION_ARG="${2:-}"; shift 2 ;;
    -y|--yes)        ASSUME_YES=1;             shift ;;
    --force)         FORCE_DEMO=1;             shift ;;
    -h|--help)       usage; exit 0 ;;
    *)               usage >&2; fail "Option inconnue : $1" ;;
  esac
done

# --- Helpers .env ---------------------------------------------------------

# Lit une valeur du .env sans le sourcer (evite d'executer son contenu et de
# se faire piéger par les valeurs contenant des espaces ou des guillemets).
get_env() {
  local key="$1" line
  [ -f "$ENV_FILE" ] || return 0
  line=$(grep -E "^${key}=" "$ENV_FILE" | tail -n 1 || true)
  line="${line#*=}"
  line="${line%\"}"; line="${line#\"}"
  line="${line%\'}"; line="${line#\'}"
  printf '%s' "$line"
}

# Ecrit une valeur dans le .env. On tronque/reecrit le fichier en place
# (`cat >`) plutot que de le remplacer par un rename : .env est bind-monte
# dans les conteneurs, et un remplacement atomique casserait le montage
# lorsque la pile tourne deja (cf. docker/entrypoint.sh).
set_env() {
  local key="$1" value="$2" tmp
  tmp=$(mktemp)
  if grep -qE "^${key}=" "$ENV_FILE"; then
    awk -v k="$key" -v v="$value" \
      'BEGIN { FS="=" } $1 == k && !done { print k "=" v; done=1; next } { print }' \
      "$ENV_FILE" > "$tmp"
  else
    cat "$ENV_FILE" > "$tmp"
    printf '%s=%s\n' "$key" "$value" >> "$tmp"
  fi
  cat "$tmp" > "$ENV_FILE"
  rm -f "$tmp"
}

# --- Sauvegardes ----------------------------------------------------------

# Copie horodatee de tout fichier que le script s'apprete a ecraser. Rien
# n'est jamais modifie en place sans qu'une copie existe d'abord.
backup_file() {
  local src="$1" dest
  [ -f "$src" ] || return 0
  mkdir -p "$BACKUP_DIR"
  dest="${BACKUP_DIR}/$(basename "$src").$(date +%Y%m%d-%H%M%S).bak"
  cp -p "$src" "$dest"
  info "Copie de sauvegarde : ${dest}"
}

# --- Validation -----------------------------------------------------------

valid_port() {
  case "$1" in ''|*[!0-9]*) return 1 ;; esac
  [ "$1" -ge 1 ] && [ "$1" -le 65535 ]
}

valid_domain() {
  printf '%s' "$1" | grep -qE '^[A-Za-z0-9]([A-Za-z0-9.-]*[A-Za-z0-9])?$'
}

valid_time() {
  printf '%s' "$1" | grep -qE '^([01][0-9]|2[0-3]):[0-5][0-9]$'
}

# Vrai si le port est deja occupe par un processus qui n'appartient pas a
# notre propre pile (un redeploiement reutilise legitimement son port).
port_busy_elsewhere() {
  local port="$1" pids
  command -v lsof >/dev/null 2>&1 || return 1
  pids=$(lsof -nP -iTCP:"$port" -sTCP:LISTEN -t 2>/dev/null || true)
  [ -n "$pids" ] || return 1
  if $COMPOSE ps --format '{{.Publishers}}' 2>/dev/null | grep -q ":${port}->"; then
    return 1
  fi
  return 0
}

ask() {
  local prompt="$1" default="$2" answer
  if [ "$ASSUME_YES" = "1" ]; then
    printf '%s' "$default"
    return
  fi
  read -r -p "$(printf '%s [%s] : ' "$prompt" "$default")" answer </dev/tty || answer=""
  printf '%s' "${answer:-$default}"
}

# --- 0. Avertissement -----------------------------------------------------

cat <<EOF
${C_YELLOW}${C_BOLD}
+--------------------------------------------------------------------+
|  INSTANCE DE DEMONSTRATION                                         |
|                                                                    |
|  Les donnees sont fictives et JETABLES : la base est effacee et    |
|  rechargee automatiquement chaque nuit, et les identifiants de     |
|  test sont affiches publiquement dans l'application.               |
|                                                                    |
|  Pour l'installation d'un etablissement, suivre INSTALLATION.md.   |
+--------------------------------------------------------------------+
${C_RESET}
EOF

# --- 1. Prerequis ---------------------------------------------------------

step "Verification des prerequis"

command -v docker >/dev/null 2>&1 \
  || fail "Docker n'est pas installe. Voir https://docs.docker.com/engine/install/"

if docker compose version >/dev/null 2>&1; then
  COMPOSE="docker compose"
elif command -v docker-compose >/dev/null 2>&1; then
  COMPOSE="docker-compose"
else
  fail "Le plugin Docker Compose est introuvable (paquet docker-compose-plugin)."
fi

docker info >/dev/null 2>&1 \
  || fail "Le demon Docker ne repond pas. Demarrez Docker puis relancez ce script."

[ -f "docker-compose.yml" ] \
  || fail "docker-compose.yml introuvable. Lancez ce script depuis le dossier du projet."

ok "Docker et Docker Compose sont operationnels."

# --- 2. Garde-fou : ne jamais convertir une instance client ---------------

# Un .env avec DEMO_MODE!=true et un volume de donnees deja cree signale une
# installation reelle. La basculer en mode demo la ferait effacer des la
# premiere reinitialisation nocturne.
if [ -f "$ENV_FILE" ] && [ "$(get_env DEMO_MODE)" != "true" ]; then
  if docker volume inspect soukouli_db_data >/dev/null 2>&1; then
    echo
    warn "Une base de donnees Soukouli existe deja et n'est PAS en mode demo."
    warn "La passer en mode demo effacerait definitivement toutes ses donnees."
    if [ "$FORCE_DEMO" != "1" ]; then
      fail "Abandon. S'il s'agit bien d'une instance jetable, relancez avec --force."
    fi
    if [ "$ASSUME_YES" != "1" ]; then
      read -r -p "Tapez EFFACER pour confirmer la destruction des donnees : " confirm </dev/tty || confirm=""
      [ "$confirm" = "EFFACER" ] || fail "Confirmation invalide. Abandon."
    fi
    warn "Poursuite en mode destructif a la demande explicite de l'operateur."
  fi
fi

# --- 3. Fichier .env ------------------------------------------------------

step "Preparation du fichier .env"

FRESH_ENV=0
if [ ! -f "$ENV_FILE" ]; then
  [ -f "$ENV_EXAMPLE" ] || fail "Ni .env ni .env.example n'existent dans ce dossier."
  cp "$ENV_EXAMPLE" "$ENV_FILE"
  chmod 600 "$ENV_FILE"
  FRESH_ENV=1
  ok "Fichier .env cree depuis .env.example."
else
  backup_file "$ENV_FILE"
  info "Fichier .env existant conserve et complete."
fi

# Sur une premiere installation seulement : un mot de passe de base fort.
# Le regenerer plus tard serait sans effet (PostgreSQL ne relit son mot de
# passe qu'a la creation du volume) et bloquerait le demarrage.
if [ "$FRESH_ENV" = "1" ]; then
  case "$(get_env DB_PASSWORD)" in
    ""|changeme*)
      if command -v openssl >/dev/null 2>&1; then
        set_env DB_PASSWORD "$(openssl rand -base64 33 | tr -d '/+=' | cut -c1-32)"
        ok "Mot de passe PostgreSQL genere aleatoirement."
      else
        warn "openssl est absent : renseignez DB_PASSWORD manuellement dans .env."
      fi
      ;;
  esac
fi

# --- 4. Parametres demandes a l'utilisateur -------------------------------

step "Parametres de deploiement"

CURRENT_URL=$(get_env APP_URL)
CURRENT_DOMAIN=$(printf '%s' "$CURRENT_URL" | sed -E 's#^[a-zA-Z]+://##; s#[:/].*$##')
[ -n "$CURRENT_DOMAIN" ] || CURRENT_DOMAIN="localhost"

if [ -z "$SCHEME" ]; then
  case "$CURRENT_URL" in
    https://*) SCHEME="https" ;;
    *)         SCHEME="http"  ;;
  esac
fi

if [ -z "$DOMAIN" ]; then
  DOMAIN=$(ask "Nom de domaine ou adresse IP de la demo" "$CURRENT_DOMAIN")
fi
valid_domain "$DOMAIN" \
  || fail "Nom de domaine invalide : '${DOMAIN}' (attendu : demo.exemple.com, 192.168.1.50, localhost)"

CURRENT_APP_PORT=$(get_env APP_PORT); [ -n "$CURRENT_APP_PORT" ] || CURRENT_APP_PORT=80
if [ -z "$APP_PORT" ]; then
  APP_PORT=$(ask "Port HTTP de l'application" "$CURRENT_APP_PORT")
fi
valid_port "$APP_PORT" || fail "Port applicatif invalide : '${APP_PORT}' (1-65535)"

# Port base de donnees : publie sur la boucle locale uniquement, et
# optionnel. Une demo est exposee sur internet, le defaut reste "non publie".
CURRENT_DB_PORT=$(get_env DB_PORT_HOST); [ -n "$CURRENT_DB_PORT" ] || CURRENT_DB_PORT=5432
if [ -z "$EXPOSE_DB" ]; then
  if [ "$ASSUME_YES" = "1" ]; then
    if [ -n "$(get_env DB_PORT_HOST)" ]; then EXPOSE_DB=1; else EXPOSE_DB=0; fi
  else
    info "La publication du port PostgreSQL n'est pas necessaire : l'application"
    info "joint la base par le reseau Docker interne. Repondre N sauf besoin"
    info "explicite d'un outil d'administration externe."
    answer=$(ask "Publier quand meme le port PostgreSQL sur l'hote ? [o/N]" "N")
    case "$answer" in [oOyY]*) EXPOSE_DB=1 ;; *) EXPOSE_DB=0 ;; esac
  fi
fi

if [ "$EXPOSE_DB" = "1" ]; then
  if [ -z "$DB_PORT_HOST" ]; then
    DB_PORT_HOST=$(ask "Port hote pour PostgreSQL" "$CURRENT_DB_PORT")
  fi
  valid_port "$DB_PORT_HOST" || fail "Port base de donnees invalide : '${DB_PORT_HOST}' (1-65535)"
  [ "$DB_PORT_HOST" != "$APP_PORT" ] \
    || fail "Le port de la base de donnees et celui de l'application doivent differer (${APP_PORT})."
fi

# Reinitialisation nocturne : active par defaut sur une demo.
if [ -z "$AUTO_RESET" ]; then
  if [ "$ASSUME_YES" = "1" ]; then
    AUTO_RESET=1
  else
    answer=$(ask "Reinitialiser automatiquement la demo chaque nuit ? [O/n]" "O")
    case "$answer" in [nN]*) AUTO_RESET=0 ;; *) AUTO_RESET=1 ;; esac
  fi
fi

if [ "$AUTO_RESET" = "1" ]; then
  if [ -z "$RESET_TIME" ]; then
    CURRENT_RESET_TIME=$(get_env DEMO_AUTO_RESET_TIME); [ -n "$CURRENT_RESET_TIME" ] || CURRENT_RESET_TIME="03:00"
    RESET_TIME=$(ask "Heure de la reinitialisation (fuseau du conteneur)" "$CURRENT_RESET_TIME")
  fi
  valid_time "$RESET_TIME" || fail "Heure invalide : '${RESET_TIME}' (format attendu HH:MM, ex: 03:00)"
fi

# Reverse proxy : sur un VPS expose, l'application ne doit ecouter que sur la
# boucle locale, sinon elle reste joignable en HTTP clair a cote du HTTPS.
if [ -z "$BEHIND_PROXY" ]; then
  if [ "$ASSUME_YES" = "1" ]; then
    case "$(get_env APP_BIND_IP)" in
      127.0.0.1|localhost) BEHIND_PROXY=1 ;;
      *)                   BEHIND_PROXY=0 ;;
    esac
  else
    case "$DOMAIN" in
      localhost|127.0.0.1|192.168.*|10.*) proxy_default="N" ;;
      *)                                  proxy_default="O" ;;
    esac
    info "Sur un serveur expose sur internet, l'application ne doit etre"
    info "joignable que par le reverse proxy (nginx) installe sur l'hote."
    answer=$(ask "Deployer derriere un reverse proxy local ? [O/n]" "$proxy_default")
    case "$answer" in [nN]*) BEHIND_PROXY=0 ;; *) BEHIND_PROXY=1 ;; esac
  fi
fi

if port_busy_elsewhere "$APP_PORT"; then
  warn "Le port ${APP_PORT} est deja utilise par un autre programme : le demarrage echouera probablement."
fi
if [ "$EXPOSE_DB" = "1" ] && port_busy_elsewhere "$DB_PORT_HOST"; then
  warn "Le port ${DB_PORT_HOST} est deja utilise par un autre programme."
fi

# En https, APP_PORT est le port *interne* sur lequel le reverse proxy TLS
# fait suivre les requetes : il n'a rien a faire dans l'URL publique, qui
# passe par 443. En http, on n'ajoute le port que s'il n'est pas implicite.
APP_URL="${SCHEME}://${DOMAIN}"
if [ "$SCHEME" = "http" ] && [ "$APP_PORT" != "80" ]; then
  APP_URL="${APP_URL}:${APP_PORT}"
fi

# --- 5. Ecriture de la configuration --------------------------------------

step "Ecriture de la configuration"

set_env APP_URL "$APP_URL"
set_env APP_PORT "$APP_PORT"

# Interface de publication : c'est ce reglage, et non le pare-feu, qui empeche
# l'acces direct depuis internet. Les regles Docker sont evaluees avant celles
# d'UFW : un port publie sur 0.0.0.0 reste joignable malgre un "ufw deny".
if [ "$BEHIND_PROXY" = "1" ]; then
  set_env APP_BIND_IP "127.0.0.1"
else
  set_env APP_BIND_IP "0.0.0.0"
fi
[ -n "$APP_VERSION_ARG" ] && set_env APP_VERSION "$APP_VERSION_ARG"

# Reglages propres a la demo.
set_env DEMO_MODE "true"
set_env DEMO_AUTO_RESET "$([ "$AUTO_RESET" = "1" ] && echo true || echo false)"
[ "$AUTO_RESET" = "1" ] && set_env DEMO_AUTO_RESET_TIME "$RESET_TIME"

# Durcissement : une demo est publique, rien ne doit fuiter de l'interne.
set_env APP_ENV "production"
set_env APP_DEBUG "false"
set_env DEBUGBAR_ENABLED "false"

# En mode demo, DemoSeeder cree les comptes de test de config/demo.php :
# INITIAL_ADMIN_PASSWORD n'est pas utilise, on le laisse vide pour eviter de
# laisser croire qu'il pilote le compte admin de la vitrine.
set_env INITIAL_ADMIN_PASSWORD ""

if [ "$EXPOSE_DB" = "1" ]; then
  set_env DB_PORT_HOST "$DB_PORT_HOST"
else
  set_env DB_PORT_HOST ""
fi

# DB_PORT reste le port *interne* du conteneur postgres : l'application le
# joint via le reseau compose, il ne suit pas le port publie sur l'hote.
if [ "$(get_env DB_PORT)" != "5432" ]; then
  set_env DB_PORT 5432
  info "DB_PORT ramene a 5432 (port interne du conteneur, distinct du port publie)."
fi

# --- Configuration nginx propre a la demo ---------------------------------

step "Generation de la configuration nginx"

[ -f "$NGINX_BASE_CONF" ] \
  || fail "${NGINX_BASE_CONF} introuvable : le depot semble incomplet."

# Hote virtuel dedie, nomme d'apres le domaine, charge par nginx a cote de
# default.conf (tout /etc/nginx/conf.d/*.conf est inclus dans le bloc http).
# default.conf n'est ni modifie ni demonte : il reste la configuration de
# reference des installations client.
NGINX_VHOST="${NGINX_DIR}/${DOMAIN}.conf"

# Un redeploiement sur un autre domaine laisserait l'ancien vhost derriere
# lui : nginx chargerait deux hotes concurrents avec des zones limit_req
# homonymes et refuserait de demarrer.
for stale in "$NGINX_DIR"/*.conf; do
  [ -f "$stale" ] || continue
  [ "$stale" = "$NGINX_BASE_CONF" ] && continue
  [ "$stale" = "$NGINX_VHOST" ] && continue
  if grep -qF "$MARKER" "$stale"; then
    backup_file "$stale"
    rm -f "$stale"
    info "Ancien vhost genere retire : $(basename "$stale")"
  fi
done

backup_file "$NGINX_VHOST"

cat > "$NGINX_VHOST" <<EOF
# ${MARKER}
# Hote virtuel de l'instance de DEMONSTRATION : ${DOMAIN}
#
# Charge par nginx en complement de default.conf (inclusion de
# /etc/nginx/conf.d/*.conf). default.conf reste intact : c'est la
# configuration de reference des installations client.

# Limitation de debit : la demo est publique et ses identifiants aussi.
# Declaree au niveau http (conf.d/ est inclus dans le bloc http{}).
limit_req_zone \$binary_remote_addr zone=demo_general:10m rate=20r/s;
limit_req_zone \$binary_remote_addr zone=demo_login:10m rate=10r/m;
limit_req_status 429;

# Schema d'origine transmis a PHP-FPM. Indispensable derriere un reverse
# proxy TLS : l'application ne declare aucun proxy de confiance
# (app/Http/Middleware/TrustProxies.php), elle ignore donc
# X-Forwarded-Proto et generait sans cela des redirections en http://
# depuis une page servie en https.
map \$http_x_forwarded_proto \$fastcgi_https {
    default "";
    https   on;
}

# Journaliser l'IP du visiteur et non celle du proxy (plages Docker/RFC1918).
set_real_ip_from 10.0.0.0/8;
set_real_ip_from 172.16.0.0/12;
set_real_ip_from 192.168.0.0/16;
real_ip_header X-Forwarded-For;

server {
    # default_server : sans lui, seules les requetes portant exactement
    # l'en-tete Host "${DOMAIN}" atterrissent ici ; un acces par l'adresse IP
    # du serveur retomberait sur default.conf (server_name _) et servirait la
    # demo SANS desindexation ni limitation de debit. Cet hote n'heberge que
    # la demo : on capte donc aussi tout le trafic non appareille.
    # A retirer si default.conf doit reprendre la main sur les autres hotes.
    listen 80 default_server;
    listen [::]:80 default_server;
    server_name ${DOMAIN};
    root /var/www/html/public;
    index index.php;

    client_max_body_size 25m;

    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    # Une vitrine ne doit ni concurrencer le site reel dans les moteurs de
    # recherche, ni y laisser indexer ses donnees fictives.
    add_header X-Robots-Tag "noindex, nofollow, noarchive" always;

    location / {
        limit_req zone=demo_general burst=40 nodelay;
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    # Principal vecteur d'abus sur une demo dont les mots de passe sont
    # affiches publiquement : on plafonne les tentatives de connexion.
    location = /login {
        limit_req zone=demo_login burst=5 nodelay;
        try_files \$uri /index.php?\$query_string;
    }

    # Sonde de sante du conteneur : jamais limitee, sinon le healthcheck
    # echouerait des que la demo recoit du trafic.
    location = /up {
        access_log off;
        try_files \$uri /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }

    # robots.txt servi directement par nginx : l'image livre un fichier
    # "Disallow:" (tout autorise) adapte a une installation client, ce qui
    # laisserait indexer la demo.
    location = /robots.txt {
        access_log off;
        # default_type et non add_header Content-Type : ce dernier ferait
        # doublon avec l'en-tete de la reponse et, surtout, tout add_header
        # dans un bloc annule l'heritage de ceux du bloc parent (les en-tetes
        # de securite ci-dessus disparaitraient de cette reponse).
        default_type text/plain;
        return 200 "User-agent: *\\nDisallow: /\\n";
    }

    location ~ \.php\$ {
        fastcgi_pass app:9000;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;
        fastcgi_param PATH_INFO \$fastcgi_path_info;
        fastcgi_param HTTPS \$fastcgi_https;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 32k;
        fastcgi_buffers 8 16k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    location ~* \.(css|js|png|jpe?g|gif|svg|ico|woff2?|ttf|map)\$ {
        expires 30d;
        access_log off;
        add_header Cache-Control "public, immutable";
        # add_header n'est pas herite des que le bloc en declare un.
        add_header X-Robots-Tag "noindex, nofollow, noarchive" always;
    }
}
EOF

ok "Hote virtuel genere : ${NGINX_VHOST} (server_name ${DOMAIN})."

# --- Vhost nginx de l'hote (reverse proxy) --------------------------------

# Genere mais JAMAIS installe : ecrire dans /etc/nginx demande les droits
# root et remplacerait une configuration systeme existante. Le fichier est
# depose dans le projet, l'operateur l'installe et recharge nginx lui-meme.
if [ "$BEHIND_PROXY" = "1" ]; then
  step "Generation du vhost nginx pour l'hote"

  mkdir -p "$VPS_CONF_DIR"
  VPS_VHOST="${VPS_CONF_DIR}/${DOMAIN}.conf"
  backup_file "$VPS_VHOST"

  cat > "$VPS_VHOST" <<EOF
# ${MARKER}
# Reverse proxy nginx de l'HOTE (VPS) pour ${DOMAIN}.
#
# A installer sur le serveur, hors du depot :
#   sudo cp ${DOMAIN}.conf /etc/nginx/sites-available/${DOMAIN}
#   sudo ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/
#   sudo nginx -t && sudo systemctl reload nginx
#
# Puis obtenir le certificat (certbot ajoute lui-meme le bloc 443 et la
# redirection HTTP -> HTTPS dans ce fichier) :
#   sudo certbot --nginx -d ${DOMAIN}
#
# Prerequis : un enregistrement DNS A pointant ${DOMAIN} vers ce serveur,
# sans quoi la validation Let's Encrypt echouera.

server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    # Taille des televersements : doit valoir au moins celle du nginx
    # conteneurise (25m), sinon le proxy rejette l'envoi avant lui avec
    # une 413 (le defaut nginx est 1m).
    client_max_body_size 25m;

    access_log /var/log/nginx/${DOMAIN}.access.log;
    error_log  /var/log/nginx/${DOMAIN}.error.log;

    location / {
        proxy_pass http://127.0.0.1:${APP_PORT};
        proxy_http_version 1.1;

        proxy_set_header Host              \$host;
        proxy_set_header X-Real-IP         \$remote_addr;
        proxy_set_header X-Forwarded-For   \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Host  \$host;
        # Indispensable : c'est cet en-tete que le vhost conteneurise
        # convertit en HTTPS=on pour PHP-FPM. Sans lui, l'application
        # generait des redirections en http:// depuis une page https://.
        proxy_set_header X-Forwarded-Proto \$scheme;

        # La generation des bulletins/recus PDF (LaTeX) peut depasser la
        # minute : le conteneur autorise 300s, on s'aligne pour ne pas
        # couper la reponse cote proxy (504).
        proxy_connect_timeout 30s;
        proxy_send_timeout    300s;
        proxy_read_timeout    300s;
    }
}
EOF

  ok "Vhost hote genere : ${VPS_VHOST}"
fi

# --- Surcouche compose ----------------------------------------------------

# Le docker-compose.yml de reference monte default.conf et n'expose aucun
# port de base de donnees : les specificites de la demo passent par cette
# surcouche, jamais par une modification des fichiers versionnes.
if [ -f "$OVERRIDE_FILE" ] && ! grep -qF "$MARKER" "$OVERRIDE_FILE"; then
  backup_file "$OVERRIDE_FILE"
  warn "${OVERRIDE_FILE} existant (non genere par ce script) : il va etre remplace."
fi

{
  echo "# ${MARKER}"
  echo "# Specificites de l'instance de demonstration."
  echo "services:"
  echo "  webserver:"
  echo "    volumes:"
  echo "      # Ajoute l'hote virtuel de la demo a cote de default.conf, que"
  echo "      # docker-compose.yml continue de monter (cibles distinctes :"
  echo "      # compose fusionne les volumes par chemin cible, donc les deux"
  echo "      # montages coexistent)."
  echo "      - ./${NGINX_VHOST}:/etc/nginx/conf.d/${DOMAIN}.conf:ro"
  if [ "$EXPOSE_DB" = "1" ]; then
    echo "  db:"
    echo "    ports:"
    echo "      # Boucle locale uniquement : injoignable depuis internet."
    echo "      - \"127.0.0.1:\${DB_PORT_HOST}:5432\""
  fi
} > "$OVERRIDE_FILE"

if [ "$EXPOSE_DB" = "1" ]; then
  warn "PostgreSQL sera publie sur 127.0.0.1:${DB_PORT_HOST} (non requis - voir --no-db-port)."
fi

echo
info "Application   : ${APP_URL}"
if [ "$BEHIND_PROXY" = "1" ]; then
  info "Ecoute locale : 127.0.0.1:${APP_PORT} (derriere le nginx de l'hote)"
else
  info "Ecoute        : 0.0.0.0:${APP_PORT} (toutes interfaces)"
fi
if [ "$EXPOSE_DB" = "1" ]; then
  info "Base          : 127.0.0.1:${DB_PORT_HOST}"
else
  info "Base          : non publiee (reseau Docker interne uniquement)"
fi
info "Mode demo     : actif"
if [ "$AUTO_RESET" = "1" ]; then
  info "Reset nocturne: chaque jour a ${RESET_TIME}"
else
  info "Reset nocturne: desactive"
fi
info "Volumetrie    : $(get_env DEMO_CLASSES_PAR_PROMOTION) classe(s)/promotion, $(get_env DEMO_ELEVES_MIN)-$(get_env DEMO_ELEVES_MAX) eleves/classe"
info "Image         : $(get_env DOCKER_IMAGE):$(get_env APP_VERSION)"
echo

if [ "$ASSUME_YES" != "1" ]; then
  confirm=$(ask "Lancer le deploiement avec ces parametres ? [O/n]" "O")
  case "$confirm" in [nN]*) fail "Deploiement annule. Le fichier .env a deja ete mis a jour." ;; esac
fi

# --- 6. Demarrage ---------------------------------------------------------

step "Telechargement des images"
$COMPOSE pull

step "Demarrage des services"
# Pas de sauvegarde prealable : les donnees d'une demo sont fictives et
# regenerees a chaque reinitialisation.
$COMPOSE up -d --remove-orphans

# --- 7. Verification ------------------------------------------------------

step "Verification de l'etat de l'application (jusqu'a ${HEALTH_TIMEOUT}s)"
info "Le premier demarrage inclut le chargement des donnees de demonstration,"
info "ce qui peut prendre une a deux minutes."

HEALTHY=0
DEADLINE=$(( $(date +%s) + HEALTH_TIMEOUT ))
while [ "$(date +%s)" -lt "$DEADLINE" ]; do
  CID=$($COMPOSE ps -q webserver 2>/dev/null || true)
  if [ -n "$CID" ]; then
    STATUS=$(docker inspect -f '{{.State.Health.Status}}' "$CID" 2>/dev/null || echo "unknown")
    if [ "$STATUS" = "healthy" ]; then HEALTHY=1; break; fi
    if [ "$(docker inspect -f '{{.State.Running}}' "$CID" 2>/dev/null)" != "true" ]; then
      warn "Le conteneur webserver s'est arrete."
      break
    fi
  fi
  sleep 3
done

if [ "$HEALTHY" != "1" ]; then
  echo
  warn "L'application ne repond pas encore apres ${HEALTH_TIMEOUT}s."
  info "Diagnostic :"
  info "  $COMPOSE ps"
  info "  $COMPOSE logs --tail=80 app"
  info "  $COMPOSE logs --tail=40 webserver"
  info "Une demo etant jetable, le plus simple en cas de blocage est de repartir a zero :"
  info "  $COMPOSE down -v && ./deploy-demo.sh -d ${DOMAIN} -p ${APP_PORT}"
  exit 1
fi

ok "Les services sont demarres et l'application repond."

# --- 8. Rechargement immediat des donnees (--reset) -----------------------

if [ "$RESET_NOW" = "1" ]; then
  step "Rechargement des donnees de demonstration"
  $COMPOSE exec -T app php artisan demo:reset --force
  ok "Jeu de donnees de demonstration recharge."
fi

# --- 9. Recapitulatif -----------------------------------------------------

echo
echo "${C_GREEN}${C_BOLD}Demo deployee.${C_RESET}"
echo
echo "  Application : ${C_BOLD}${APP_URL}${C_RESET}"
if [ "$EXPOSE_DB" = "1" ]; then
  echo "  Base        : 127.0.0.1:${DB_PORT_HOST} (base $(get_env DB_DATABASE), utilisateur $(get_env DB_USERNAME))"
fi
echo

# Les comptes de test sont publics et definis dans config/demo.php : ils sont
# deja affiches par la banniere de l'application, on les rappelle ici.
echo "${C_BOLD}Comptes de test${C_RESET} (egalement affiches dans la banniere de l'application) :"
if [ -f "config/demo.php" ]; then
  grep -oE "\['role' => '[^']+',[[:space:]]*'login' => '[^']+',[[:space:]]*'password' => '[^']+'\]" config/demo.php \
    | sed -E "s/\['role' => '([^']+)',[[:space:]]*'login' => '([^']+)',[[:space:]]*'password' => '([^']+)'\]/  \1|\2|\3/" \
    | awk -F'|' '{ printf "  %-16s %-20s %s\n", $1, $2, $3 }'
else
  info "(config/demo.php introuvable - voir la banniere dans l'application)"
fi

echo
if [ "$AUTO_RESET" = "1" ]; then
  info "La base est effacee et rechargee chaque nuit a ${RESET_TIME}."
  info "L'application renvoie des erreurs pendant ~30s le temps de la recreation du schema."
else
  warn "La reinitialisation nocturne est desactivee : les donnees modifiees par les"
  warn "visiteurs s'accumuleront. Rechargement manuel : ./deploy-demo.sh --reset"
fi

echo
if [ "$BEHIND_PROXY" = "1" ]; then
  echo "${C_BOLD}Etapes restantes, cote hote (le script n'y touche pas) :${C_RESET}"
  echo "  1. DNS : un enregistrement A pour ${DOMAIN} vers l'IP de ce serveur."
  echo "  2. Installer le vhost genere :"
  echo "       sudo cp ${VPS_VHOST} /etc/nginx/sites-available/${DOMAIN}"
  echo "       sudo ln -s /etc/nginx/sites-available/${DOMAIN} /etc/nginx/sites-enabled/"
  echo "       sudo nginx -t && sudo systemctl reload nginx"
  echo "  3. Certificat TLS :"
  echo "       sudo certbot --nginx -d ${DOMAIN}"
  if [ "$SCHEME" = "http" ]; then
    echo "  4. Une fois le certificat en place, rebasculer APP_URL en https :"
    echo "       ./deploy-demo.sh --https -d ${DOMAIN} -p ${APP_PORT} --behind-proxy"
  fi
  echo
  info "L'application n'ecoute que sur 127.0.0.1:${APP_PORT} : elle n'est pas"
  info "joignable depuis internet tant que le proxy n'est pas en place."
elif [ "$SCHEME" = "http" ] && [ "$DOMAIN" != "localhost" ] && [ "$DOMAIN" != "127.0.0.1" ]; then
  warn "La demo est servie en HTTP non chiffre et publiee sur toutes les interfaces."
  warn "Pour une vitrine publique, relancez avec :"
  warn "  ./deploy-demo.sh -d ${DOMAIN} -p ${APP_PORT} --behind-proxy"
fi
info "Journaux         : $COMPOSE logs -f app"
info "Recharger la demo: $COMPOSE exec app php artisan demo:reset --force"
info "Acces base       : $COMPOSE exec db psql -U $(get_env DB_USERNAME) $(get_env DB_DATABASE)"
info "Vhost nginx      : ${NGINX_VHOST} (charge avec ${NGINX_BASE_CONF})"
info "Arret            : $COMPOSE down"
info "Remise a zero    : $COMPOSE down -v"
