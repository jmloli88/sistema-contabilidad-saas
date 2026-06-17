<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\TrendDetector;
use App\DTOs\Predictive\SeasonalAnalysis;
use App\DTOs\Predictive\ComparisonResult;
use Carbon\Carbon;

class TrendDetectorPropertyTest extends TestCase
{
    private TrendDetector $detector;

    protected function setUp(): void
    {
        parent::setUp();
        $this->detector = new TrendDetector();
    }

    /**
     * Property 5: Seasonal Pattern Calculation
     * **Validates: Requirements 2.2**
     * 
     * For any detected seasonal pattern, the percentage variation relative to 
     * annual average should be calculated correctly using statistical formulas
     */
    public function test_seasonal_pattern_calculation_is_mathematically_correct()
    {
        // Generate test data with known seasonal patterns
        $testCases = [
            $this->generateKnownSeasonalData(),
            $this->generateUniformData(),
            $this->generateHighVariabilityData()
        ];

        foreach ($testCases as $testData) {
            $analysis = $this->detector->detectSeasonalPatterns($testData['data'], 24);
            
            $this->assertInstanceOf(SeasonalAnalysis::class, $analysis);
            
            // Verify monthly patterns are calculated
            $patterns = $analysis->monthlyPatterns;
            $this->assertCount(12, $patterns, "Should have patterns for all 12 months");
            
            // Verify percentage variations are mathematically correct
            $overallAverage = $this->calculateOverallAverage($testData['data']);
            
            foreach ($patterns as $month => $pattern) {
                $this->assertArrayHasKey('variation_percent', $pattern);
                $this->assertIsFloat($pattern['variation_percent']);
                
                // Verify calculation: ((month_avg - overall_avg) / overall_avg) * 100
                if ($pattern['data_points'] > 0 && $overallAverage > 0) {
                    $expectedVariation = (($pattern['average_value'] - $overallAverage) / $overallAverage) * 100;
                    $this->assertEqualsWithDelta(
                        $expectedVariation, 
                        $pattern['variation_percent'], 
                        0.01,
                        "Variation calculation for month {$month} should be mathematically correct"
                    );
                }
            }
            
            // Verify seasonal strength is calculated
            $this->assertIsFloat($analysis->seasonalStrength);
            $this->assertGreaterThanOrEqual(0, $analysis->seasonalStrength);
        }
    }

    /**
     * Property 6: Year-over-Year Comparison
     * **Validates: Requirements 2.3**
     * 
     * For any trend analysis with multiple years of data, year-over-year 
     * comparisons should show calculated deviations between periods
     */
    public function test_year_over_year_comparison_shows_correct_deviations()
    {
        $testCases = [
            [
                'current' => $this->generateYearData(2024, 50000, 0.1), // 10% growth
                'previous' => $this->generateYearData(2023, 50000, 0.0)
            ],
            [
                'current' => $this->generateYearData(2024, 40000, -0.05), // 5% decline
                'previous' => $this->generateYearData(2023, 50000, 0.0)
            ],
            [
                'current' => $this->generateYearData(2024, 50000, 0.0), // No change
                'previous' => $this->generateYearData(2023, 50000, 0.0)
            ]
        ];

        foreach ($testCases as $testCase) {
            $comparison = $this->detector->compareYearOverYear(
                $testCase['current'], 
                $testCase['previous']
            );
            
            $this->assertInstanceOf(ComparisonResult::class, $comparison);
            
            // Verify deviations are calculated for each month
            $deviations = $comparison->deviations;
            $this->assertGreaterThan(0, count($deviations));
            
            foreach ($deviations as $deviation) {
                $this->assertArrayHasKey('deviation_percent', $deviation);
                $this->assertIsFloat($deviation['deviation_percent']);
                
                // Verify mathematical correctness of deviation calculation
                if ($deviation['previous'] > 0) {
                    $expectedDeviation = (($deviation['current'] - $deviation['previous']) / $deviation['previous']) * 100;
                    $this->assertEqualsWithDelta(
                        $expectedDeviation,
                        $deviation['deviation_percent'],
                        0.01,
                        "Deviation calculation should be mathematically correct"
                    );
                }
            }
            
            // Verify overall change is calculated
            $this->assertIsFloat($comparison->overallChange);
            
            // Verify significant changes are identified (>15% variation)
            foreach ($comparison->significantChanges as $change) {
                $this->assertGreaterThan(15, abs($change['deviation_percent']));
                $this->assertContains($change['type'], ['increase', 'decrease']);
            }
        }
    }

