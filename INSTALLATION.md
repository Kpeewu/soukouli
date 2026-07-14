# Guide d'installation - Soukouli

Ce guide explique comment installer, démarrer, mettre à jour et sauvegarder l'application Soukouli sur votre propre serveur ou ordinateur, à l'aide de Docker.

Aucune connaissance de programmation n'est nécessaire : toutes les commandes à exécuter sont indiquées telles quelles. Un minimum d'aisance avec un terminal (ligne de commande) est recommandé pour l'installation initiale.

---

## Sommaire

1. [Prérequis](#1-prérequis)
2. [Récupérer les fichiers de déploiement](#2-récupérer-les-fichiers-de-déploiement)
3. [Configurer l'application](#3-configurer-lapplication)
4. [Démarrer l'application](#4-démarrer-lapplication)
5. [Première connexion](#5-première-connexion)
6. [Configuration de l'établissement](#6-configuration-de-létablissement)
7. [Mettre à jour l'application](#7-mettre-à-jour-lapplication)
8. [Sauvegardes et restauration](#8-sauvegardes-et-restauration)
9. [Arrêter / redémarrer l'application](#9-arrêter--redémarrer-lapplication)
10. [Dépannage](#10-dépannage)
11. [Recommandations de sécurité](#11-recommandations-de-sécurité)
12. [Support](#12-support)

---

## 1. Prérequis

### Matériel

- Un serveur ou ordinateur qui reste allumé et connecté au réseau local pendant les heures d'utilisation (idéalement en continu).
- Au moins 2 Go de RAM disponibles et 5 Go d'espace disque libre.
- Un système Linux, macOS ou Windows (avec WSL2) à jour.

### Logiciels

Seul **Docker** est nécessaire. Il installe et gère automatiquement tout le reste (serveur web, base de données, PHP...).

- **Windows / macOS** : installez [Docker Desktop](https://www.docker.com/products/docker-desktop/).
- **Linux** : installez [Docker Engine](https://docs.docker.com/engine/install/) ainsi que le plugin Docker Compose (`docker-compose-plugin`).

Vérifiez l'installation en ouvrant un terminal et en tapant :

```bash
docker --version
docker compose version
```

Les deux commandes doivent afficher un numéro de version sans erreur.

---

## 2. Récupérer les fichiers de déploiement

Vous avez besoin de 3 fichiers/dossiers, disponibles dans le dépôt du projet :

- `docker-compose.yml`
- `docker/nginx/default.conf`
- `.env.example`

Le plus simple est de récupérer l'ensemble du dépôt :

```bash
git clone https://github.com/Kpeewu/soukouli.git soukouli
cd soukouli
```

> Si `git` n'est pas installé sur votre machine, vous pouvez aussi télécharger le dépôt sous forme d'archive ZIP depuis la page GitHub du projet ("Code" > "Download ZIP"), puis l'extraire.

Vous n'avez pas besoin de construire l'application vous-même : elle est déjà prête à l'emploi sur le registre public Docker Hub (`kpeewu/soukouli`), et sera téléchargée automatiquement au démarrage.

---

## 3. Configurer l'application

Dans le dossier du projet, copiez le fichier d'exemple :

```bash
cp .env.example .env
```

Ouvrez ensuite le fichier `.env` avec un éditeur de texte et modifiez au minimum les valeurs suivantes :

| Variable | Description | Exemple |
|---|---|---|
| `APP_URL` | Adresse à laquelle l'application sera accessible | `http://192.168.1.50` ou `http://mon-ecole.exemple.com` |
| `DB_PASSWORD` | Mot de passe de la base de données. **À changer obligatoirement** | une phrase longue et unique |
| `APP_PORT` | Port réseau sur lequel l'application répond | `80` (défaut) |

Les autres valeurs (`HASHIDS_SALT`, `APP_KEY`) n'ont **rien à faire manuellement** : elles sont générées automatiquement et en toute sécurité au premier démarrage, tant qu'elles sont laissées à leur valeur par défaut (`changeme...` ou vide) dans `.env`.

Vous pouvez éventuellement fixer vous-même le mot de passe du compte administrateur initial en renseignant `INITIAL_ADMIN_PASSWORD`. Si vous le laissez vide, un mot de passe sera généré aléatoirement et affiché une seule fois au premier démarrage (voir [section 5](#5-première-connexion)).

> ⚠️ Le fichier `.env` contient des informations sensibles (mots de passe). Ne le partagez jamais et ne le publiez jamais sur un dépôt public.

---

## 4. Démarrer l'application

Toujours dans le dossier du projet, lancez :

```bash
docker compose pull
docker compose up -d
```

La première commande télécharge les images (application, base de données, serveur web) : cela peut prendre plusieurs minutes selon votre connexion internet. La seconde démarre l'ensemble des services en arrière-plan.

Au premier démarrage, l'application :

1. crée automatiquement la base de données et sa structure ;
2. crée les données de référence du système éducatif togolais (cycles Maternelle/Primaire/Collège/Lycée, examens officiels CEPD/BEPC/BAC, matières de base, année scolaire en cours) ;
3. crée un compte administrateur initial.

Vous pouvez suivre le déroulement avec :

```bash
docker compose logs -f app
```

(`Ctrl+C` pour quitter l'affichage des journaux — cela n'arrête pas l'application).

Une fois le démarrage terminé, vérifiez que tout fonctionne :

```bash
curl http://localhost/up
```

Cette commande doit répondre `OK`. Si vous avez changé `APP_PORT`, remplacez `localhost` par l'adresse et le port correspondants.

---

## 5. Première connexion

Récupérez le mot de passe administrateur généré lors du premier démarrage :

```bash
docker compose logs app | grep -A4 "COMPTE ADMINISTRATEUR"
```

Vous obtiendrez un bloc de ce type :

```
COMPTE ADMINISTRATEUR INITIAL CREE
Identifiant : admin
Mot de passe : ***************
```

> Si vous avez défini `INITIAL_ADMIN_PASSWORD` dans le fichier `.env` avant le premier démarrage, c'est ce mot de passe qui a été utilisé à la place.

Rendez-vous ensuite sur `http://<adresse-de-votre-serveur>` (ou `http://localhost` en local), connectez-vous avec l'identifiant `admin` et le mot de passe récupéré.

**Changez ce mot de passe dès la première connexion**, depuis le menu "Mon profil" en haut à droite.

---

## 6. Configuration de l'établissement

Une fois connecté en tant qu'administrateur :

1. Rendez-vous dans **Paramètres** pour renseigner le nom de votre établissement, son logo, ses coordonnées, etc.
2. Rendez-vous dans **Utilisateurs** pour créer les comptes réels de votre équipe (directeurs, comptables, secrétaires, surveillants, professeurs) — le compte `admin` initial ne sert qu'à l'administration système, pas à la gestion scolaire courante.
3. Consultez le changelog (icône `v1.1.0` en bas de la barre latérale) pour voir le détail des fonctionnalités disponibles dans cette version.

---

## 7. Mettre à jour l'application

Un script est fourni pour automatiser les mises à jour en toute sécurité :

```bash
./update.sh
```

Ce script :

1. sauvegarde automatiquement votre base de données avant toute modification (dans le dossier `./backups`) ;
2. télécharge la dernière version de l'application ;
3. redémarre les services (les nouvelles migrations de base de données sont appliquées automatiquement) ;
4. vérifie que l'application répond correctement après la mise à jour.

Pour installer une version précise plutôt que la plus récente :

```bash
./update.sh v1.2.0
```

En cas d'échec après une mise à jour, le script affiche la commande exacte à exécuter pour restaurer la sauvegarde précédente.

> Sur Linux/macOS, si `./update.sh` affiche une erreur de permission, exécutez d'abord `chmod +x update.sh`.

---

## 8. Sauvegardes et restauration

### Sauvegarde manuelle

En plus de la sauvegarde automatique effectuée par `update.sh`, vous pouvez sauvegarder la base de données à tout moment :

```bash
docker compose exec -T db pg_dump -U soukouli soukouli_db | gzip > backups/sauvegarde-manuelle-$(date +%Y%m%d).sql.gz
```

### Restauration

```bash
gunzip -c backups/<nom-du-fichier>.sql.gz | docker compose exec -T db psql -U soukouli soukouli_db
```

### Sauvegarde des fichiers (logos, reçus, cartes, bulletins générés)

Ces fichiers sont conservés dans un volume Docker nommé `soukouli_app_storage`, indépendant de la base de données. Il n'est pas nécessaire de le sauvegarder aussi souvent, mais il est recommandé de le faire périodiquement :

```bash
docker run --rm -v soukouli_app_storage:/data -v "$(pwd)/backups":/backup alpine \
  tar czf /backup/storage-$(date +%Y%m%d).tar.gz -C /data .
```

> **Conseil** : copiez régulièrement le dossier `./backups` vers un support externe (clé USB, disque externe, service cloud) — une sauvegarde qui reste uniquement sur le même serveur ne protège pas contre une panne matérielle ou un vol.

---

## 9. Arrêter / redémarrer l'application

Arrêter (sans perdre de données) :

```bash
docker compose stop
```

Redémarrer :

```bash
docker compose start
```

Arrêter et supprimer les conteneurs (les données restent conservées dans les volumes) :

```bash
docker compose down
```

⚠️ N'utilisez **jamais** `docker compose down -v` en production : l'option `-v` supprime aussi les volumes, donc **toutes vos données** (base de données, fichiers, reçus, bulletins).

---

## 10. Dépannage

**L'application ne répond pas après le démarrage**

```bash
docker compose ps
docker compose logs app
```

Vérifiez que tous les services affichent un statut `Up` (ou `healthy`). Le service `app` a besoin d'une trentaine de secondes après son démarrage avant de répondre.

**Le port 80 est déjà utilisé par un autre programme**

Modifiez `APP_PORT` dans `.env` (par exemple `APP_PORT=8080`), puis relancez `docker compose up -d`. L'application sera alors accessible sur `http://localhost:8080`.

**Erreur de connexion à la base de données**

Vérifiez que `DB_PASSWORD` dans `.env` n'a pas été modifié après le premier démarrage (un changement de mot de passe après coup n'est pas répercuté automatiquement dans la base existante).

**Génération de bulletins/reçus PDF en échec**

Consultez les journaux applicatifs depuis l'interface (**Administration > Journaux**) ou :

```bash
docker compose logs app
```

**Réinitialiser complètement l'installation (perte de toutes les données)**

À utiliser uniquement si vous êtes certain de vouloir repartir de zéro, après avoir sauvegardé ce qui doit l'être :

```bash
docker compose down -v
docker compose up -d
```

---

## 11. Recommandations de sécurité

- Changez le mot de passe administrateur dès la première connexion.
- Choisissez un `DB_PASSWORD` fort et unique dans `.env`.
- Ne partagez jamais le fichier `.env` ni le contenu du dossier `./backups`.
- Cette installation sert l'application en HTTP simple, sans chiffrement. Si l'application doit être accessible depuis internet (et pas seulement depuis votre réseau local), placez un reverse proxy avec certificat HTTPS (par exemple [Caddy](https://caddyserver.com/) ou celui fourni par votre hébergeur) devant le service `webserver`.
- Limitez l'accès réseau au serveur à votre réseau local si l'application n'a pas besoin d'être accessible depuis l'extérieur.

---

## 12. Support

En cas de problème non couvert par ce guide, contactez votre prestataire ou l'éditeur de l'application en fournissant :

- le résultat de `docker compose ps`
- les journaux concernés : `docker compose logs app` (ou `db`, `webserver`, `scheduler`)
- la version installée (visible en bas de la barre latérale de l'application, ou via `docker compose images`)
