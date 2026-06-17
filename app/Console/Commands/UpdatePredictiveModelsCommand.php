<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\UpdatePredictiveModelsJob;
use Illuminate\Support\Facades\Log;

/**
 * Manual command to trigger predictive models update
 * 
 * This command allows administrators to manually trigger the
 * UpdatePredictiveModelsJob for testing or emergency updates.
 */
class UpdatePredictiveModelsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'predictive:update-models 
                            {--sync : Run synchronously instead of queuing}
                            {--force : Force update even if recent update exists}';

    /**
     * The console command description.
     */
    protected $description = 'Manually trigger predictive models update job';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Triggering predictive models update...');
        
        try {
            // Check if recent update exists (unless forced)
            if (!$this->option('force')) {
                $lastUpdate = cache('predictive_last_successful_update');
                if ($lastUpdate && now()->diffInHours($lastUpdate) < 6) {
                    $this->warn('A recent update was completed less than 6 hours ago.');
                    if (!$this->confirm('Do you want to proceed anyway?')) {
                        $this->info('Update cancelled.');
                        return self::SUCCESS;
                    }
                }
            }
            
            if ($this->option('sync')) {
                // Run synchronously for immediate feedback
                $this->info('Running update synchronously...');
                $this->withProgressBar(1, function () {
                    dispatch_sync(new UpdatePredictiveModelsJob());
                });
                $this->newLine();
                $this->info('Predictive models update completed successfully!');
            } else {
                // Queue the job
                UpdatePredictiveModelsJob::dispatch();
                $this->info('Predictive models update job has been queued.');
                $this->info('Check the logs for progress and completion status.');
            }
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Failed to trigger predictive models update: ' . $e->getMessage());
            
            Log::channel('predictive')->error('Manual update command failed', [
                'error' => $e->getMessage(),
                'user' => $this->getUser()
            ]);
            
            return self::FAILURE;
        }
    }

    /**
     * Get current user for logging
     */
    private function getUser(): string
    {
        return auth()->check() ? auth()->user()->email : 'console';
    }
}