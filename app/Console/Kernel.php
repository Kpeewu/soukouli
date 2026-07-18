<?php

namespace App\Console;

use App\Console\Commands\DemoReset;
use App\Console\Commands\NouvelleAnneeScolaire;
use App\Console\Commands\PassageEleves;
use App\Services\CronScheduleService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(NouvelleAnneeScolaire::class)->everyFiveMinutes()->when(function () {
                 return CronScheduleService::isEnabledThisMonth(
                     CronScheduleService::KEY_ANNEE_ENABLED,
                     CronScheduleService::KEY_ANNEE_MONTH,
                     8
                 );
             })->sendOutputTo(storage_path('logs/nouvelleAnneeSchedule.log'))->withoutOverlapping();

        $schedule->command(PassageEleves::class)->everyTenMinutes()->when(function () {
                 return CronScheduleService::isEnabledThisMonth(
                     CronScheduleService::KEY_PASSAGE_ENABLED,
                     CronScheduleService::KEY_PASSAGE_MONTH,
                     8,
                     false
                 );
             })->sendOutputTo(storage_path('logs/passageElevesSchedule.log'))->withoutOverlapping();

        // Instance de demonstration : on repart d'une base propre chaque nuit,
        // quoi qu'aient saisi les visiteurs dans la journee.
        //
        // --force est indispensable : le conteneur scheduler n'a pas de TTY,
        // et sans lui confirm() renverrait false et le reset echouerait
        // silencieusement chaque nuit.
        //
        // Pas de onOneServer() : il exige un driver de cache verrouillable,
        // or CACHE_DRIVER=file. Il n'y a de toute facon qu'un scheduler.
        if (config('demo.enabled') && config('demo.auto_reset')) {
            $schedule->command(DemoReset::class, ['--force' => true])
                     ->dailyAt(config('demo.auto_reset_time', '03:00'))
                     ->sendOutputTo(storage_path('logs/demoReset.log'))
                     ->withoutOverlapping();
        }
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}
