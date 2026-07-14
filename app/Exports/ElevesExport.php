<?php

namespace App\Exports;

use App\Models\Classe;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ElevesExport implements WithHeadings, WithMapping, FromQuery
{
    public function __construct(protected Classe $classe)
    {
    }

    public function headings(): array
    {
        return [
            ['N°', 'Nom', 'Prénom', 'Date de naissance', 'Sexe', 'Adresse'],
        ];
    }

    public function query()
    {
        return $this->classe->eleves();
    }

    public function map($eleve): array
    {
        return [
            $eleve->id,
            $eleve->nom,
            $eleve->prenom,
            $eleve->date_naissance,
            $eleve->sexe,
            $eleve->adresse

        ];
    }
}
