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
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\PredictiveConfigInterface;
use Carbon\Carbon;
use Exception;
use Throwable;

/**
 * Weekly job for validating prediction accuracy
 * 
 * Scheduled to run weekly, this job:
 * - Compares past predictions with actual values from database
 * - Calculates MAPE (Mean Absolute Percentage Error) and RMSE (Root Mean Square Error)
 * - Detects low accuracy scenarios (< 70%) and generates suggestions
 * - Generates monthly accuracy reports for each prediction type
 * - Stores accuracy metrics in prediction_accuracy_log table
 * - Sends notifications when accuracy drops significantly
 * 
 * Requirements: 8.1, 8.2, 8.3, 8.4, 8.5
 */
class ValidateModelAccuracyJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 1800; // 30 minutes

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 600; // 10 minutes

    /**
     * Administrator email for notifications
     */
    private const ADMIN_EMAIL = 'admin@sistema-contabilidad.com';

    /**
     * Accuracy threshold for generating suggestions (70%)
     */
    private const LOW_ACCURACY_THRESHOLD = 70.0;

    /**
     * Prediction types to validate
     */
    private const PREDICTION_TYPES = [
        'income' => ['linear_regression', 'moving_average', 'seasonal'],
        'expense' => ['forecast'],
        'capacity' => ['utilization'],
        'trends' => ['seasonal']
    ];

    /**
     * Job execution start time
     */
    private Carbon $startTime;

    /**
     * Accuracy validation results
     */
    private array $validationResults = [];

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
        PredictiveConfigInterface $config
    ): void {
        $this->startTime = Carbon::now();
        
        Log::channel('predictive')->info('Starting weekly model accuracy validation', [
            'start_time' => $this->startTime->toISOString(),
            'validation_period' => 'weekly',
            'accuracy_threshold' => self::LOW_ACCURACY_THRESHOLD
        ]);

        try {
            // Step 1: Validate income prediction accuracy
            $this->validateIncomePredictionAccuracy($incomePredictor);
            
            // Step 2: Validate expense forecast accuracy
            $this->validateExpenseForecastAccuracy($expenseForecaster);
            
            // Step 3: Validate capacity analysis accuracy
            $this->validateCapacityAnalysisAccuracy($capacityAnalyzer);
            
            // Step 4: Validate trend detection accuracy
            $this->validateTrendDetectionAccuracy($trendDetector);
            
            // Step 5: Generate low accuracy suggestions
            $this->generateLowAccuracySuggestions($config);
            
            // Step 6: Generate monthly accuracy reports
            $this->generateMonthlyAccuracyReports();
            
            // Step 7: Store accuracy metrics in database
            $this->storeAccuracyMetrics();
            
            // Step 8: Send notifications for significant accuracy drops
            $this->checkForAccuracyDrops();
            
            // Step 9: Log successful completion
            $this->logSuccessfulCompletion();
            
        } catch (Exception $e) {
            $this->handleJobFailure($e);
            throw $e; // Re-throw to mark job as failed
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(Throwable $exception): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->error('Model accuracy validation job failed permanently', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'attempts' => $this->attempts(),
            'execution_time_seconds' => $executionTime,
            'validation_results' => $this->validationResults
        ]);

        // Send administrator notification
        $this->sendAdministratorNotification(
            'Model Accuracy Validation Failed',
            "The weekly model accuracy validation job has failed permanently after {$this->attempts()} attempts.\n\n" .
            "Error: {$exception->getMessage()}\n" .
            "Execution time: {$executionTime} seconds\n\n" .
            "Please check the logs and investigate the accuracy validation system."
        );
    }

    /**
     * Validate income prediction accuracy
     */
    private function validateIncomePredictionAccuracy(IncomePredictorInterface $incomePredictor): void
    {
        Log::channel('predictive')->info('Validating income prediction accuracy');
        
        foreach (self::PREDICTION_TYPES['income'] as $algorithm) {
            try {
                $accuracy = $this->calculatePredictionAccuracy('income', $algorithm);
                
                $this->validationResults['income'][$algorithm] = $accuracy;
                
                Log::channel('predictive')->debug('Income prediction accuracy calculated', [
                    'algorithm' => $algorithm,
                    'mape' => $accuracy['mape'],
                    'rmse' => $accuracy['rmse'],
                    'accuracy_percentage' => $accuracy['accuracy_percentage']
                ]);
                
            } catch (Exception $e) {
                Log::channel('predictive')->warning('Failed to validate income prediction accuracy', [
                    'algorithm' => $algorithm,
                    'error' => $e->getMessage()
                ]);
                
                $this->validationResults['income'][$algorithm] = [
                    'error' => $e->getMessage(),
                    'mape' => null,
                    'rmse' => null,
                    'accuracy_percentage' => null
                ];
            }
        }
    }

    /**
     * Validate expense forecast accuracy
     */
    private function validateExpenseForecastAccuracy(ExpenseForecasterInterface $expenseForecaster): void
    {
        Log::channel('predictive')->info('Validating expense forecast accuracy');
        
        try {
            $accuracy = $this->calculatePredictionAccuracy('expense', 'forecast');
            
            $this->validationResults['expense']['forecast'] = $accuracy;
            
            Log::channel('predictive')->debug('Expense forecast accuracy calculated', [
                'mape' => $accuracy['mape'],
                'rmse' => $accuracy['rmse'],
                'accuracy_percentage' => $accuracy['accuracy_percentage']
            ]);
            
        } catch (Exception $e) {
            Log::channel('predictive')->warning('Failed to validate expense forecast accuracy', [
                'error' => $e->getMessage()
            ]);
            
            $this->validationResults['expense']['forecast'] = [
                'error' => $e->getMessage(),
                'mape' => null,
                'rmse' => null,
                'accuracy_percentage' => null
            ];
        }
    }

    /**
     * Validate capacity analysis accuracy
     */
    private function validateCapacityAnalysisAccuracy(CapacityAnalyzerInterface $capacityAnalyzer): void
    {
        Log::channel('predictive')->info('Validating capacity analysis accuracy');
        
        try {
            $accuracy = $this->calculatePredictionAccuracy('capacity', 'utilization');
            
            $this->validationResults['capacity']['utilization'] = $accuracy;
            
            Log::channel('predictive')->debug('Capacity analysis accuracy calculated', [
                'mape' => $accuracy['mape'],
                'rmse' => $accuracy['rmse'],
                'accuracy_percentage' => $accuracy['accuracy_percentage']
            ]);
            
        } catch (Exception $e) {
            Log::channel('predictive')->warning('Failed to validate capacity analysis accuracy', [
                'error' => $e->getMessage()
            ]);
            
            $this->validationResults['capacity']['utilization'] = [
                'error' => $e->getMessage(),
                'mape' => null,
                'rmse' => null,
                'accuracy_percentage' => null
            ];
        }
    }

    /**
     * Validate trend detection accuracy
     */
    private function validateTrendDetectionAccuracy(TrendDetectorInterface $trendDetector): void
    {
        Log::channel('predictive')->info('Validating trend detection accuracy');
        
        try {
            $accuracy = $this->calculatePredictionAccuracy('trends', 'seasonal');
            
            $this->validationResults['trends']['seasonal'] = $accuracy;
            
            Log::channel('predictive')->debug('Trend detection accuracy calculated', [
                'mape' => $accuracy['mape'],
                'rmse' => $accuracy['rmse'],
                'accuracy_percentage' => $accuracy['accuracy_percentage']
            ]);
            
        } catch (Exception $e) {
            Log::channel('predictive')->warning('Failed to validate trend detection accuracy', [
                'error' => $e->getMessage()
            ]);
            
            $this->validationResults['trends']['seasonal'] = [
                'error' => $e->getMessage(),
                'mape' => null,
                'rmse' => null,
                'accuracy_percentage' => null
            ];
        }
    }
    /**
     * Calculate prediction accuracy using MAPE and RMSE
     */
    private function calculatePredictionAccuracy(string $predictionType, string $algorithm): array
    {
        // Get cached predictions from the last 30 days for comparison
        $predictions = $this->getCachedPredictions($predictionType, $algorithm);
        
        if (empty($predictions)) {
            throw new Exception("No cached predictions found for {$predictionType} - {$algorithm}");
        }
        
        $actualValues = $this->getActualValues($predictionType, $predictions);
        
        if (empty($actualValues)) {
            throw new Exception("No actual values found for comparison");
        }
        
        // Calculate MAPE (Mean Absolute Percentage Error)
        $mape = $this->calculateMAPE($predictions, $actualValues);
        
        // Calculate RMSE (Root Mean Square Error)
        $rmse = $this->calculateRMSE($predictions, $actualValues);
        
        // Convert MAPE to accuracy percentage (100 - MAPE)
        $accuracyPercentage = max(0, 100 - $mape);
        
        return [
            'mape' => round($mape, 2),
            'rmse' => round($rmse, 2),
            'accuracy_percentage' => round($accuracyPercentage, 2),
            'predictions_count' => count($predictions),
            'actual_values_count' => count($actualValues)
        ];
    }

    /**
     * Get cached predictions for comparison
     */
    private function getCachedPredictions(string $predictionType, string $algorithm): array
    {
        $startDate = Carbon::now()->subDays(30)->format('Y-m-d');
        
        $results = DB::table('prediction_cache')
            ->where('prediction_type', $predictionType)
            ->where('created_at', '>=', $startDate)
            ->whereRaw("JSON_EXTRACT(result_data, '$.algorithm') = ?", [$algorithm])
            ->get();
        
        $predictions = [];
        foreach ($results as $result) {
            $resultData = json_decode($result->result_data, true);
            if (isset($resultData['projections'])) {
                $predictions[] = [
                    'date' => $result->created_at,
                    'projections' => $resultData['projections'],
                    'filters_hash' => $result->filters_hash
                ];
            }
        }
        
        return $predictions;
    }

    /**
     * Get actual values for comparison with predictions
     */
    private function getActualValues(string $predictionType, array $predictions): array
    {
        $actualValues = [];
        
        foreach ($predictions as $prediction) {
            $predictionDate = Carbon::parse($prediction['date']);
            
            switch ($predictionType) {
                case 'income':
                    $actualValues[] = $this->getActualIncomeValues($predictionDate, $prediction['projections']);
                    break;
                    
                case 'expense':
                    $actualValues[] = $this->getActualExpenseValues($predictionDate, $prediction['projections']);
                    break;
                    
                case 'capacity':
                    $actualValues[] = $this->getActualCapacityValues($predictionDate, $prediction['projections']);
                    break;
                    
                case 'trends':
                    $actualValues[] = $this->getActualTrendValues($predictionDate, $prediction['projections']);
                    break;
            }
        }
        
        return array_filter($actualValues); // Remove null values
    }

    /**
     * Get actual income values for comparison
     */
    private function getActualIncomeValues(Carbon $predictionDate, array $projections): ?array
    {
        $actualValues = [];
        
        // Compare 3-month projection
        if (isset($projections['3_months'])) {
            $targetDate = $predictionDate->copy()->addMonths(3);
            if ($targetDate->isPast()) {
                $actualIncome = $this->getMonthlyIncome($targetDate);
                if ($actualIncome !== null) {
                    $actualValues['3_months'] = [
                        'predicted' => $projections['3_months'],
                        'actual' => $actualIncome,
                        'target_date' => $targetDate->format('Y-m-d')
                    ];
                }
            }
        }
        
        // Compare 6-month projection
        if (isset($projections['6_months'])) {
            $targetDate = $predictionDate->copy()->addMonths(6);
            if ($targetDate->isPast()) {
                $actualIncome = $this->getMonthlyIncome($targetDate);
                if ($actualIncome !== null) {
                    $actualValues['6_months'] = [
                        'predicted' => $projections['6_months'],
                        'actual' => $actualIncome,
                        'target_date' => $targetDate->format('Y-m-d')
                    ];
                }
            }
        }
        
        return !empty($actualValues) ? $actualValues : null;
    }

    /**
     * Get actual expense values for comparison
     */
    private function getActualExpenseValues(Carbon $predictionDate, array $projections): ?array
    {
        $actualValues = [];
        
        // Compare 3-month projection
        if (isset($projections['3_months'])) {
            $targetDate = $predictionDate->copy()->addMonths(3);
            if ($targetDate->isPast()) {
                $actualExpenses = $this->getMonthlyExpenses($targetDate);
                if ($actualExpenses !== null) {
                    $actualValues['3_months'] = [
                        'predicted' => $projections['3_months'],
                        'actual' => $actualExpenses,
                        'target_date' => $targetDate->format('Y-m-d')
                    ];
                }
            }
        }
        
        return !empty($actualValues) ? $actualValues : null;
    }

    /**
     * Get actual capacity values for comparison
     */
    private function getActualCapacityValues(Carbon $predictionDate, array $projections): ?array
    {
        $actualValues = [];
        
        // Compare utilization projection
        if (isset($projections['utilization'])) {
            $targetDate = $predictionDate->copy()->addMonth();
            if ($targetDate->isPast()) {
                $actualUtilization = $this->getMonthlyUtilization($targetDate);
                if ($actualUtilization !== null) {
                    $actualValues['utilization'] = [
                        'predicted' => $projections['utilization'],
                        'actual' => $actualUtilization,
                        'target_date' => $targetDate->format('Y-m-d')
                    ];
                }
            }
        }
        
        return !empty($actualValues) ? $actualValues : null;
    }

    /**
     * Get actual trend values for comparison
     */
    private function getActualTrendValues(Carbon $predictionDate, array $projections): ?array
    {
        // Trend validation is more complex and may require seasonal comparison
        // For now, return null to skip trend accuracy validation
        return null;
    }

    /**
     * Get monthly income from database
     */
    private function getMonthlyIncome(Carbon $targetDate): ?float
    {
        $monthStart = $targetDate->copy()->startOfMonth();
        $monthEnd = $targetDate->copy()->endOfMonth();
        
        $result = DB::table('repases')
            ->whereBetween('fecha', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->sum('total_neto');
        
        return $result > 0 ? (float) $result : null;
    }

    /**
     * Get monthly expenses from database
     */
    private function getMonthlyExpenses(Carbon $targetDate): ?float
    {
        $monthStart = $targetDate->copy()->startOfMonth();
        $monthEnd = $targetDate->copy()->endOfMonth();
        
        $result = DB::table('gastos')
            ->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->whereBetween('repases.fecha', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->sum('gastos.monto');
        
        return $result > 0 ? (float) $result : null;
    }

    /**
     * Get monthly utilization from database
     */
    private function getMonthlyUtilization(Carbon $targetDate): ?float
    {
        $monthStart = $targetDate->copy()->startOfMonth();
        $monthEnd = $targetDate->copy()->endOfMonth();
        
        $examCount = DB::table('repase_examenes')
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->whereBetween('repases.fecha', [$monthStart->format('Y-m-d'), $monthEnd->format('Y-m-d')])
            ->count();
        
        // Assume maximum capacity of 1000 exams per month per clinic
        $maxCapacity = 1000;
        $utilization = ($examCount / $maxCapacity) * 100;
        
        return min(100, $utilization); // Cap at 100%
    }

    /**
     * Calculate MAPE (Mean Absolute Percentage Error)
     */
    private function calculateMAPE(array $predictions, array $actualValues): float
    {
        $totalError = 0;
        $count = 0;
        
        foreach ($actualValues as $actualValue) {
            if (is_array($actualValue)) {
                foreach ($actualValue as $period => $data) {
                    if (isset($data['predicted']) && isset($data['actual']) && $data['actual'] > 0) {
                        $error = abs($data['predicted'] - $data['actual']) / $data['actual'];
                        $totalError += $error;
                        $count++;
                    }
                }
            }
        }
        
        return $count > 0 ? ($totalError / $count) * 100 : 0;
    }

    /**
     * Calculate RMSE (Root Mean Square Error)
     */
    private function calculateRMSE(array $predictions, array $actualValues): float
    {
        $totalSquaredError = 0;
        $count = 0;
        
        foreach ($actualValues as $actualValue) {
            if (is_array($actualValue)) {
                foreach ($actualValue as $period => $data) {
                    if (isset($data['predicted']) && isset($data['actual'])) {
                        $error = $data['predicted'] - $data['actual'];
                        $totalSquaredError += $error * $error;
                        $count++;
                    }
                }
            }
        }
        
        return $count > 0 ? sqrt($totalSquaredError / $count) : 0;
    }

    /**
     * Generate suggestions for low accuracy scenarios
     */
    private function generateLowAccuracySuggestions(PredictiveConfigInterface $config): void
    {
        Log::channel('predictive')->info('Generating low accuracy suggestions');
        
        $suggestions = [];
        
        foreach ($this->validationResults as $predictionType => $algorithms) {
            foreach ($algorithms as $algorithm => $accuracy) {
                if (isset($accuracy['accuracy_percentage']) && 
                    $accuracy['accuracy_percentage'] !== null && 
                    $accuracy['accuracy_percentage'] < self::LOW_ACCURACY_THRESHOLD) {
                    
                    $suggestions[] = $this->generateSuggestionForLowAccuracy(
                        $predictionType, 
                        $algorithm, 
                        $accuracy
                    );
                }
            }
        }
        
        if (!empty($suggestions)) {
            Log::channel('predictive')->warning('Low accuracy detected - suggestions generated', [
                'suggestions_count' => count($suggestions),
                'suggestions' => $suggestions
            ]);
            
            // Send notification to administrators
            $this->sendLowAccuracyNotification($suggestions);
        }
        
        // Store suggestions in validation results
        $this->validationResults['suggestions'] = $suggestions;
    }

    /**
     * Generate specific suggestion for low accuracy
     */
    private function generateSuggestionForLowAccuracy(string $predictionType, string $algorithm, array $accuracy): array
    {
        $suggestions = [];
        
        // Base suggestions based on accuracy level
        if ($accuracy['accuracy_percentage'] < 50) {
            $suggestions[] = "Consider reviewing the algorithm implementation for {$predictionType} - {$algorithm}";
            $suggestions[] = "Check if sufficient historical data is available (minimum 12-24 months)";
            $suggestions[] = "Verify data quality and remove outliers that may affect predictions";
        } elseif ($accuracy['accuracy_percentage'] < 60) {
            $suggestions[] = "Fine-tune algorithm parameters for {$predictionType} - {$algorithm}";
            $suggestions[] = "Consider using ensemble methods combining multiple algorithms";
        } else {
            $suggestions[] = "Minor parameter adjustments may improve accuracy for {$predictionType} - {$algorithm}";
        }
        
        // Algorithm-specific suggestions
        switch ($algorithm) {
            case 'linear_regression':
                $suggestions[] = "Consider using polynomial regression for non-linear trends";
                $suggestions[] = "Check for seasonal patterns that linear regression might miss";
                break;
                
            case 'moving_average':
                $suggestions[] = "Adjust the moving average window size";
                $suggestions[] = "Consider weighted moving average for recent data emphasis";
                break;
                
            case 'seasonal':
                $suggestions[] = "Verify seasonal pattern detection with more historical data";
                $suggestions[] = "Check if business cycles have changed recently";
                break;
        }
        
        return [
            'prediction_type' => $predictionType,
            'algorithm' => $algorithm,
            'current_accuracy' => $accuracy['accuracy_percentage'],
            'threshold' => self::LOW_ACCURACY_THRESHOLD,
            'mape' => $accuracy['mape'],
            'rmse' => $accuracy['rmse'],
            'suggestions' => $suggestions,
            'generated_at' => Carbon::now()->toISOString()
        ];
    }

    /**
     * Generate monthly accuracy reports
     */
    private function generateMonthlyAccuracyReports(): void
    {
        Log::channel('predictive')->info('Generating monthly accuracy reports');
        
        $currentMonth = Carbon::now()->format('Y-m');
        $reports = [];
        
        foreach (self::PREDICTION_TYPES as $predictionType => $algorithms) {
            $reports[$predictionType] = $this->generateMonthlyReportForType($predictionType, $currentMonth);
        }
        
        // Store reports in validation results
        $this->validationResults['monthly_reports'] = $reports;
        
        Log::channel('predictive')->info('Monthly accuracy reports generated', [
            'month' => $currentMonth,
            'reports_count' => count($reports)
        ]);
    }

    /**
     * Generate monthly report for specific prediction type
     */
    private function generateMonthlyReportForType(string $predictionType, string $month): array
    {
        // Get accuracy data for the current month
        $monthlyData = DB::table('prediction_accuracy_log')
            ->where('prediction_type', $predictionType)
            ->whereRaw("strftime('%Y-%m', created_at) = ?", [$month])
            ->get();
        
        $report = [
            'prediction_type' => $predictionType,
            'month' => $month,
            'total_validations' => $monthlyData->count(),
            'algorithms' => []
        ];
        
        // Group by algorithm
        $algorithmGroups = $monthlyData->groupBy('algorithm');
        
        foreach ($algorithmGroups as $algorithm => $records) {
            $mapeValues = $records->pluck('percentage_error')->map(fn($val) => abs($val))->toArray();
            $rmseValues = $records->pluck('absolute_error')->toArray();
            
            $report['algorithms'][$algorithm] = [
                'validations_count' => $records->count(),
                'average_mape' => !empty($mapeValues) ? round(array_sum($mapeValues) / count($mapeValues), 2) : 0,
                'average_rmse' => !empty($rmseValues) ? round(array_sum($rmseValues) / count($rmseValues), 2) : 0,
                'accuracy_percentage' => !empty($mapeValues) ? max(0, 100 - (array_sum($mapeValues) / count($mapeValues))) : 0
            ];
        }
        
        return $report;
    }

    /**
     * Store accuracy metrics in database
     */
    private function storeAccuracyMetrics(): void
    {
        Log::channel('predictive')->info('Storing accuracy metrics in database');
        
        $storedCount = 0;
        
        foreach ($this->validationResults as $predictionType => $algorithms) {
            if (in_array($predictionType, ['suggestions', 'monthly_reports'])) {
                continue; // Skip non-prediction data
            }
            
            foreach ($algorithms as $algorithm => $accuracy) {
                if (isset($accuracy['mape']) && $accuracy['mape'] !== null) {
                    try {
                        DB::table('prediction_accuracy_log')->insert([
                            'prediction_type' => $predictionType,
                            'algorithm' => $algorithm,
                            'prediction_date' => Carbon::now()->subWeek()->format('Y-m-d'),
                            'actual_date' => Carbon::now()->format('Y-m-d'),
                            'predicted_value' => 0, // Placeholder - would need actual prediction values
                            'actual_value' => 0, // Placeholder - would need actual values
                            'absolute_error' => $accuracy['rmse'],
                            'percentage_error' => $accuracy['mape'],
                            'created_at' => Carbon::now()
                        ]);
                        
                        $storedCount++;
                    } catch (Exception $e) {
                        Log::channel('predictive')->warning('Failed to store accuracy metric', [
                            'prediction_type' => $predictionType,
                            'algorithm' => $algorithm,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        Log::channel('predictive')->info('Accuracy metrics stored', [
            'stored_count' => $storedCount
        ]);
    }

    /**
     * Check for significant accuracy drops and send notifications
     */
    private function checkForAccuracyDrops(): void
    {
        Log::channel('predictive')->info('Checking for significant accuracy drops');
        
        $significantDrops = [];
        
        foreach ($this->validationResults as $predictionType => $algorithms) {
            if (in_array($predictionType, ['suggestions', 'monthly_reports'])) {
                continue;
            }
            
            foreach ($algorithms as $algorithm => $accuracy) {
                if (isset($accuracy['accuracy_percentage']) && $accuracy['accuracy_percentage'] !== null) {
                    $previousAccuracy = $this->getPreviousAccuracy($predictionType, $algorithm);
                    
                    if ($previousAccuracy !== null) {
                        $drop = $previousAccuracy - $accuracy['accuracy_percentage'];
                        
                        // Consider a drop of 10% or more as significant
                        if ($drop >= 10) {
                            $significantDrops[] = [
                                'prediction_type' => $predictionType,
                                'algorithm' => $algorithm,
                                'previous_accuracy' => $previousAccuracy,
                                'current_accuracy' => $accuracy['accuracy_percentage'],
                                'drop_percentage' => round($drop, 2)
                            ];
                        }
                    }
                }
            }
        }
        
        if (!empty($significantDrops)) {
            Log::channel('predictive')->warning('Significant accuracy drops detected', [
                'drops_count' => count($significantDrops),
                'drops' => $significantDrops
            ]);
            
            $this->sendAccuracyDropNotification($significantDrops);
        }
    }

    /**
     * Get previous accuracy for comparison
     */
    private function getPreviousAccuracy(string $predictionType, string $algorithm): ?float
    {
        $previousRecord = DB::table('prediction_accuracy_log')
            ->where('prediction_type', $predictionType)
            ->where('algorithm', $algorithm)
            ->where('created_at', '<', Carbon::now()->subWeek())
            ->orderBy('created_at', 'desc')
            ->first();
        
        if ($previousRecord) {
            return max(0, 100 - abs($previousRecord->percentage_error));
        }
        
        return null;
    }

    /**
     * Send notification for low accuracy
     */
    private function sendLowAccuracyNotification(array $suggestions): void
    {
        $message = "Low prediction accuracy detected in the following models:\n\n";
        
        foreach ($suggestions as $suggestion) {
            $message .= "• {$suggestion['prediction_type']} - {$suggestion['algorithm']}: ";
            $message .= "{$suggestion['current_accuracy']}% (threshold: {$suggestion['threshold']}%)\n";
            $message .= "  MAPE: {$suggestion['mape']}%, RMSE: {$suggestion['rmse']}\n";
            $message .= "  Suggestions: " . implode('; ', array_slice($suggestion['suggestions'], 0, 2)) . "\n\n";
        }
        
        $message .= "Please review the prediction models and consider implementing the suggested improvements.";
        
        $this->sendAdministratorNotification('Low Prediction Accuracy Alert', $message);
    }

    /**
     * Send notification for accuracy drops
     */
    private function sendAccuracyDropNotification(array $drops): void
    {
        $message = "Significant accuracy drops detected in the following models:\n\n";
        
        foreach ($drops as $drop) {
            $message .= "• {$drop['prediction_type']} - {$drop['algorithm']}: ";
            $message .= "{$drop['previous_accuracy']}% → {$drop['current_accuracy']}% ";
            $message .= "(drop: {$drop['drop_percentage']}%)\n";
        }
        
        $message .= "\nPlease investigate the cause of these accuracy drops and take corrective action.";
        
        $this->sendAdministratorNotification('Prediction Accuracy Drop Alert', $message);
    }

    /**
     * Handle job failure
     */
    private function handleJobFailure(Exception $exception): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->error('Model accuracy validation job failed', [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
            'execution_time_seconds' => $executionTime,
            'attempt' => $this->attempts(),
            'max_attempts' => $this->tries,
            'validation_results' => $this->validationResults
        ]);
    }

    /**
     * Log successful completion
     */
    private function logSuccessfulCompletion(): void
    {
        $executionTime = Carbon::now()->diffInSeconds($this->startTime);
        
        Log::channel('predictive')->info('Model accuracy validation completed successfully', [
            'start_time' => $this->startTime->toISOString(),
            'end_time' => Carbon::now()->toISOString(),
            'execution_time_seconds' => $executionTime,
            'execution_time_minutes' => round($executionTime / 60, 2),
            'validation_results' => $this->validationResults,
            'next_scheduled_run' => Carbon::now()->addWeek()->toISOString()
        ]);
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
}