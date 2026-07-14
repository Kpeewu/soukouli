{{-- Tableau de notes --}}
<table style="width:100%; border-collapse:collapse; font-size:12px; margin-top:0px;">
    <thead>
        <tr>
            <th rowspan="2" style="border:1px solid #000; padding:3px; width:22%;">MATIERES</th>
            <th colspan="2" style="border:1px solid #000; padding:3px;">Notes</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Moy.</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Coef</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Note Déf.</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Appréciation</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Professeur</th>
            <th rowspan="2" style="border:1px solid #000; padding:3px;">Signature</th>
        </tr>
        <tr>
            <th style="border:1px solid #000; padding:2px; font-size:9px;">Classe</th>
            <th style="border:1px solid #000; padding:2px; font-size:9px;">Compo</th>
        </tr>
    </thead>
    <tbody>
        @php
            $total = 0;
            $total_coefficient = 0;
        @endphp
        @foreach ($lignes as $ligne)
            @php
                $moyenne_2_notes = round(($ligne['notes_cours']['moyenne_classe'] + $ligne['notes_cours']['compo']) / 2, 2);
                $coefficient = $ligne['notes_cours']['cours']->coefficient;
                $note_def = round($moyenne_2_notes * $coefficient, 2);
                $professeur = $ligne['notes_cours']['cours']->professeur->nom ?? '';
                $appreciation = 'Néant';
                if ($moyenne_2_notes <= 5.0) {
                    $appreciation = 'Faible';
                } elseif (5.0 < $moyenne_2_notes && $moyenne_2_notes < 10.0) {
                    $appreciation = 'Insuffisant';
                } elseif (10.0 <= $moyenne_2_notes && $moyenne_2_notes < 12.0) {
                    $appreciation = 'Passable';
                } elseif (12.0 <= $moyenne_2_notes && $moyenne_2_notes < 14.0) {
                    $appreciation = 'A. Bien';
                } elseif (14.0 <= $moyenne_2_notes && $moyenne_2_notes < 16.0) {
                    $appreciation = 'Bien';
                } elseif (16.0 <= $moyenne_2_notes && $moyenne_2_notes < 18.0) {
                    $appreciation = 'T. Bien';
                } elseif (18.0 <= $moyenne_2_notes && $moyenne_2_notes <= 20.0) {
                    $appreciation = 'Excellent';
                }
                $total += $note_def;
                $total_coefficient += $coefficient;
            @endphp
            <tr>
                <td style="border:1px solid #000; padding:4px;">{{ $ligne['notes_cours']['cours']->matiere->intitule }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $ligne['notes_cours']['moyenne_classe'] }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $ligne['notes_cours']['compo'] }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $moyenne_2_notes }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $coefficient }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">{{ $note_def }}</td>
                <td style="border:1px solid #000; padding:4px;text-align:center;">{{ $appreciation }}</td>
                <td style="border:1px solid #000; padding:4px;text-align:center;">{{ $professeur }}</td>
                <td style="border:1px solid #000; padding:4px; text-align:center;">Validée</td>
            </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4" style="border:1px solid #000; padding:8px; text-align:center; font-weight:bold;">TOTAUX</td>
            <td style="border:1px solid #000; padding:3px; text-align:center; font-weight:bold;">{{ $total_coefficient }}</td>
            <td style="border:1px solid #000; padding:3px; text-align:center; font-weight:bold;">{{ $total }}</td>
            <td colspan="3" style="border:1px solid #000;"></td>
        </tr>
    </tfoot>
</table>

{{-- Bloc pied de page : moyennes, rang, assiduite, signatures --}}
@php
    $temp_trimestre = 1;
    if (substr($trimestre->intitule, 0, 11) === 'Trimestre 2') {
        $temp_trimestre = 2;
    } elseif (substr($trimestre->intitule, 0, 11) === 'Trimestre 3') {
        $temp_trimestre = 3;
    }
