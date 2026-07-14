@php
    $indice_trimestre = intval(substr($trimestre->intitule, 10, 1));
    $suffixe = $indice_trimestre === 1 ? 'er' : 'ème';
@endphp
<div style="margin-top:8px; font-size:13px; font-weight:bold; text-transform:uppercase;">
    Bulletin de notes du {{ substr($trimestre->intitule, 10, 1) }}<sup>{{ $suffixe }}</sup> trimestre
</div>