    /**
     * Property 7: Confidence Interval Generation
     * **Validates: Requirements 2.4**
     * 
     * For any trend graph, 95% confidence intervals should be calculated 
     * and included in the visualization data
     */
    public function test_confidence_intervals_are_calculated_correctly()
    {
        $testData = $this->generateStatisticalTestData();
        
        $analysis = $this->detector->detectSeasonalPatterns($testData, 24);
        
        $confidenceIntervals = $analysis->confidenceIntervals;
        $this->assertIsArray($confidenceIntervals);
        
        foreach ($confidenceIntervals as $month => $interval) {
            // Verify confidence interval structure
            $this->assertArrayHasKey('lower_bound', $interval);
            $this->assertArrayHasKey('upper_bound', $interval);
            $this->assertArrayHasKey('confidence_level', $interval);
            
            // Verify confidence level is 95%
            $this->assertEquals(95, $interval['confidence_level']);
            
            // Verify bounds are numeric and logical
            $this->assertIsFloat($interval['lower_bound']);
            $this->assertIsFloat($interval['upper_bound']);
            $this->assertGreaterThanOrEqual(0, $interval['lower_bound']);
            $this->assertGreaterThanOrEqual($interval['lower_bound'], $interval['upper_bound']);
            
            // Verify interval width is reasonable (not zero for data with variance)
            if ($this->hasVarianceInMonth($testData, $month)) {
                $this->assertGreaterThan(0, $interval['upper_bound'] - $interval['lower_bound']);
            }
        }
    }

    private function generateKnownSeasonalData(): array
    {
        $data = [];
        $baseValue = 50000;
        
        // Generate 24 months with known seasonal pattern
        for ($i = 0; $i < 24; $i++) {
            $month = ($i % 12) + 1;
            $seasonal = sin(($month / 12) * 2 * pi()) * 0.2; // 20% seasonal variation
            $value = $baseValue * (1 + $seasonal);
            
            $data[] = [
                'month' => Carbon::now()->subMonths(24 - $i - 1)->format('Y-m'),
                'total_ingresos' => $value,
                'value' => $value
            ];
        }
        
        return ['data' => $data];
    }

    private function generateUniformData(): array
    {
        $data = [];
        $baseValue = 50000;
        
        // Generate 24 months with uniform data (no seasonality)
        for ($i = 0; $i < 24; $i++) {
            $data[] = [
                'month' => Carbon::now()->subMonths(24 - $i - 1)->format('Y-m'),
                'total_ingresos' => $baseValue,
                'value' => $baseValue
            ];
        }
        
        return ['data' => $data];
    }

    private function generateHighVariabilityData(): array
    {
        $data = [];
        $baseValue = 50000;
        
        // Generate 24 months with high variability
        for ($i = 0; $i < 24; $i++) {
            $randomFactor = (rand(50, 150) / 100); // 50% to 150% of base
            $value = $baseValue * $randomFactor;
            
            $data[] = [
                'month' => Carbon::now()->subMonths(24 - $i - 1)->format('Y-m'),
                'total_ingresos' => $value,
                'value' => $value
            ];
        }
        
        return ['data' => $data];
    }

    private function generateYearData(int $year, float $baseValue, float $growthRate): array
    {
        $data = [];
        
        for ($month = 1; $month <= 12; $month++) {
            $monthlyGrowth = $growthRate * ($month / 12); // Gradual growth throughout year
            $value = $baseValue * (1 + $monthlyGrowth);
            
            $data[] = [
                'month' => sprintf('%d-%02d', $year, $month),
                'total_ingresos' => $value,
                'value' => $value
            ];
        }
        
        return $data;
    }

    private function generateStatisticalTestData(): array
    {
        $data = [];
        $baseValue = 50000;
        
        // Generate 36 months with controlled variance for statistical testing
        for ($i = 0; $i < 36; $i++) {
            $month = ($i % 12) + 1;
            
            // Add controlled variance based on month
            $variance = ($month % 3 == 0) ? 0.1 : 0.05; // Higher variance every 3rd month
            $randomFactor = 1 + ((rand(-100, 100) / 100) * $variance);
            $value = $baseValue * $randomFactor;
            
            $data[] = [
                'month' => Carbon::now()->subMonths(36 - $i - 1)->format('Y-m'),
                'total_ingresos' => $value,
                'value' => $value
            ];
        }
        
        return $data;
    }

    private function calculateOverallAverage(array $data): float
    {
        $total = 0;
        $count = 0;
        
        foreach ($data as $point) {
            $total += $point['total_ingresos'] ?? $point['value'] ?? 0;
            $count++;
        }
        
        return $count > 0 ? $total / $count : 0;
    }

    private function hasVarianceInMonth(array $data, int $month): bool
    {
        $monthValues = [];
        
        foreach ($data as $point) {
            $pointMonth = (int) date('n', strtotime($point['month'] . '-01'));
            if ($pointMonth == $month) {
                $monthValues[] = $point['total_ingresos'] ?? $point['value'] ?? 0;
            }
        }
        
        if (count($monthValues) < 2) return false;
        
        $mean = array_sum($monthValues) / count($monthValues);
        $variance = array_sum(array_map(fn($x) => pow($x - $mean, 2), $monthValues)) / count($monthValues);
        
        return $variance > 0.01; // Threshold for meaningful variance
    }
}