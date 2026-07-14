\documentclass[10pt,a5paper]{article}
\usepackage[utf8]{inputenc}
\usepackage[french]{babel}
\usepackage{graphicx}
\usepackage[table]{xcolor}
\usepackage{array}
\usepackage{tabularx}
\usepackage[left=1.2cm,right=1.2cm,top=0.8cm,bottom=0.8cm]{geometry}
\pagestyle{empty}
\setlength{\parindent}{0pt}

% Colonnes centrees
\newcolumntype{C}[1]{>{\centering\arraybackslash}p{#1}}
\newcolumntype{L}[1]{>{\raggedright\arraybackslash}p{#1}}
\newcolumntype{R}[1]{>{\raggedleft\arraybackslash}p{#1}}

\begin{document}

% En-tete
\begin{center}
\small
\textsc{Ministère des Enseignements Primaire et Secondaire}\\
\textsc{Technique et de l'Artisanat}\\
\vspace{0.1cm}
\large
\textbf{@latex($school['type']) @latex($school['name'])}\\
\small
@latex($school['motto'])\\
@latex($school['bp']) \textit{@latex($school['city']) - @latex($school['country'])}\\
\vspace{0.15cm}
\rule{9cm}{0.4pt}\\
\vspace{0.1cm}
{\large \textbf{REÇU DE PAIEMENT}}\\
\vspace{0.05cm}
{\normalsize \textbf{N° {{ $recu->numero }}}}\\
\vspace{0.1cm}
\rule{9cm}{0.4pt}\\
\end{center}

\vspace{0.2cm}

% Informations du recu
\begin{tabular}{@{}ll@{}}
\textbf{Date d'émission:} & {{ $recu->date_emission->format('d/m/Y à H:i') }}\\
\end{tabular}

\vspace{0.2cm}

% Informations de l'eleve
\noindent\fbox{
\begin{minipage}{0.95\textwidth}
\vspace{0.1cm}
\begin{tabular}{@{}ll@{}}
\textbf{Élève:} & {{ $eleve->nom }} {{ $eleve->prenom }}\\
\textbf{Matricule:} & {{ $eleve->matricule }}\\
@php
    $classe = $eleve->getClasseActuelle();
@endphp
\textbf{Classe:} & {{ $classe ? $classe->nom : '-' }}\\
@if($classe && $classe->promotion && $classe->promotion->cycle)
\textbf{Cycle:} & {{ $classe->promotion->cycle->nom }}\\
@endif
\end{tabular}
\vspace{0.1cm}
\end{minipage}
}

\vspace{0.3cm}

% Tableau des details du paiement
\begin{center}
\begin{tabular}{|L{8cm}|R{3cm}|}
\hline
\rowcolor{gray!30}
\textbf{Désignation} & \textbf{Montant (FCFA)}\\
\hline
{{ $paiement->configurationFrais->typeFrais->nom ?? $paiement->motif }}
@if($paiement->tranche)
 - {{ $paiement->tranche->nom }}
@endif
& {{ number_format($paiement->montant, 0, ' ', ' ') }}\\
\hline
\rowcolor{gray!15}
\textbf{TOTAL} & \textbf{ {{ number_format($paiement->montant, 0, ' ', ' ') }} }\\
\hline
\end{tabular}
\end{center}

\vspace{0.2cm}

% Mode de paiement
\begin{tabular}{@{}ll@{}}
\textbf{Mode de paiement:} & {{ ucfirst(str_replace('_', ' ', $paiement->mode_paiement ?? $paiement->methode)) }}\\
@if($paiement->reference)
\textbf{Référence:} & {{ $paiement->reference }}\\
@endif
@if($paiement->notes)
\textbf{Notes:} & {{ $paiement->notes }}\\
@endif
\end{tabular}

\vspace{0.4cm}

% Signature
\begin{flushright}
\begin{minipage}{6cm}
\centering
\textbf{ {{ $comptable->accordTitre('Le Comptable', 'La Comptable') }} }\\
\vspace{0.6cm}
\rule{5cm}{0.4pt}\\
{{ trim(($comptable->prenom ?? '') . ' ' . ($comptable->nom ?? '')) ?: $comptable->username }}
\end{minipage}
\end{flushright}

\vspace{0.2cm}

% Pied de page
\begin{center}
\footnotesize
\textit{Ce reçu est un document officiel de paiement.}\\
\textit{Conservez-le précieusement pour toute réclamation.}
\end{center}

@if($recu->annule)
\vspace{0.2cm}
\begin{center}
\fcolorbox{red}{white}{
\begin{minipage}{0.8\textwidth}
\centering
\textcolor{red}{\large \textbf{ANNULÉ}}\\
\vspace{0.05cm}
\textcolor{red}{\footnotesize Motif: {{ $recu->motif_annulation }}}
\end{minipage}
}
\end{center}
@endif

\end{document}
