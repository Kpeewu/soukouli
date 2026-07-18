# Manuel d'utilisation — Soukouli

> **Soukouli** — Logiciel de gestion scolaire pour les établissements du système éducatif togolais.
> Version de l'application : **1.1.0**. Ce manuel s'adresse au **personnel de l'établissement**
> (direction, secrétariat, comptabilité, surveillance, enseignants). Pour l'installation et
> l'exploitation technique du serveur, voir plutôt le fichier [`INSTALLATION.md`](INSTALLATION.md).

---

## Table des matières

1. [Présentation générale](#1-présentation-générale)
2. [Connexion et profil](#2-connexion-et-profil)
3. [Types d'utilisateurs (rôles et permissions)](#3-types-dutilisateurs-rôles-et-permissions)
4. [Administration système (Admin)](#4-administration-système-admin)
5. [Scolarité (élèves, classes, enseignants, matières)](#5-scolarité-élèves-classes-enseignants-matières)
6. [Évaluations et notes](#6-évaluations-et-notes)
7. [Assiduité (absences, retards, comportement)](#7-assiduité-absences-retards-comportement)
8. [Examens officiels](#8-examens-officiels)
9. [Gestion financière (comptabilité)](#9-gestion-financière-comptabilité)
10. [Documents PDF (bulletins, cartes, listes)](#10-documents-pdf-bulletins-cartes-listes)
11. [Année scolaire et passage de classe](#11-année-scolaire-et-passage-de-classe)
12. [Annexes](#12-annexes)

---

## 1. Présentation générale

### À quoi sert Soukouli ?

Soukouli permet à un établissement scolaire de gérer, en un seul endroit :

- les **élèves** et leur inscription dans les classes ;
- les **enseignants**, les **matières** et les **cours** ;
- les **évaluations** (devoirs, compositions, interrogations) et les **notes** ;
- l'**assiduité** (absences, retards, comportement) ;
- les **examens officiels** togolais (inscriptions et résultats) ;
- la **comptabilité** : frais de scolarité, encaissements, reçus et rapports ;
- la génération des **documents** : bulletins, cartes étudiantes, listes et fiches.

### Le contexte togolais

L'application est préchargée avec les données de référence du système éducatif togolais :

| Cycle | Niveaux |
|-------|---------|
| **Maternelle** | Maternelle 1, Maternelle 2 |
| **Primaire** | CP1, CP2, CE1, CE2, CM1, CM2 |
| **Collège** | 6ᵉ, 5ᵉ, 4ᵉ, 3ᵉ |
| **Lycée** | 2ⁿᵈᵉ, 1ʳᵉ, Terminale |

Les **examens officiels** de référence sont le **CEPD** (CM2), le **BEPC** (3ᵉ) et le **BAC**
(1ʳᵉ et Terminale). Tous les montants sont exprimés en **FCFA**.

### Vocabulaire utile (glossaire)

| Terme | Signification |
|-------|---------------|
| **Cycle** | Grand niveau d'enseignement : Maternelle, Primaire, Collège, Lycée. |
| **Promotion** | Un niveau d'une année scolaire donnée (ex. « 6ᵉ 2025-2026 »). |
| **Classe (groupe)** | Une division d'une promotion (ex. « 6ᵉ A », « 6ᵉ B »). |
| **Trimestre** | Période de notation dans l'année scolaire. |
| **Année scolaire** | L'exercice en cours ; l'application n'affiche que l'année **active**. |
| **Type de frais** | Nature d'un frais (Scolarité, Inscription, Examen, Cantine…). |
| **Tarif (configuration de frais)** | Montant d'un type de frais pour un cycle/niveau donné. |
| **Tranche** | Échéance de paiement d'un tarif (avec une date limite). |
| **Reçu** | Justificatif de paiement (numéroté `RECU-AAAA-NNNNN`, imprimable en PDF). |

### L'interface

- **Barre latérale (menu de gauche)** : le contenu **dépend de votre rôle**. Vous ne voyez que les
  modules auxquels vous avez droit.
- **Barre supérieure** : nom de l'établissement, votre nom, le **sélecteur d'année scolaire** et le
  bouton de **déconnexion**.
- **Sélecteur d'année scolaire** : permet de basculer l'affichage sur une autre année. Toutes les
  listes (élèves, classes, paiements…) sont filtrées sur l'année choisie.

> ℹ️ **Cloisonnement par cycle.** Beaucoup de rôles existent en deux variantes : une variante
> **« général »** qui voit **tous les cycles**, et des variantes **par cycle** (Maternelle, Primaire,
> Collège, Lycée) qui ne voient **que leur cycle**. Ce cloisonnement est appliqué automatiquement
> partout dans l'application.

> ℹ️ **À propos des liens.** Les adresses (URL) données dans ce manuel indiquent la page atteinte.
> En pratique, on y accède **en cliquant dans le menu** : les identifiants dans les URL sont
> encodés, il n'est donc pas utile de les taper à la main. Suivez le **chemin de menu** indiqué.

---

## 2. Connexion et profil

### Se connecter

1. Ouvrez l'adresse de l'application dans votre navigateur (la page d'accueil `/` est l'écran de
   connexion).
2. Saisissez votre **nom d'utilisateur** et votre **mot de passe**, puis cliquez sur **Connexion**.

> ⚠️ La connexion se fait avec un **nom d'utilisateur**, **pas** une adresse e-mail.

| Action | Chemin / Lien |
|--------|---------------|
| Page de connexion | `/` ou `/login` |
| Se déconnecter | Menu utilisateur (en haut à droite) → **Déconnexion** |

Après connexion, vous arrivez sur le **Tableau de bord** (`/dashboard`), qui affiche des statistiques
adaptées à votre rôle (effectifs, finances, vos cours…).

### Modifier son profil et son mot de passe

Tout utilisateur peut mettre à jour ses informations et **changer son mot de passe**.

| Action | Chemin / Lien |
|--------|---------------|
| Mon profil | Menu utilisateur (en haut à droite) → **Mon profil** → `/mon-profil` |

> 💡 **Premières connexions.** Les comptes créés par l'administrateur reçoivent un mot de passe
> défini à la création. Les comptes **enseignants** créés automatiquement ont le mot de passe par
> défaut **`professeur`**. Dans tous les cas, changez votre mot de passe dès la première connexion
> via **Mon profil**.

---

## 3. Types d'utilisateurs (rôles et permissions)

L'application distingue **6 familles de rôles**. La plupart existent en variante **« général »**
(tous les cycles) et en variantes **par cycle**.

| Rôle | Variantes | À quoi il sert |
|------|-----------|----------------|
| **Administrateur** | `admin` | **Administration du système uniquement** : comptes utilisateurs, cycles, années scolaires, examens officiels, journaux et tâches planifiées. **N'a pas accès** à la gestion scolaire courante ni à la comptabilité. |
| **Directeur** | Directeur général (tous cycles) + Directeur par cycle | Pilotage pédagogique : élèves, enseignants, classes, matières, évaluations/notes, promotions, passage de classe. **Consultation** de la comptabilité. |
| **Secrétaire** | Secrétaire général + Secrétaire par cycle | Inscription des élèves, gestion des classes, création des devoirs/compositions, inscriptions aux examens, **encaissement** des paiements, configuration des bulletins. |
| **Comptable** | Comptable général + Comptable par cycle | Toute la **comptabilité** : encaissement, reçus (y compris **annulation**), rapports, et paramétrage des frais (comptable général). |
| **Surveillant** | Surveillant général + Surveillant par cycle | **Assiduité uniquement** : absences, retards et comportement des élèves. |
| **Professeur** | `professeur` | **Saisie des notes** et des interrogations pour **ses cours** uniquement. |

Le libellé affiché s'accorde au genre (Directeur/Directrice, Surveillant/Surveillante…) selon la
civilité renseignée.

### Ce que chaque rôle peut faire (vue d'ensemble)

| Module | Admin | Directeur | Secrétaire | Comptable | Surveillant | Professeur |
|--------|:---:|:---:|:---:|:---:|:---:|:---:|
| Comptes utilisateurs | ✅ | — | — | — | — | — |
| Cycles / Années scolaires | ✅¹ | ✅¹ | — | — | — | — |
| Paramètres établissement | — | ✅² | ✅² | — | — | — |
| Élèves (inscription, fiche) | — | ✅ | ✅ | 👁️ | 👁️ | 👁️ |
| Classes / Promotions | — | ✅ | 👁️/✅³ | — | 👁️ | 👁️ |
| Enseignants | — | 👁️/✅⁴ | ✅ | — | — | — |
| Matières et cours | — | ✅ | ✅ | — | — | 👁️ |
| Devoirs / Compositions | — | 👁️ | ✅ | — | — | 👁️ (notes) |
| Interrogations | — | 👁️ | ✅ | — | — | ✅ |
| Saisie des notes | — | ✅ | ✅ | — | — | ✅ |
| Assiduité | — | ✅ | — | — | ✅ | — |
| Examens officiels | ✅⁵ | 👁️/✅⁵ | ✅⁶ | — | — | — |
| Comptabilité (consultation) | — | 👁️ | ✅ | ✅ | — | — |
| Encaisser un paiement | — | — | ✅ | ✅ | — | — |
| Annuler un reçu | — | — | — | ✅ | — | — |
| Configurer les frais | — | ✅⁷ | — | ✅⁷ | — | — |
| Bulletins / Documents PDF | — | ✅ | ✅ | — | — | 👁️ |
| Passage de classe | — | ✅ | — | — | — | — |

Légende : ✅ = action complète · 👁️ = consultation seule · — = pas d'accès.
¹ Admin **et** directeur général. ² Directeur général **et** secrétaire général. ³ Le secrétaire
consulte les classes ; la création de groupes relève des directeurs. ⁴ Le directeur général
**consulte** la liste des enseignants ; le **recrutement** revient au directeur de cycle ou au
secrétaire. ⁵ La **définition** des examens officiels est réservée au **directeur général**.
⁶ Le secrétaire gère les **inscriptions** aux sessions. ⁷ Paramétrage des frais réservé au
**directeur général** et au **comptable général**.

---

## 4. Administration système (Admin)

Le compte **administrateur** sert exclusivement à l'**administration du système**. Il ne peut pas
inscrire d'élèves, saisir de notes ni encaisser de paiements — ces tâches reviennent aux comptes
métier (directeur, secrétaire, comptable, surveillant, professeur).

> 💡 **Compte administrateur initial.** Au tout premier démarrage, un compte `admin` est créé
> automatiquement avec un mot de passe affiché dans la console du serveur (ou défini par la variable
> `INITIAL_ADMIN_PASSWORD`). Connectez-vous avec, changez le mot de passe, puis créez les comptes du
> personnel. Voir [`INSTALLATION.md`](INSTALLATION.md).

### 4.1 Créer et gérer les comptes utilisateurs

C'est **la première tâche** de l'administrateur : donner un accès à chaque membre du personnel.

**Chemin de menu :** *Gestion Système › Utilisateurs*

| Action | Chemin / Lien |
|--------|---------------|
| Voir la liste des utilisateurs | *Gestion Système › Utilisateurs* → `/users` |
| Ajouter un utilisateur | Bouton **Ajouter un utilisateur** → `/users/create` |
| Modifier un utilisateur | Icône **Modifier** sur la ligne → `/users/{id}/edit` |
| Supprimer un utilisateur | Icône **Supprimer** sur la ligne |

**Créer un compte, étape par étape :**

1. Menu *Gestion Système › Utilisateurs*, puis **Ajouter un utilisateur**.
2. Renseignez la partie **Compte** :
   - **Nom d'utilisateur** *(obligatoire, unique)* — servira à se connecter.
   - **Rôle** *(obligatoire)* — choisissez dans la liste déroulante, organisée par catégories :
     *Administration*, *Direction de cycle* (Directeur général ou par cycle), *Comptabilité*
     (Comptable général ou par cycle), *Secrétariat* (Secrétaire général ou par cycle).
     Choisissez la variante **« général »** pour un accès à tous les cycles, ou la variante **du
     cycle** concerné pour un accès restreint.
3. Renseignez l'**Identité** *(facultatif)* : civilité (M./Mme), nom, prénom, téléphone.
4. Définissez le **Mot de passe** (8 caractères minimum) et sa confirmation. Le bouton
   **Générer un mot de passe** propose un mot de passe aléatoire ; communiquez-le à l'intéressé qui
   le changera à la première connexion.
5. Cliquez sur **Créer l'utilisateur**.

**Modifier / supprimer :**

- **Modifier** permet de changer l'identité, le **rôle** (l'accès change immédiatement) et,
  facultativement, le mot de passe (laissez vide pour ne pas le changer).
- **Supprimer** retire définitivement le compte. *Le compte administrateur principal ne peut pas
  être supprimé.*

> ⚠️ **Les comptes enseignants ne se créent pas ici.** Le rôle « Professeur » est volontairement
> absent de ce formulaire. Un compte enseignant est **créé automatiquement** lorsqu'un directeur de
> cycle ou un secrétaire **recrute un enseignant** (voir [§5.3](#53-enseignants-équipe-enseignante)).
> L'administrateur peut, dans **Modifier**, rattacher un compte à une fiche enseignant existante.

> ℹ️ Il n'existe pas d'option « désactiver » un compte : pour retirer un accès, modifiez le rôle ou
> supprimez le compte.

### 4.2 Cycles

Gestion des cycles d'enseignement et de leurs niveaux. *Accessible à l'administrateur et au
directeur général.* Créer un nouveau cycle génère automatiquement les rôles associés (directeur,
secrétaire, comptable, surveillant de ce cycle).

| Action | Chemin / Lien |
|--------|---------------|
| Gérer les cycles | *Gestion Système › Cycles* → `/cycles` |

### 4.3 Années scolaires

Génération et activation de l'année scolaire suivante. *Administrateur et directeur général.*

| Action | Chemin / Lien |
|--------|---------------|
| Gérer les années scolaires | *Gestion Système › Générer année suivante* → `/annees-scolaires` |
| Générer l'année suivante | Bouton **Générer** dans l'écran ci-dessus |
| Activer une année | Bouton **Activer** sur l'année voulue |
| Changer l'année affichée | **Sélecteur d'année** dans la barre supérieure |

Voir aussi [§11 — Année scolaire et passage de classe](#11-année-scolaire-et-passage-de-classe).

### 4.4 Paramètres de l'établissement

Nom de l'établissement, logo, image de fond de connexion et autres réglages d'affichage.
*Réservé au directeur général et au secrétaire général.*

| Action | Chemin / Lien |
|--------|---------------|
| Paramètres | *Gestion Système › Paramètres* → `/settings` |

### 4.5 Journaux et tâches planifiées (Admin)

| Action | Chemin / Lien |
|--------|---------------|
| Journaux et erreurs applicatives | *Gestion Système › Logs & Erreurs* → `/logs` |
| Tâches planifiées (automatisations) | *Gestion Système › Tâches planifiées* → `/crons` |

Les tâches planifiées gèrent notamment le **basculement automatique d'année scolaire** et le
**passage des élèves** (voir [§11](#11-année-scolaire-et-passage-de-classe)).

---

## 5. Scolarité (élèves, classes, enseignants, matières)

Ces modules sont utilisés au quotidien par les **directeurs**, **secrétaires** et **professeurs**
(en consultation). L'administrateur n'y a pas accès.

### 5.1 Élèves

**Chemins de menu :** *Inscription › Admission › Inscrire un élève* (secrétaire) ; la liste des
élèves d'une classe s'ouvre depuis *Nos classes*.

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Inscrire un élève | Directeur, Secrétaire | *Inscription › Inscrire un élève* → `/eleve/ajouter-un-eleve` |
| Voir la liste des élèves d'une classe | Directeur, Secrétaire, Surveillant, Professeur | *Nos classes* → choisir la classe → **Liste des élèves** → `/classe/liste-des-eleves/{classe}` |
| Voir la fiche d'un élève | Directeur, Secrétaire | Cliquer sur l'élève → `/eleve/details/{eleve}` |
| Modifier un élève | Directeur, Secrétaire | Bouton **Modifier** sur la fiche |
| Supprimer un élève | Directeur, Secrétaire | Bouton **Supprimer** sur la fiche |
| Exporter les élèves (Excel) | Directeur, Secrétaire | Depuis la liste de la classe → **Exporter** |
| Importer des élèves (Excel) | Directeur, Secrétaire | Depuis la liste de la classe → **Importer** (un **modèle** téléchargeable est fourni) |

> 💡 L'**import Excel** permet d'inscrire une classe entière d'un coup : téléchargez d'abord le
> modèle, remplissez-le, puis importez-le.

### 5.2 Classes et promotions

Une **promotion** est un niveau d'une année (ex. « 6ᵉ ») ; une **classe** est un groupe de cette
promotion (ex. « 6ᵉ A »).

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Voir les classes | Directeur, Secrétaire | *Nos classes* → naviguer par cycle/promotion |
| Ajouter un groupe (classe) | Directeur de cycle | *Nos classes* → promotion → **Ajouter un groupe** → `/classe/ajouter-une-classe/{promotion}` |
| Gérer les promotions (périodes) | Directeurs | *Gestion Système › Promotions* → `/promotions` |

### 5.3 Enseignants (équipe enseignante)

**Chemin de menu :** *Équipe › Équipe enseignante › Gérer les enseignants*

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Voir les enseignants | Directeur, Secrétaire | *Équipe › Gérer les enseignants* → `/professeurs/liste-des-enseignants` |
| Recruter un enseignant | Directeur **de cycle**, Secrétaire | Bouton **Ajouter** → `/professeurs/ajouter-un-enseignant` |
| Modifier un enseignant | Directeur de cycle, Secrétaire | Bouton **Modifier** |
| Supprimer un enseignant | Directeur de cycle, Secrétaire | Bouton **Supprimer** |

> ⚠️ **Le directeur général** ne peut que **consulter** la liste des enseignants. Le **recrutement**
> est réservé aux **directeurs de cycle** et aux **secrétaires**.

> 🔑 **Création automatique du compte enseignant.** Lors du recrutement, l'application crée
> automatiquement le **compte de connexion** de l'enseignant :
> - **Nom d'utilisateur** = première lettre du prénom + nom (ex. *Jean Kossi* → `jkossi`) ;
> - **Mot de passe par défaut** = `professeur` (à changer à la première connexion via **Mon profil**).
>
> Le nom d'utilisateur généré est affiché dans le message de confirmation. Communiquez ces
> identifiants à l'enseignant.

### 5.4 Matières et cours

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Liste des matières | Directeur, Secrétaire | *Matières et Cours › Matières* → `/matiere/liste-des-matieres` |
| Ajouter une matière | Directeur, Secrétaire | Bouton **Ajouter** → `/matiere/ajouter-une-matiere` |
| Voir les cours d'une classe | Directeur, Secrétaire, Professeur | *Matières et Cours › Cours* → classe → `/cours/classe/{classe}` |
| Affecter un enseignant à un cours | Directeur, Secrétaire | Depuis la liste des cours d'une classe |

---

## 6. Évaluations et notes

L'application distingue deux natures d'évaluations :

- **Devoirs / Compositions** : **créés par le secrétaire** du cycle. Le **professeur** ne fait que
  **saisir les notes**.
- **Interrogations** : créées et gérées par le **professeur** du cours (ou le secrétaire du cycle).

> Les **directeurs**, le **directeur général** et le **secrétaire général** ont un droit de
> **consultation** sur les évaluations.

### Côté secrétaire / directeur

**Chemin de menu :** *Évaluations*

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Créer un devoir / une composition | Secrétaire | *Évaluations › Devoirs / Compositions › Nouveau devoir/composition* → choisir la promotion et la matière → `/evaluation/ajout-evaluation/{promotion}/{matiere}/{trimestre}` |
| Voir les devoirs / compositions | Directeur, Secrétaire, Professeur | *Évaluations › Voir les devoirs/compositions* → `/evaluation/liste-des-evaluations/{promotion}/{matiere}/{trimestre}` |
| Créer une interrogation | Professeur, Secrétaire | *Évaluations › Interrogations › Nouvelle interrogation* → classe → cours → `/evaluation/ajout-interrogation/{classe}/cours/{cours}/trimestre/{trimestre}` |
| Voir les interrogations | Professeur, Directeur, Secrétaire | *Évaluations › Voir les interrogations* → `/evaluation/liste-des-interrogations/classe/{classe}/cours/{cours}/...` |

### Côté professeur (saisie des notes)

Le professeur dispose d'un menu simplifié : **Mes Cours** et **Saisie des Notes**.

| Action | Chemin / Lien |
|--------|---------------|
| Voir mes classes | *Mes Cours* → classe → `/classe/liste-des-eleves/{classe}` |
| Nouvelle interrogation | *Saisie des Notes › Interrogations › Nouvelle interrogation* → `/evaluation/ajout-interrogation/cours/classe/{classe}` |
| Saisir / mettre à jour les notes | Ouvrir l'évaluation → saisir les notes → **Enregistrer** (`/evaluation/mise-a-jour/{evaluation}/trimestre/{trimestre}`) |

> 💡 Un professeur ne voit et ne note **que les cours qu'il enseigne**. Il ne peut pas supprimer un
> devoir ou une composition (seul le secrétaire du cycle le peut).

Les **moyennes** et les **bulletins** sont calculés automatiquement à partir des notes saisies
(voir [§10](#10-documents-pdf-bulletins-cartes-listes)).

---

## 7. Assiduité (absences, retards, comportement)

Ce module est le domaine du **surveillant** (les directeurs y ont aussi accès). Il couvre les
**avertissements/comportement**, les **absences** et les **retards** par élève et par trimestre.

Le surveillant dispose d'un menu **Nos classes** pour atteindre les élèves.

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Ouvrir la liste des élèves d'une classe | Surveillant, Directeur | *Nos classes* → classe → `/classe/liste-des-eleves/{classe}` |
| Voir les avertissements d'un élève | Surveillant, Directeur | Depuis l'élève → `/assiduite/liste-des-avertissements/{eleve}/classe/{classe}` |
| Ajouter un avertissement | Surveillant, Directeur | Bouton **Ajouter** → `/assiduite/ajouter-avertissement/eleve/{eleve}/trimestre/{trimestre}` |
| Saisir le comportement | Surveillant, Directeur | Depuis l'avertissement → `/assiduite/comportement-de-l-eleve/{assiduite}/classe/{classe}` |
| Gérer les **retards** | Surveillant, Directeur | Depuis l'avertissement → **Retards** → `/retard/liste-des-retards/{assiduite}/classe/{classe}` |
| Gérer les **absences** | Surveillant, Directeur | Depuis l'avertissement → **Absences** → `/absence/liste-des-absence/{assiduite}/classe/{classe}` |

---

## 8. Examens officiels

Gestion des examens officiels togolais (CEPD, BEPC, BAC) : leur **définition**, les **sessions**
annuelles, les **inscriptions** des élèves et la **saisie des résultats**.

**Chemin de menu :** *Examens*

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Définir les examens officiels | **Directeur général** | *Examens › Examens Officiels* → `/examens-officiels` |
| Voir / créer les sessions d'examen | Directeur, Secrétaire | *Examens › Sessions d'examen* → `/examens/sessions` |
| Voir les inscriptions d'une session | Directeur, Secrétaire | Ouvrir la session → **Inscriptions** → `/examens/sessions/{session}/inscriptions` |
| Inscrire des élèves à une session | Secrétaire, Directeur | **Ajouter une inscription** → `/examens/sessions/{session}/inscriptions/create` |
| Saisir les résultats | Directeur, Secrétaire | Ouvrir la session → **Résultats** → `/examens/resultats/{session}/saisie` |
| Consulter la liste des résultats | Directeur, Secrétaire | `/examens/resultats/{session}/liste` |

> ⚠️ Seul le **directeur général** peut **définir** les examens officiels. Les sessions, inscriptions
> et résultats sont gérés par les directeurs et secrétaires (l'administrateur n'y a pas accès).

---

## 9. Gestion financière (comptabilité)

La comptabilité de Soukouli couvre les **frais de scolarité** : on définit des **types de frais** et
leurs **tarifs**, puis on **encaisse** les paiements des élèves en émettant un **reçu**. L'application
calcule automatiquement les **soldes**, les **statuts** (soldé / partiel / impayé) et les **retards**.

> ℹ️ Il n'y a **ni compte bancaire, ni prêt, ni épargne** : « argent » = frais scolaires, paiements
> et reçus.

**Qui fait quoi ?**

| | Comptable | Secrétaire | Directeur | Admin |
|---|:---:|:---:|:---:|:---:|
| Consulter tableau de bord, soldes, rapports | ✅ | ✅ | 👁️ | — |
| Encaisser un paiement (émettre un reçu) | ✅ | ✅ | — | — |
| Imprimer un reçu (PDF) | ✅ | ✅ | 👁️ | — |
| **Annuler un reçu** | ✅ | — | — | — |
| Configurer les frais (types, tarifs) | ✅ (général) | — | ✅ (général) | — |

**Chemin de menu :** *Comptabilité*

### 9.1 Paramétrer les frais (préalable)

*Réservé au **directeur général** et au **comptable général**.* À faire une fois par année, avant les
encaissements.

| Action | Chemin / Lien |
|--------|---------------|
| Types de frais (Scolarité, Inscription, Examen…) | *Comptabilité › Configuration frais › Types de frais* → `/comptabilite/types-frais` |
| Tarifs par niveau (montants par cycle/niveau) | *Comptabilité › Configuration frais › Tarifs par niveau* → `/comptabilite/configurations-frais` |
| Ajouter des **tranches** (échéances) à un tarif | Depuis un tarif → **Ajouter une tranche** |

**Ordre logique :** créer d'abord le **type de frais** → puis le **tarif** (montant pour un
cycle/niveau et l'année en cours) → éventuellement découper le tarif en **tranches** (la somme des
tranches doit être égale au montant total, avec des dates limites cohérentes).

### 9.2 Encaisser un paiement

*Comptable ou secrétaire.*

1. Menu *Comptabilité › Paiements élèves* (`/comptabilite/eleves`) : liste des élèves avec leur
   **solde**.
2. Cliquez sur un élève pour ouvrir sa **fiche** (`/comptabilite/eleves/{eleve}`) : elle affiche les
   frais dus, ce qui est déjà payé et ce qui reste.
3. Cliquez sur **Payer** (`/comptabilite/eleves/{eleve}/payer`).
4. Choisissez le **frais** (et la **tranche** le cas échéant), saisissez le **montant**, le **mode de
   paiement** (espèces, chèque, mobile money, virement) et une **référence** (obligatoire sauf pour
   les espèces).
5. Validez : le **reçu** est généré automatiquement (numéro `RECU-AAAA-NNNNN`) et son numéro est
   affiché.

> ⚠️ Le montant saisi ne peut pas dépasser le **solde restant** du frais ou de la tranche.

### 9.3 Reçus

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Liste des reçus | Comptable, Secrétaire, Directeur | *Comptabilité › Reçus* → `/comptabilite/recus` |
| Détail d'un reçu | Comptable, Secrétaire, Directeur | Cliquer sur le reçu → `/comptabilite/recus/{recu}` |
| Télécharger / imprimer le reçu (PDF) | Comptable, Secrétaire, Directeur | Bouton **PDF** → `/comptabilite/recus/{recu}/pdf` |
| **Annuler** un reçu | **Comptable uniquement** | Bouton **Annuler** → `/comptabilite/recus/{recu}/annuler` |

> ⚠️ L'**annulation d'un reçu** est réservée au **comptable**. Un reçu annulé (et son paiement) est
> exclu des soldes et des rapports : les montants se recalculent automatiquement. Les **secrétaires**
> peuvent encaisser mais **pas annuler**.

### 9.4 Tableau de bord et rapports

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Tableau de bord comptable (KPI, encaissements du jour/semaine, derniers paiements) | Comptable, Secrétaire, Directeur | *Comptabilité › Tableau de bord* → `/comptabilite` |
| Rapport financier (attendu vs encaissé, taux de recouvrement, par type de frais et par cycle) | Comptable, Secrétaire, Directeur | *Comptabilité › Rapports* → `/comptabilite/rapports` |
| Élèves **en retard** de paiement (échéances dépassées) | Comptable, Secrétaire, Directeur | Depuis les rapports → **Impayés / retards** → `/comptabilite/rapports/en-retard` |

> ℹ️ Les **directeurs** ont un accès en **consultation** à la comptabilité (leur cycle pour un
> directeur de cycle, tous les cycles pour le directeur général), mais ne peuvent ni encaisser ni
> annuler. Tous les écrans se filtrent par cycle selon le rôle.

---

## 10. Documents PDF (bulletins, cartes, listes)

L'application génère plusieurs documents imprimables. *Accessible aux directeurs, secrétaires et
professeurs (consultation) — pas à l'administrateur.* On y accède depuis la **classe** ou l'**élève**.

| Document | Chemin / Lien |
|----------|---------------|
| Liste des élèves d'une classe | `/liste-eleves/classe/{classe}` |
| Fiche d'informations d'un élève | Fiche élève → **Fiche d'informations** → `/fiche-informations-eleve/{eleve}` |
| Fiches d'informations de toute une classe | `/fiche-informations-eleve/classe/{classe}` |
| Cartes étudiantes d'une classe | `/cartes-etudiantes/classe/{classe}` |
| Bulletin d'un élève (par trimestre) | `/bulletin/eleve/{eleve}/classe/{classe}/{trimestre}` |
| Bulletins de toute une classe (par trimestre) | `/bulletins-du-trimestre/classe/{classe}/trimestre/{trimestre}` |

### Configuration des bulletins (secrétaires)

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Ordre d'affichage des matières sur le bulletin | Secrétaires | *Configuration bulletins › Ordre des matières* → `/bulletin-config` |
| Disposition de l'en-tête du bulletin | **Secrétaire général** | *Configuration bulletins › Disposition en-tête* → `/bulletin-config/header` |

---

## 11. Année scolaire et passage de classe

En fin d'année, on **génère l'année suivante** puis on fait **passer les élèves** dans la classe
supérieure.

| Action | Qui | Chemin / Lien |
|--------|-----|---------------|
| Générer / activer l'année suivante | Admin, Directeur général | *Gestion Système › Générer année suivante* → `/annees-scolaires` |
| Passage en année supérieure (classe par classe) | Directeurs | *Passage année supérieure* → `/passage` |
| Voir le plan de passage | Directeurs | `/passage/plan` |
| Basculer l'affichage sur une autre année | Tous | **Sélecteur d'année** (barre supérieure) |

> 💡 Le basculement d'année et le passage des élèves peuvent aussi être **automatisés** via les
> **Tâches planifiées** (menu *Gestion Système › Tâches planifiées*, réservé à l'administrateur).

---

## 12. Annexes

### 12.1 Aide-mémoire des liens par rôle

**Administrateur**

| Action | Lien |
|--------|------|
| Comptes utilisateurs | `/users` |
| Cycles | `/cycles` |
| Années scolaires | `/annees-scolaires` |
| Journaux | `/logs` |
| Tâches planifiées | `/crons` |

**Directeur (général / de cycle)**

| Action | Lien |
|--------|------|
| Tableau de bord | `/dashboard` |
| Élèves d'une classe | `/classe/liste-des-eleves/{classe}` |
| Enseignants (consultation) | `/professeurs/liste-des-enseignants` |
| Promotions | `/promotions` |
| Comptabilité (consultation) | `/comptabilite` |
| Examens officiels (directeur général) | `/examens-officiels` |
| Passage de classe | `/passage` |
| Bulletins d'une classe | `/bulletins-du-trimestre/classe/{classe}/trimestre/{trimestre}` |

**Secrétaire**

| Action | Lien |
|--------|------|
| Inscrire un élève | `/eleve/ajouter-un-eleve` |
| Recruter un enseignant | `/professeurs/ajouter-un-enseignant` |
| Devoirs / compositions | `/evaluation/ajout-evaluation/{promotion}/{matiere}/{trimestre}` |
| Inscriptions aux examens | `/examens/sessions/{session}/inscriptions` |
| Encaisser un paiement | `/comptabilite/eleves/{eleve}/payer` |
| Configuration des bulletins | `/bulletin-config` |

**Comptable**

| Action | Lien |
|--------|------|
| Tableau de bord comptable | `/comptabilite` |
| Paiements élèves | `/comptabilite/eleves` |
| Encaisser un paiement | `/comptabilite/eleves/{eleve}/payer` |
| Reçus | `/comptabilite/recus` |
| Rapports | `/comptabilite/rapports` |
| Types de frais (comptable général) | `/comptabilite/types-frais` |
| Tarifs par niveau (comptable général) | `/comptabilite/configurations-frais` |

**Surveillant**

| Action | Lien |
|--------|------|
| Élèves d'une classe | `/classe/liste-des-eleves/{classe}` |
| Avertissements d'un élève | `/assiduite/liste-des-avertissements/{eleve}/classe/{classe}` |
| Retards | `/retard/liste-des-retards/{assiduite}/classe/{classe}` |
| Absences | `/absence/liste-des-absence/{assiduite}/classe/{classe}` |

**Professeur**

| Action | Lien |
|--------|------|
| Mes classes | `/classe/liste-des-eleves/{classe}` |
| Nouvelle interrogation | `/evaluation/ajout-interrogation/cours/classe/{classe}` |
| Saisir / mettre à jour les notes | `/evaluation/mise-a-jour/{evaluation}/trimestre/{trimestre}` |
| Mon profil | `/mon-profil` |

### 12.2 Foire aux questions (FAQ)

**Je ne vois pas un menu / une page.** L'affichage dépend de votre **rôle**. Si un module manque,
c'est que votre rôle n'y donne pas accès — voir la [matrice §3](#ce-que-chaque-rôle-peut-faire-vue-densemble).

**J'ai oublié mon mot de passe.** Contactez l'**administrateur**, qui peut le réinitialiser via
*Utilisateurs › Modifier*.

**Je ne vois qu'un seul cycle.** C'est normal pour un rôle **par cycle** : seul un rôle **« général »**
voit tous les cycles.

**Les données ont changé / une classe est vide.** Vérifiez l'**année scolaire** sélectionnée dans la
barre supérieure : l'affichage est filtré sur l'année active.

**Un paiement a été encaissé par erreur.** Un **comptable** peut **annuler le reçu** correspondant
(`/comptabilite/recus/{recu}/annuler`) ; les soldes se recalculent automatiquement.

**Un nouvel enseignant ne peut pas se connecter.** Son identifiant est *(1ʳᵉ lettre du prénom + nom)*
et son mot de passe par défaut est **`professeur`** ; il doit le changer via **Mon profil**.
