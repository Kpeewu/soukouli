@php
    $timestamp = strtotime($eleve->date_naissance);
    $date_naissance = date('d-m-Y', $timestamp);
@endphp
<div style="text-align:center; font-size:12px; white-space:nowrap;">
    Nom et prénoms: <strong style="font-size:14px;">{{ $eleve->nom }} {{ $eleve->prenom }}</strong>
    &nbsp;&nbsp; Sexe: <strong style="font-size:14px;">{{ $eleve->sexe }}</strong>
    &nbsp;&nbsp; Date naissance: <strong style="font-size:14px;">{{ $date_naissance }}</strong>
</div>
