<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Contracts\Predictive\CacheServiceInterface;

/**
 * Job for automatic predictive cache maintenance
 * 
 * Cleans expired cache entries and performs health checks
 * to maintain optimal cache performance.
 */
class CleanPredictiveCacheJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        // Job can be created without parameters
    }

    /**
     * Execute the job.
     */
    public function handle(CacheServiceInterface $cacheService): void
    {
        Log::channel('predictive')->info('Starting automatic cache maintenance');
        
        try {
            // Clean expired entries
            $cleanedCount = $cacheService->cleanExpiredEntries();
            
            Log::channel('predictive')->info('Cache maintenance completed', [
                'cleaned_entries' => $cleanedCount
            ]);
            
            // Check cache health
            $isHealthy = $cacheService->isHealthy();
            
            if (!$isHealthy) {
                Log::channel('predictive')->warning('Cache health check failed during maintenance');
                
                // Get statistics for debugging
                $stats = $cacheService->getCacheStatistics();
                Log::channel('predictive')->warning('Cache statistics during health failure', [
                    'hit_rate' => $stats['hit_rate_percentage'] ?? 'unknown',
                    'total_requests' => $stats['total_requests'] ?? 'unknown',
                    'cache_health' => $stats['cache_health'] ?? false
                ]);
            }
            
            // Log cache statistics periodically
            $this->logCacheStatistics($cacheService);
            
        } catch (\Exception $e) {
            Log::channel('predictive')->error('Cache maintenance job failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Re-throw to mark job as failed
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::channel('predictive')->error('Cache maintenance job failed permanently', [
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);
    }

    /**
     * Log cache statistics for monitoring
     */
    private function logCacheStatistics(CacheServiceInterface $cacheService): void
    {
        try {
            $stats = $cacheService->getCacheStatistics();
            
            // Log key metrics for monitoring
            Log::channel('predictive')->info('Cache performance metrics', [
                'hit_rate_percentage' => $stats['hit_rate_percentage'],
                'memory_hit_rate' => $stats['memory_hit_rate'],
                'total_requests' => $stats['total_requests'],
                'cache_health' => $stats['cache_health'],
                'timestamp' => now()->toISOString()
            ]);
            
            // Log warnings for poor performance
            if ($stats['hit_rate_percentage'] < 50 && $stats['total_requests'] > 100) {
                Log::channel('predictive')->warning('Low cache hit rate detected', [
                    'hit_rate' => $stats['hit_rate_percentage'],
                    'total_requests' => $stats['total_requests']
                ]);
            }
            
        } catch (\Exception $e) {
            Log::channel('predictive')->debug('Failed to log cache statistics', [
                'error' => $e->getMessage()
            ]);
        }
    }
}