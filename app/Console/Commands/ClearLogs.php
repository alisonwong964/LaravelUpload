<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'clear:logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clear the application logs';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logPath = storage_path('logs/laravel.log');

        if (File::exists($logPath)) {
            File::put($logPath, ''); // Clear the log file
            $this->info('Logs have been cleared!');
        } else {
            $this->info('No log file found to clear.');
        }

        return 0;
    }
}
