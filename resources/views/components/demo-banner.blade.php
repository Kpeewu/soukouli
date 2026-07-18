@if (config('demo.enabled'))
    {{--
        Barre fixee en BAS de fenetre, volontairement.
        Le theme OneUI place #page-header en position: fixed; top: 0 via la
        classe page-header-fixed : une banniere dans le flux normal ou fixee
        en haut serait recouverte sur toutes les vues du tableau de bord, ou
        imposerait de recalculer les offsets de la sidebar et du header.
        En bas, aucune interference avec l'existant.
    --}}
    <style>
        .demo-banner {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            z-index: 1040;
            background: #ffc107;
            color: #212529;
            padding: .4rem .75rem;
            text-align: center;
            font-size: .8125rem;
            line-height: 1.4;
            box-shadow: 0 -2px 8px rgba(0, 0, 0, .15);
        }

        .demo-banner a {
            color: #212529;
            text-decoration: underline;
        }

        .demo-banner code {
            color: #6c1f1f;
            background: rgba(255, 255, 255, .55);
            padding: 0 .2rem;
            border-radius: 2px;
        }

        .demo-banner-compte {
            display: inline-block;
            margin: .15rem .25rem;
            white-space: nowrap;
        }
    </style>

    <div class="demo-banner">
        <strong>Version de démonstration</strong> &mdash; données fictives,
        réinitialisées automatiquement.
        <a data-toggle="collapse" href="#demo-credentials" role="button" aria-expanded="false"
           aria-controls="demo-credentials">Comptes de test</a>

        <div class="collapse mt-1" id="demo-credentials">
            @foreach (config('demo.credentials') as $compte)
                <span class="demo-banner-compte">
                    {{ $compte['role'] }} :
                    <code>{{ $compte['login'] }}</code> /
                    <code>{{ $compte['password'] }}</code>
                </span>
            @endforeach
        </div>
    </div>
@endif
