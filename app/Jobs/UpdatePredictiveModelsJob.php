<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use Carbon\Carbon;
use Exception;
use Throwable;

/**
 * Daily job for automatic predictive model updates
 * 
 * Scheduled to run daily at 02:00 AM, this job:
 * - Backs up current prediction models/cache before updates
 * - Recalculates all prediction models with latest data
 * - Updates prediction cache with new results
 * - Monitors execution time (must complete within 10 minutes)
 * - Handles errors gracefully with administrator notifications
 * - Logs all operations for debugging and monitoring
 * 
 * Requirements: 1.5, 9.1, 9.2, 9.3, 9.4, 9.5
 */
class UpdatePredictiveModelsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run (10 minutes).
     */
    public int $timeout = 600;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 300; // 5 minutes

    /**
     * Administrator email for notifications
     */
    private const ADMIN_EMAIL = 'admin@sistema-contabilidad.com';

    /**
     * Prediction types to update
     */
    private const PREDICTION_TYPES = ['income', 'expense', 'capacity', 'trends'];

    /**
     * Common filter combinations for cache warming
     */
    private const COMMON_FILTERS = [
        [], // Global view
        ['algorithm' => 'linear_regression'],
        ['algorithm' => 'moving_average'],
        ['algorithm' => 'seasonal']
    ];

    /**
     * Job execution start time for performance monitoring
     */
    private Carbon $startTime;

    /**
     * Backup information for rollback capability
     */
    private array $backupInfo = [];

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        $this->startTime = Carbon::now();
    }

    /**
     * Execute the job.
     */
    public function handle(
        IncomePredictorInterface $incomePredictor,
        ExpenseForecasterInterface $expenseForecaster,
        CapacityAnalyzerInterface $capacityAnalyzer,
        TrendDetectorInterface $trendDetector,
        CacheServiceInterface $cacheService,
        PredictiveConfigInterface $config
    ): void {
        $this->startTime = Carbon::now();
        
        Log::channel('predictive')->info('Starting automatic predictive models update', [
            'scheduled_time' => '02:00 AM',
            'start_time' => $this->startTime->toISOString(),
            'timeout_minutes' => $this->timeout / 60
        ]);

        try {
            // Step 1: Create backup of current models/cache
            $this->createModelBackup($cacheService);
            
            // Step 2: Validate system health before updates
            $this->validateSystemHealth($cacheService);
            
            // Step 3: Recalculate all prediction models
            $this->recalculateAllModels(
                $incomePredictor,
                $expenseForecaster,
                $capacityAnalyzer,
                $trendDetector,
                $config
            );
            
            // Step 4: Update prediction cache with new results
            $this->updatePredictionCache($cacheService);
            
            // Step 5: Warm cache for common filter combinations
            $this->warmCommonPredictions($cacheService);
            
            // Step 6: Validate execution time
            $this->validateExecutionTime();
            
            // Step 7: Log successful completion
            $this->logSuccessfulCompletion();
            
            // Step 8: Clean up old backups
            $this->cleanupOldBackups();
            
        } catch (Exception $e) {
            $this->handleJobFailure($e, $cacheService);
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->error('Predictive models update job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'execution_time_seconds' => $executionTime,
            'backup_info' => $this->backupInfo
        ]);

        // Send administrator notification
        $this->sendAdministratorNotification(
            'Predictive Models Update Failed',
            "The automatic predictive models update job has failed permanently after {$this->attempts()} attempts.\n\n" .
            "Error: {$exception->getMessage()}\n" .
            "Execution time: {$executionTime} seconds\n" .
            "Backup available: " . (!empty($this->backupInfo) ? 'Yes' : 'No') . "\n\n" .
            "Please check the logs and consider manual intervention."
        );
    }

    /**
     * Create backup of current prediction models and cache
     */
    private function createModelBackup(CacheServiceInterface $cacheService): void
    {
        Log::channel('predictive')->info('Creating model backup before update');
        
        try {
            $backupTimestamp = $this->startTime->format('Y_m_d_H_i_s');
            
            // Backup prediction cache table
            $cacheBackupTable = "prediction_cache_backup_{$backupTimestamp}";
            DB::statement("CREATE TABLE {$cacheBackupTable} AS SELECT * FROM prediction_cache");
            
            // Backup accuracy log
            $accuracyBackupTable = "prediction_accuracy_log_backup_{$backupTimestamp}";
            DB::statement("CREATE TABLE {$accuracyBackupTable} AS SELECT * FROM prediction_accuracy_log");
            
            // Store backup information
            $this->backupInfo = [
                'timestamp' => $backupTimestamp,
                'cache_table' => $cacheBackupTable,
                'accuracy_table' => $accuracyBackupTable,
                'cache_entries_backed_up' => DB::table('prediction_cache')->count(),
                'accuracy_entries_backed_up' => DB::table('prediction_accuracy_log')->count()
            ];
            
            Log::channel('predictive')->info('Model backup created successfully', $this->backupInfo);
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to create model backup', [
                'error' => $e->getMessage()
            ]);
            throw new Exception("Model backup failed: {$e->getMessage()}");
        }
    }

    /**
     * Validate system health before starting updates
     */
    private function validateSystemHealth(CacheServiceInterface $cacheService): void
    {
        Log::channel('predictive')->info('Validating system health before updates');
        
        // Check cache service health
        if (!$cacheService->isHealthy()) {
            throw new Exception('Cache service is not healthy - aborting update');
        }
        
        // Check database connectivity
        try {
            DB::connection()->getPdo();
        } catch (Exception $e) {
            throw new Exception("Database connectivity check failed: {$e->getMessage()}");
        }
        
        // Check available disk space (basic check)
        $freeSpace = disk_free_space(storage_path());
        $requiredSpace = 100 * 1024 * 1024; // 100MB minimum
        
        if ($freeSpace < $requiredSpace) {
            throw new Exception('Insufficient disk space for model updates');
        }
        
        Log::channel('predictive')->info('System health validation passed');
    }

    /**
     * Recalculate all prediction models with latest data
     */
    private function recalculateAllModels(
        IncomePredictorInterface $incomePredictor,
        ExpenseForecasterInterface $expenseForecaster,
        CapacityAnalyzerInterface $capacityAnalyzer,
        TrendDetectorInterface $trendDetector,
        PredictiveConfigInterface $config
    ): void {
        Log::channel('predictive')->info('Starting model recalculation with latest data');
        
        $modelsUpdated = 0;
        $errors = [];
        
        try {
            // Get active algorithms from configuration
            $activeAlgorithms = $config->getWithOverride('active_algorithms', ['linear_regression', 'moving_average', 'seasonal']);
            
            // Recalculate income predictions for each algorithm
            foreach ($activeAlgorithms as $algorithm) {
                try {
                    $filters = ['algorithm' => $algorithm];
                    $result = $incomePredictor->predictIncome($filters, 12);
                    
                    Log::channel('predictive')->debug('Income prediction recalculated', [
                        'algorithm' => $algorithm,
                        'accuracy' => $result->accuracy
                    ]);
                    
                    $modelsUpdated++;
                } catch (Exception $e) {
                    $errors[] = "Income prediction ({$algorithm}): {$e->getMessage()}";
                    Log::channel('predictive')->warning('Failed to recalculate income prediction', [
                        'algorithm' => $algorithm,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Recalculate expense forecasts
            try {
                $expenseForecast = $expenseForecaster->forecastExpenses([], 12);
                
                Log::channel('predictive')->debug('Expense forecast recalculated', [
                    'correlation' => $expenseForecast->correlation,
                    'alerts_count' => count($expenseForecast->alerts)
                ]);
                
                $modelsUpdated++;
            } catch (Exception $e) {
                $errors[] = "Expense forecast: {$e->getMessage()}";
                Log::channel('predictive')->warning('Failed to recalculate expense forecast', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Recalculate capacity analysis
            try {
                $capacityAnalysis = $capacityAnalyzer->analyzeCurrentCapacity([]);
                
                Log::channel('predictive')->debug('Capacity analysis recalculated', [
                    'current_utilization' => $capacityAnalysis->currentUtilization,
                    'bottlenecks_count' => count($capacityAnalysis->bottlenecks)
                ]);
                
                $modelsUpdated++;
            } catch (Exception $e) {
                $errors[] = "Capacity analysis: {$e->getMessage()}";
                Log::channel('predictive')->warning('Failed to recalculate capacity analysis', [
                    'error' => $e->getMessage()
                ]);
            }
            
            // Recalculate trend analysis (if sufficient data)
            try {
                // Get historical data for trend analysis
                $historicalData = $this->getHistoricalDataForTrends();
                
                if (count($historicalData) >= 24) { // Minimum 24 months for seasonal analysis
                    $seasonalAnalysis = $trendDetector->detectSeasonalPatterns($historicalData);
                    
                    Log::channel('predictive')->debug('Trend analysis recalculated', [
                        'seasonal_patterns_detected' => count($seasonalAnalysis->patterns ?? [])
                    ]);
                    
                    $modelsUpdated++;
                } else {
                    Log::channel('predictive')->info('Skipping trend analysis - insufficient historical data', [
                        'available_months' => count($historicalData),
                        'required_months' => 24
                    ]);
                }
            } catch (Exception $e) {
                $errors[] = "Trend analysis: {$e->getMessage()}";
                Log::channel('predictive')->warning('Failed to recalculate trend analysis', [
                    'error' => $e->getMessage()
                ]);
            }
            
            Log::channel('predictive')->info('Model recalculation completed', [
                'models_updated' => $modelsUpdated,
                'errors_count' => count($errors),
                'errors' => $errors
            ]);
            
            // If too many errors occurred, consider it a failure
            if (count($errors) > 2) {
                throw new Exception('Too many model recalculation errors: ' . implode('; ', $errors));
            }
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Model recalculation failed', [
                'error' => $e->getMessage(),
                'models_updated' => $modelsUpdated,
                'errors' => $errors
            ]);
            throw $e;
        }
    }

    /**
     * Update prediction cache with new results
     */
    private function updatePredictionCache(CacheServiceInterface $cacheService): void
    {
        Log::channel('predictive')->info('Updating prediction cache with new results');
        
        try {
            // Invalidate all existing cache to force regeneration
            $invalidatedCount = $cacheService->invalidateCache();
            
            Log::channel('predictive')->info('Cache invalidated for update', [
                'invalidated_entries' => $invalidatedCount
            ]);
            
            // Clean expired entries
            $cleanedCount = $cacheService->cleanExpiredEntries();
            
            Log::channel('predictive')->info('Expired cache entries cleaned', [
                'cleaned_entries' => $cleanedCount
            ]);
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to update prediction cache', [
                'error' => $e->getMessage()
            ]);
            throw new Exception("Cache update failed: {$e->getMessage()}");
        }
    }

    /**
     * Warm cache for common prediction combinations
     */
    private function warmCommonPredictions(CacheServiceInterface $cacheService): void
    {
        Log::channel('predictive')->info('Warming cache for common predictions');
        
        try {
            $warmedCount = $cacheService->warmCache(self::PREDICTION_TYPES, self::COMMON_FILTERS);
            
            Log::channel('predictive')->info('Cache warming completed', [
                'warmed_entries' => $warmedCount,
                'prediction_types' => self::PREDICTION_TYPES,
                'filter_combinations' => count(self::COMMON_FILTERS)
            ]);
            
        } catch (Exception $e) {
            // Cache warming failure is not critical - log warning but don't fail job
            Log::channel('predictive')->warning('Cache warming failed', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate execution time meets performance requirements
     */
    private function validateExecutionTime(): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        $maxExecutionTime = 600; // 10 minutes in seconds
        
        Log::channel('predictive')->info('Validating execution time', [
            'execution_time_seconds' => $executionTime,
            'max_allowed_seconds' => $maxExecutionTime,
            'performance_requirement_met' => $executionTime <= $maxExecutionTime
        ]);
        
        if ($executionTime > $maxExecutionTime) {
            Log::channel('predictive')->warning('Execution time exceeded performance requirement', [
                'execution_time_minutes' => round($executionTime / 60, 2),
                'max_allowed_minutes' => 10
            ]);
            
            // Send performance warning notification
            $this->sendAdministratorNotification(
                'Predictive Models Update Performance Warning',
                "The predictive models update job completed but exceeded the 10-minute performance requirement.\n\n" .
                "Execution time: " . round($executionTime / 60, 2) . " minutes\n" .
                "Maximum allowed: 10 minutes\n\n" .
                "Consider optimizing the update process or reviewing system performance."
            );
        }
    }

    /**
     * Log successful completion with statistics
     */
    private function logSuccessfulCompletion(): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->info('Predictive models update completed successfully', [
            'start_time' => $this->startTime->toISOString(),
            'end_time' => Carbon::now()->toISOString(),
            'execution_time_seconds' => $executionTime,
            'execution_time_minutes' => round($executionTime / 60, 2),
            'performance_requirement_met' => $executionTime <= 600,
            'backup_created' => !empty($this->backupInfo),
            'next_scheduled_run' => Carbon::tomorrow()->setTime(2, 0)->toISOString()
        ]);
        
        // Update last successful run timestamp
        Cache::put('predictive_last_successful_update', Carbon::now()->toISOString(), 24 * 60 * 60);
    }

    /**
     * Clean up old backup tables to save space
     */
    private function cleanupOldBackups(): void
    {
        try {
            // Keep backups for 7 days
            $cutoffDate = Carbon::now()->subDays(7)->format('Y_m_d');
            
            // Get all backup tables
            $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'prediction_%_backup_%'");
            
            $deletedTables = 0;
            foreach ($tables as $table) {
                $tableName = $table->name;
                
                // Extract date from table name (format: prediction_cache_backup_YYYY_MM_DD_HH_ii_ss)
                if (preg_match('/backup_(\d{4}_\d{2}_\d{2})_/', $tableName, $matches)) {
                    $backupDate = $matches[1];
                    
                    if ($backupDate < $cutoffDate) {
                        DB::statement("DROP TABLE IF EXISTS {$tableName}");
                        $deletedTables++;
                    }
                }
            }
            
            if ($deletedTables > 0) {
                Log::channel('predictive')->info('Old backup tables cleaned up', [
                    'deleted_tables' => $deletedTables,
                    'cutoff_date' => $cutoffDate
                ]);
            }
            
        } catch (Exception $e) {
            // Cleanup failure is not critical
            Log::channel('predictive')->warning('Failed to clean up old backups', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Handle job failure with rollback and notifications
     */
    private function handleJobFailure(Exception $exception, CacheServiceInterface $cacheService): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->error('Predictive models update job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'execution_time_seconds' => $executionTime,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries
        ]);
        
        // Attempt to restore from backup if available
        if (!empty($this->backupInfo)) {
            try {
                $this->restoreFromBackup();
                Log::channel('predictive')->info('Successfully restored from backup after failure');
            } catch (Exception $restoreException) {
                Log::channel('predictive')->error('Failed to restore from backup', [
                    'restore_error' => $restoreException->getMessage()
                ]);
            }
        }
        
        // Send administrator notification if this is the final attempt
        if ($this->attempts() >= $this->tries) {
            $this->sendAdministratorNotification(
                'Predictive Models Update Failed',
                "The automatic predictive models update job has failed after {$this->attempts()} attempts.\n\n" .
                "Error: {$exception->getMessage()}\n" .
                "Execution time: {$executionTime} seconds\n" .
                "Backup restored: " . (!empty($this->backupInfo) ? 'Yes' : 'No') . "\n\n" .
                "Please investigate and consider manual intervention."
            );
        }
    }

    /**
     * Restore prediction cache from backup
     */
    private function restoreFromBackup(): void
    {
        if (empty($this->backupInfo)) {
            throw new Exception('No backup information available for restore');
        }
        
        Log::channel('predictive')->info('Restoring from backup', $this->backupInfo);
        
        DB::beginTransaction();
        
        try {
            // Clear current tables
            DB::table('prediction_cache')->delete();
            DB::table('prediction_accuracy_log')->delete();
            
            // Restore from backup tables
            DB::statement("INSERT INTO prediction_cache SELECT * FROM {$this->backupInfo['cache_table']}");
            DB::statement("INSERT INTO prediction_accuracy_log SELECT * FROM {$this->backupInfo['accuracy_table']}");
            
            DB::commit();
            
            Log::channel('predictive')->info('Backup restore completed successfully');
            
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Backup restore failed: {$e->getMessage()}");
        }
    }

    /**
     * Send notification to administrator
     */
    private function sendAdministratorNotification(string $subject, string $message): void
    {
        try {
            // For now, just log the notification
            // In a real implementation, this would send an email
            Log::channel('predictive')->info('Administrator notification', [
                'subject' => $subject,
                'message' => $message,
                'recipient' => self::ADMIN_EMAIL
            ]);
            
            // TODO: Implement actual email sending when mail configuration is available
            // Mail::raw($message, function ($mail) use ($subject) {
            //     $mail->to(self::ADMIN_EMAIL)
            //          ->subject($subject);
            // });
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to send administrator notification', [
                'error' => $e->getMessage(),
                'subject' => $subject
            ]);
        }
    }

    /**
     * Get historical data for trend analysis
     */
    private function getHistoricalDataForTrends(): array
    {
        try {
            // Get last 36 months of data for comprehensive trend analysis
            $startDate = Carbon::now()->subMonths(36)->format('Y-m-d');
            
            $results = DB::table('repases')
                ->select([
                    DB::raw('strftime("%Y-%m", fecha) as month'),
                    DB::raw('SUM(total) as total_ingresos'),
                    DB::raw('COUNT(*) as total_repases')
                ])
                ->where('fecha', '>=', $startDate)
                ->groupBy('month')
                ->orderBy('month')
                ->get();
            
            return $results->map(function ($item) {
                return [
                    'month' => $item->month,
                    'total_ingresos' => (float) $item->total_ingresos,
                    'total_repases' => (int) $item->total_repases
                ];
            })->toArray();
            
        } catch (Exception $e) {
            Log::channel('predictive')->warning('Failed to get historical data for trends', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}