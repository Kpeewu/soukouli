<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1cm 0.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #000; }
    </style>
</head>
<body>
    @php
        $positions = $layout ?? \App\Http\Controllers\BulletinHeaderConfigController::DEFAULT_LAYOUT;
        $blocData = ['eleve' => $eleve, 'classe' => $classe, 'trimestre' => $trimestre, 'school' => $school, 'logo' => $logo];
    @endphp

    <div style="position:relative; width:794px; height:300px;">
        @foreach ($positions as $bloc => $pos)
            <div style="position:absolute; left:{{ $pos['x'] }}px; top:{{ $pos['y'] }}px;">
                @include('pdf.bulletin.blocks.' . $bloc, $blocData)
            </div>
        @endforeach
    </div>

    @include('pdf.bulletin.corps', [
        'lignes' => $lignes,
        'moyennes_trimestres' => $moyennes_trimestres,
        'moyenne_lettre' => $moyenne_lettre,
        'classe' => $classe,
        'trimestre' => $trimestre,
        'moyenne_annuelle' => $moyenne_annuelle,
        'assiduite' => $assiduite,
        'directeur' => $directeur,
    ])
</body>
</html>
