<?php

namespace App\Console\Commands\Concerns;

/**
 * Garde-fou partage par les commandes de demonstration.
 *
 * Ces commandes effacent la base de donnees : la seule barriere qui compte
 * est le drapeau DEMO_MODE, et --force ne la contourne jamais.
 */
trait GuardsDemoMode
{
    protected function guardDemoMode(string $question): bool
    {
        if (!config('demo.enabled')) {
            $this->error('DEMO_MODE n\'est pas actif : commande refusee.');
            $this->line('Definir DEMO_MODE=true dans .env pour l\'activer.');

            return false;
        }

        if (config('app.debug')) {
            $this->warn('ATTENTION : APP_DEBUG=true. Ne pas exposer cette demo publiquement');
            $this->warn('(une page d\'erreur revelerait les identifiants de la base).');
        }

        // Sans TTY (scheduler, entrypoint Docker), confirm() renvoie le defaut
        // false : --force est obligatoire dans ces contextes.
        if (!$this->option('force') && !$this->confirm($question, false)) {
            $this->line('Annule.');

            return false;
        }

        return true;
    }
}
