<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 1cm 0.5cm; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #000; }
        .page-eleve { page-break-after: always; }
        .page-eleve:last-child { page-break-after: auto; }
    </style>
</head>
<body>
    @php
        $positions = $layout ?? \App\Http\Controllers\BulletinHeaderConfigController::DEFAULT_LAYOUT;
    @endphp

    @foreach ($bulletins as $bulletin)
        @php
            $eleve = $bulletin['eleve'];
            $eleveId = $eleve->id;
            $blocData = ['eleve' => $eleve, 'classe' => $classe, 'trimestre' => $trimestre, 'school' => $school, 'logo' => $logo];
        @endphp
        <div class="page-eleve">
            <div style="position:relative; width:794px; height:300px;">
                @foreach ($positions as $bloc => $pos)
                    <div style="position:absolute; left:{{ $pos['x'] }}px; top:{{ $pos['y'] }}px;">
                        @include('pdf.bulletin.blocks.' . $bloc, $blocData)
                    </div>
                @endforeach
            </div>

            @include('pdf.bulletin.corps', [
                'lignes' => $bulletin[$eleveId],
                'moyennes_trimestres' => $bulletin['moyennes'],
                'moyenne_lettre' => $bulletin['moyenne_lettres'],
                'classe' => $classe,
                'trimestre' => $trimestre,
                'moyenne_annuelle' => $bulletin['moyenne_annuelle'],
                'assiduite' => $bulletin['assiduite'],
                'directeur' => $directeur,
            ])
        </div>
    @endforeach
</body>
</html>
