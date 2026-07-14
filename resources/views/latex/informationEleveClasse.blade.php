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


\begin{document}


@foreach ($eleves as $eleve)
    \vspace{-2cm}
    \begin{flushleft}
    \begin{figure}[!h]
    \includegraphics[scale=0.6]{@latex($logo)}
    \end{figure}
    \end{flushleft}


    \vspace{-3.5cm}

    \begin{flushright}
    \huge
    \textbf{Fiche d'information}\\
    \small
    \vspace{0cm}
    @latex($school['full_name'])\\
    \end{flushright}

    \vspace{0cm}

    \begin{figure}[!h]
    @if (!empty($eleve->profil) && is_file(public_path('/storage/' . $eleve->profil)))
        \includegraphics[height=5.4cm]{@latex(public_path('/storage/' . $eleve->profil))}
    @else
        \includegraphics[height=5.4cm]{@latex(public_path('/assets/media/avatars/avatar1.jpg'))}
    @endif
    \end{figure}



    \vspace{-5.9cm}
    \hspace{5cm}
    \renewcommand{\arraystretch}{1.3}
    \begin{tabular}{|p{12.7cm}|}
    \hline
    \textbf{\large \underline{Informations générales}}\\
    \textbf{Nom:} @latex($eleve->nom)\\
    \textbf{Prénom:} @latex($eleve->prenom)\\
    \textbf{Sexe:} @if ($eleve->sexe === 'M')
        Masculin
    @else
        Féminin
    @endif\\
    @php
        $date = $eleve->date_naissance;

        // Création du timestamp à partir du date donnée
        $timestamp = strtotime($date);

        // Créer le nouveau format à partir du timestamp
        $date_naissance = date('d-m-Y', $timestamp);
    @endphp
    \textbf{Date de Naissance:} @latex($date_naissance)\\
    \textbf{Lieu de Naissance:} @latex($eleve->lieu_naissance)\\
    \textbf{Adresse:} @latex($eleve->adresse)\\
    \textbf{Matricule:} @latex($eleve->matricule)\\
    \hline
    \end{tabular}


    @php
        $pere = $eleve->pere ?? [];
        $mere = $eleve->mere ?? [];
        $tuteur = $eleve->contact_tuteur ?? [];
    @endphp

    \vspace{0.5cm}
    \renewcommand{\arraystretch}{1.5}
    \hspace{-0.8cm}
    \begin{tabular}{|p{7cm} p{11.2cm}|}
    \hline
    \textbf{\large \underline{Responsables légaux}} & \\

    & \\

    \textbf{\large Père} & \\
    \textbf{Nom:} @latex($pere['nom'] ?? '-') & \textbf{Prénom:} @latex($pere['prenom'] ?? '-') \\
    \textbf{Contact:} @latex($pere['telephone'] ?? '-') & \textbf{Adresse:} @latex($pere['adresse'] ?? '-')\\
    \textbf{Profession:} @latex($pere['profession'] ?? '-') & \textbf{Situation Matrimoniale:} @latex($pere['situation_matrimoniale'] ?? '-')\\

    & \\

    \textbf{\large Mère} & \\
    \textbf{Nom:} @latex($mere['nom'] ?? '-') & \textbf{Prénom:} @latex($mere['prenom'] ?? '-') \\
    \textbf{Contact:} @latex($mere['telephone'] ?? '-') & \textbf{Adresse:} @latex($mere['adresse'] ?? '-')\\
    \textbf{Profession:} @latex($mere['profession'] ?? '-') & \textbf{Situation Matrimoniale:} @latex($mere['situation_matrimoniale'] ?? '-')\\

    & \\

    \textbf{\large Tuteur/Tutrice} & \\
    \textbf{Nom:} @latex($tuteur['nom'] ?? '-') & \textbf{Prénom:} @latex($tuteur['prenom'] ?? '-') \\
    \textbf{Contact:} @latex($tuteur['telephone'] ?? '-') & \textbf{Adresse:} @latex($tuteur['adresse'] ?? '-')\\
    \textbf{Profession:} @latex($tuteur['profession'] ?? '-') & \textbf{Situation Matrimoniale:} @latex($tuteur['situation_matrimoniale'] ?? '-')\\


    \hline
    \end{tabular}



    @php
        $sante = $eleve->sante ?? [];
    @endphp

    \vspace{0.5cm}
    \renewcommand{\arraystretch}{1.5}
    \hspace{-0.8cm}
    \begin{tabular}{|p{7cm} p{11.2cm}|}
    \hline
    \textbf{\large \underline{Informations sur la santé de l'élève}} & \\
    \textbf{Groupe Sanguin:} @latex($sante['groupe'] ?? $sante['groupe_sanguin'] ?? '-') & \textbf{Problème de santé importants:} @latex($sante['problemes'] ?? $sante['maladies_chroniques'] ?? '-') \\
    \textbf{Restrictions d'activités:} @latex($sante['restrictions'] ?? $sante['allergies'] ?? '-') & \textbf{Médicaments pris régulièrement:} @latex($sante['medicaments'] ?? '-')
    \\
    \hline
    \end{tabular}

    \newpage
@endforeach
















\end{document}
