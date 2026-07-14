<?php

namespace App\Services;

use App\Models\Recu;
use App\Models\Setting;
use Ismaelw\LaraTeX\LaraTeX;

class RecuPdfService
{
    /**
     * Genere le PDF d'un recu
     */
    public function generer(Recu $recu): string
    {
        $recu->load([
            'paiement.eleve.classes.promotion',
            'paiement.configurationFrais.typeFrais',
            'paiement.tranche',
            'comptable'
        ]);

        return (new LaraTeX('latex.recu'))
            ->with([
                'recu' => $recu,
                'paiement' => $recu->paiement,
                'eleve' => $recu->paiement->eleve,
                'comptable' => $recu->comptable,
                'school' => $this->getSchoolSettings(),
            ])
            ->savePdf(storage_path('app/recus/' . $recu->numero . '.pdf'))
            ->getPath();
    }

    /**
     * Telecharge directement le PDF du recu
     */
    public function download(Recu $recu)
    {
        $recu->load([
            'paiement.eleve.classes.promotion',
            'paiement.configurationFrais.typeFrais',
            'paiement.tranche',
            'comptable'
        ]);

        return (new LaraTeX('latex.recu'))
            ->with([
                'recu' => $recu,
                'paiement' => $recu->paiement,
                'eleve' => $recu->paiement->eleve,
                'comptable' => $recu->comptable,
                'school' => $this->getSchoolSettings(),
            ])
            ->download($recu->numero . '.pdf');
    }

    /**
     * Affiche le PDF dans le navigateur
     */
    public function render(Recu $recu)
    {
        $recu->load([
            'paiement.eleve.classes.promotion',
            'paiement.configurationFrais.typeFrais',
            'paiement.tranche',
            'comptable'
        ]);

        return (new LaraTeX('latex.recu'))
            ->with([
                'recu' => $recu,
                'paiement' => $recu->paiement,
                'eleve' => $recu->paiement->eleve,
                'comptable' => $recu->comptable,
                'school' => $this->getSchoolSettings(),
            ])
            ->render();
    }

    /**
     * Recupere les parametres de l'etablissement pour les templates LaTeX
     */
    private function getSchoolSettings(): array
    {
        return [
            'name' => Setting::get('school_name', 'Mon Avenir'),
            'full_name' => Setting::get('school_full_name', 'Complexe Prive Laique Mon Avenir'),
            'type' => Setting::get('school_type', 'COMPLEXE SCOLAIRE'),
            'motto' => Setting::get('school_motto', 'Travail - Discipline - Succes'),
            'bp' => Setting::get('school_bp', 'BP: 68'),
            'city' => Setting::get('school_city', 'SOKODE'),
            'country' => Setting::get('school_country', 'TOGO'),
            'phone' => Setting::get('school_phone', ''),
            'email' => Setting::get('school_email', ''),
            'address' => Setting::getFullAddress(),
        ];
    }
}
