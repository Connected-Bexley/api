<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        $schedule->command(Commands\Ck\Notify\StaleServicesCommand::class)
            ->monthlyOn(15, '09:00');

        $schedule->command(Commands\Ck\Notify\UnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Ck\Notify\StillUnactionedReferralsCommand::class)
            ->dailyAt('09:00');

        $schedule->command(Commands\Ck\AutoDelete\AuditsCommand::class)
            ->daily();

        $schedule->command(Commands\Ck\AutoDelete\PageFeedbacksCommand::class)
            ->daily();

        $schedule->command(Commands\Ck\AutoDelete\PendingAssignmentFilesCommand::class)
            ->daily();

        $schedule->command(Commands\Ck\AutoDelete\ReferralsCommand::class)
            ->daily();

        $schedule->command(Commands\Ck\AutoDelete\ServiceRefreshTokensCommand::class)
            ->daily();

        $schedule->command(Commands\Ck\EndActiveServicesCommand::class)
            ->daily();
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
