<?php

return [
    'releases' => [
        [
            'version' => '1.2.0-alpha',
            'date' => '2026-10-10',
            'groups' => [
                'Navigation' => [
                    'Bouton Retour uniforme sur toutes les pages : il ramene a la page d\'origine en conservant les filtres et la pagination.',
                    'Ajout du bouton Retour sur les pages qui n\'en avaient pas (nouvelle classe, inscription d\'un eleve, import, nouvelle matiere, detail d\'un cours, fiche comptable).',
                    'Apres la creation ou la modification d\'un eleve ou d\'une classe, retour automatique vers la liste.',
                    'Un utilisateur connecte n\'est plus ramene sur la page de connexion par le bouton Precedent du navigateur.',
                ],
                'Identite de l\'etablissement' => [
                    'Le tableau de bord affiche le nom complet de l\'etablissement.',
                    'Le logo de l\'etablissement remplace celui d\'OneUI dans l\'onglet du navigateur et dans le menu lateral.',
                ],
                'Corrections' => [
                    'Correction de l\'affichage des informations du pere, de la mere, du tuteur et de la sante d\'un eleve (donnees enregistrees en double encodage JSON).',
                    'Correction de la redirection apres l\'ajout d\'une absence, qui menait a la liste des retards.',
                    'Le message de confirmation d\'inscription d\'un eleve s\'affiche desormais comme un succes.',
                ],
            ],
        ],
        [
            'version' => '1.1.0',
            'date' => '2026-07-14',
            'groups' => [
                'Gestion des cycles d\'enseignement' => [
                    'Nouveau systeme de cycles (Maternelle, Primaire, College, Lycee) avec niveaux dynamiques par cycle.',
                    'Filtrage automatique des donnees selon le cycle gere par chaque utilisateur.',
                ],
                'Roles et permissions' => [
                    'Integration d\'un systeme complet de roles et permissions.',
                    'Roles dedies par cycle : directeur, comptable, secretaire, surveillant, en plus des roles admin et professeur.',
                    'Gestion des comptes utilisateurs et page de profil personnel.',
                ],
                'Examens officiels' => [
                    'Suivi des examens officiels togolais : CEPD, BEPC, BAC1, BAC2.',
                    'Creation de sessions d\'examen par annee et inscription des eleves.',
                    'Saisie et consultation des resultats d\'examen.',
                ],
                'Comptabilite et paiements' => [
                    'Configuration des types et tarifs de frais par cycle et par niveau.',
                    'Gestion des tranches de paiement (echeancier).',
                    'Tableau de bord comptabilite, encaissements et generation de recus PDF.',
                    'Suivi des eleves en retard de paiement.',
                ],
                'Bulletins et documents PDF' => [
                    'Refonte de la generation des bulletins.',
                    'Configuration de l\'ordre d\'affichage des matieres et de l\'en-tete des bulletins.',
                    'Generation de cartes etudiantes.',
                ],
                'Annee scolaire et passage de classe' => [
                    'Passage automatise des eleves a l\'annee scolaire suivante, classe par classe.',
                    'Generation et activation des nouvelles annees scolaires en etapes distinctes et explicites.',
                ],
                'Administration et supervision' => [
                    'Visualiseur des journaux applicatifs.',
                    'Administration des taches planifiees (passage automatique, nouvelle annee).',
                    'Page de parametres generaux de l\'etablissement.',
                ],
                'Corrections' => [
                    'Correction du format du matricule eleve.',
                    'Correction du formatage des bulletins.',
                ],
            ],
        ],
    ],
];
