\documentclass[12pt,a4paper]{article}
\usepackage[utf8]{inputenc}
\usepackage{amsmath}
\usepackage{amsfonts}
\usepackage{amssymb}
\usepackage{makeidx}
\usepackage{graphicx}
\usepackage{lmodern}
\usepackage{longtable}
\usepackage[left=1cm,right=1cm,top=1cm,bottom=1cm]{geometry}

\pagestyle{empty}
\setlength{\parindent}{0pt}

\begin{document}

@php
    $anneeLabel = $annee->annee ?? '';
@endphp

@foreach ($eleves as $eleve)
    @php
        $timestamp = strtotime($eleve->date_naissance);
        $dateNaissance = $timestamp ? date('d-m-Y', $timestamp) : '-';
    @endphp
    \fbox{%
    \begin{minipage}[t][5.4cm][t]{8.5cm}
    \centering
    \vspace{2mm}
    \includegraphics[height=1.3cm]{@latex($logo)}\\[1mm]
    {\small \textbf{@latex($school['name'])}}\\[1mm]
    {\footnotesize \textbf{CARTE D'IDENTITE SCOLAIRE} -- @latex($anneeLabel)}\\[2mm]

    \begin{minipage}[t]{2.6cm}
    \centering
@if (!empty($eleve->profil) && is_file(public_path('/storage/' . $eleve->profil)))
    \includegraphics[height=2.5cm]{@latex(public_path('/storage/' . $eleve->profil))}
@else
    \includegraphics[height=2.5cm]{@latex($photo_passeport)}
@endif
    \end{minipage}%
    \hspace{3mm}%
    \begin{minipage}[t]{5.5cm}
    \raggedright
    \footnotesize
    \textbf{@latex($eleve->nom) @latex($eleve->prenom)}\\[1mm]
    Matricule: @latex($eleve->matricule)\\
    Classe: @latex($classe->nom)\\
    Né(e) le: @latex($dateNaissance)
    \end{minipage}
    \end{minipage}%
    }
    @if ($loop->iteration % 2 === 0)
        \par\vspace{4mm}
    @else
        \hspace{4mm}%
    @endif
    @if ($loop->iteration % 8 === 0 && !$loop->last)
        \newpage
    @endif
@endforeach

\end{document}
