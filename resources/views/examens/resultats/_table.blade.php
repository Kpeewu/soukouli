<div class="table-responsive">
    <table class="table table-bordered table-striped table-vcenter">
        <thead>
            <tr>
                <th class="text-center" style="width: 50px;">N°</th>
                <th>Nom & Prenom</th>
                <th class="text-center">N° Inscription</th>
                <th class="text-center">Centre</th>
                <th class="text-center">Moyenne</th>
                <th class="text-center">Mention</th>
                @if($showStatut)
                    <th class="text-center">Statut</th>
                @endif
            </tr>
        </thead>
        <tbody>
            @forelse ($inscriptions as $index => $inscription)
                <tr>
                    <td class="text-center text-primary font-w700">{{ $loop->iteration }}</td>
                    <td class="font-w600">
                        {{ $inscription->eleve->nom ?? '' }} {{ $inscription->eleve->prenom ?? '' }}
                        <br><small class="text-muted">{{ $inscription->eleve->matricule ?? '' }}</small>
                    </td>
                    <td class="text-center">{{ $inscription->numero_inscription ?? '-' }}</td>
                    <td class="text-center">{{ $inscription->centre_examen ?? '-' }}</td>
                    <td class="text-center font-w700">
                        @if($inscription->moyenne_obtenue !== null)
                            <span class="{{ $inscription->moyenne_obtenue >= 10 ? 'text-success' : 'text-danger' }}">
                                {{ number_format($inscription->moyenne_obtenue, 2) }}/20
                            </span>
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-center">
                        @if($inscription->mention)
                            @php
                                $mentionClass = match($inscription->mention) {
                                    'TB' => 'success',
                                    'B' => 'info',
                                    'AB' => 'primary',
                                    'Insuffisant' => 'danger',
                                    default => 'secondary'
                                };
                            @endphp
                            <span class="badge badge-{{ $mentionClass }}">{{ $inscription->mention }}</span>
                        @else
                            -
                        @endif
                    </td>
                    @if($showStatut)
                        <td class="text-center">
                            @if($inscription->statut === 'admis')
                                <span class="badge badge-success">Admis</span>
                            @elseif($inscription->statut === 'ajourne')
                                <span class="badge badge-danger">Ajourne</span>
                            @elseif($inscription->statut === 'absent')
                                <span class="badge badge-warning">Absent</span>
                            @else
                                <span class="badge badge-secondary">En attente</span>
                            @endif
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td class="text-center p-3" colspan="{{ $showStatut ? 7 : 6 }}">Aucun resultat</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