@endphp
<table style="width:100%; border-collapse:collapse; font-size:9px; margin-top:8px;">
    @if ($temp_trimestre === 1)
        <tr>
            <td style="border:1px solid #000; padding:4px; width:53%;">Moyenne en lettre: <strong><em style="font-size:12px; margin-left:4px;">{{ $moyenne_lettre }}</em></strong></td>
            <td style="border:1px solid #000; padding:4px;font-size:12px;">Moyenne du 1<sup>er</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Tableau d'honneur: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">&nbsp;</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Encouragements: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">&nbsp;</td>
        </tr>
    @endif

    @if ($temp_trimestre === 2)
        <tr>
            <td style="border:1px solid #000; padding:3px; width:53%;">Moyenne en lettre: <strong><em style="font-size:12px;">{{ $moyenne_lettre }}</em></strong></td>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Moyenne du 1<sup>er</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id - 1]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id - 1]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Tableau d'honneur: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">Moyenne du 2<sup>ème</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Encouragements: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">&nbsp;</td>
        </tr>
    @endif

    @if ($temp_trimestre === 3)
        <tr>
            <td style="border:1px solid #000; padding:3px; width:53%;">Moyenne en lettre: <strong><em style="font-size:12px;">{{ $moyenne_lettre }}</em></strong></td>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Moyenne du 1<sup>er</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id - 2]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id - 2]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;font-size:12px;">Tableau d'honneur: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">Moyenne du 2<sup>ème</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id - 1]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id - 1]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
        <tr>
            <td style="border:1px solid #000; padding:3px;">Encouragements: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 13 ? 'Oui' : 'Non' }}</td>
            <td style="border:1px solid #000; padding:3px;">Moyenne du 3<sup>ème</sup> trimestre: <strong style="font-size: 14px">{{ $moyennes_trimestres[$trimestre->id]['moyenne'] }}</strong>. Rang: <strong>{{ $moyennes_trimestres[$trimestre->id]['rang'] }}</strong> sur {{ count($classe->eleves) }}</td>
        </tr>
    @endif

    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Félicitations: {{ $moyennes_trimestres[$trimestre->id]['moyenne'] >= 16 ? 'Oui' : 'Non' }}</td>
        <td style="border:1px solid #000; padding:3px;font-size:14px;">
            @if ($temp_trimestre === 3)
                Moyenne Annuelle: <strong>{{ $moyenne_annuelle }}</strong>.
            @else
                &nbsp;
            @endif
        </td>
    </tr>

    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Retards: {{ count($assiduite->retards) }}</td>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Décisions du conseil des professeurs:</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Absences: {{ count($assiduite->absences) }}</td>
        <td rowspan="4" style="border:1px solid #000; padding:3px;">&nbsp;</td>
    </tr>
    @php
        $heures_absences = 0;
        foreach ($assiduite->absences as $absence) {
            $heures_absences += $absence->nombre_heure;
        }
        $comportement = json_decode($assiduite->comportement ?? '{}');
        $avertissement = $comportement->avertissement ?? (object) ['Travail' => false, 'Discipline' => false];
        $blame = $comportement->blame ?? (object) ['Travail' => false, 'Discipline' => false];
    @endphp
    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Absences évaluées en heures: {{ $heures_absences }}h</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Avertissement: {{ ($avertissement->Travail ?? false) ? 'Travail' : '' }} {{ ($avertissement->Discipline ?? false) ? 'Discipline' : '' }}</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:3px;font-size:12px;">Blâme pour: {{ ($blame->Travail ?? false) ? 'Travail' : '' }} {{ ($blame->Discipline ?? false) ? 'Discipline' : '' }}</td>
    </tr>
    <tr>
        <td style="border:1px solid #000; padding:3px; border-top:2px solid #000;font-size:12px;padding-bottom: 48px;">
            Nom et signature du titulaire: @if ($classe->professeur){{ $classe->professeur->nom }} {{ $classe->professeur->prenom }}@endif
        </td>
        <td style="border:1px solid #000; padding:3px; border-top:2px solid #000;font-size:12px;padding-bottom: 48px;">
            Sokodé, le {{ \Carbon\Carbon::now()->translatedFormat('d F Y') }}<br>
            @php
                $directeurTitre = $directeur ? $directeur->accordTitre('Le Directeur', 'La Directrice') : 'Le Directeur';
                $directeurNom = $directeur ? trim(($directeur->prenom ?? '') . ' ' . ($directeur->nom ?? '')) : '';
            @endphp
            <strong>{{ $directeurTitre }}</strong><br>
            {{ $directeurNom }}
        </td>
    </tr>
</table>
