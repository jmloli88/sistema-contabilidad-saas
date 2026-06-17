# ValidateModelAccuracyJob Documentation

## Overview

The `ValidateModelAccuracyJob` is a weekly scheduled job that validates the accuracy of predictive models by comparing past predictions with actual values from the database. It calculates accuracy metrics, generates suggestions for improvement, and creates monthly reports.

## Purpose

This job implements the accuracy validation requirements (8.1-8.5) of the predictive analytics module:

- **Requirement 8.1**: Calculate prediction accuracy comparing past predictions with actual values
- **Requirement 8.2**: Show MAPE and RMSE metrics
- **Requirement 8.3**: Suggest parameter adjustments when accuracy < 70%
- **Requirement 8.4**: Generate monthly accuracy reports for each prediction type
- **Requirement 8.5**: Maintain historical accuracy metrics for trend analysis

## Scheduling

The job is scheduled to run **weekly on Sundays at 03:00 AM** to avoid conflicts with the daily model update job.

```php
// In routes/console.php
Schedule::job(new ValidateModelAccuracyJob())
    ->weeklyOn(0, '03:00') // Sunday at 03:00 AM
    ->name('validate-model-accuracy')
    ->description('Validate prediction accuracy and generate reports')
    ->withoutOverlapping(1800) // Prevent overlapping runs, timeout after 30 minutes
    ->onOneServer(); // Run on only one server in multi-server setup
```

## Job Configuration

- **Timeout**: 30 minutes (1800 seconds)
- **Retry Attempts**: 3
- **Backoff**: 10 minutes between retries
- **Accuracy Threshold**: 70% (configurable)

## Functionality

### 1. Accuracy Validation Process

The job validates accuracy for four prediction types:

1. **Income Predictions** (algorithms: linear_regression, moving_average, seasonal)
2. **Expense Forecasts** (algorithm: forecast)
3. **Capacity Analysis** (algorithm: utilization)
4. **Trend Detection** (algorithm: seasonal)

### 2. Metrics Calculation

For each prediction type and algorithm, the job calculates:

- **MAPE (Mean Absolute Percentage Error)**: `(|predicted - actual| / actual) * 100`
- **RMSE (Root Mean Square Error)**: `sqrt(sum((predicted - actual)²) / count)`
- **Accuracy Percentage**: `max(0, 100 - MAPE)`

### 3. Low Accuracy Detection

When accuracy falls below 70%, the job generates specific suggestions:

#### General Suggestions (accuracy < 50%)
- Review algorithm implementation
- Check historical data sufficiency (12-24 months minimum)
- Verify data quality and remove outliers

#### Moderate Issues (50-60% accuracy)
- Fine-tune algorithm parameters
- Consider ensemble methods

#### Minor Issues (60-70% accuracy)
- Minor parameter adjustments

#### Algorithm-Specific Suggestions

**Linear Regression:**
- Consider polynomial regression for non-linear trends
- Check for seasonal patterns

**Moving Average:**
- Adjust window size
- Consider weighted moving average

**Seasonal Analysis:**
- Verify seasonal pattern detection
- Check if business cycles have changed

### 4. Monthly Reporting

The job generates comprehensive monthly reports including:

- Total validations performed
- Average MAPE and RMSE by algorithm
- Accuracy percentage trends
- Algorithm performance comparisons

### 5. Accuracy Drop Detection

The job monitors for significant accuracy drops (≥10%) compared to previous validations and sends alerts to administrators.

## Database Interactions

### Tables Used

- **`prediction_cache`**: Source of cached predictions for comparison
- **`prediction_accuracy_log`**: Storage for accuracy metrics and historical data
- **`repases`**: Actual income data for validation
- **`gastos`**: Actual expense data for validation
- **`repase_examenes`**: Actual capacity utilization data

### Data Storage

Each validation stores:
```sql
INSERT INTO prediction_accuracy_log (
    prediction_type,
    algorithm,
    prediction_date,
    actual_date,
    predicted_value,
    actual_value,
    absolute_error,
    percentage_error,
    created_at
)
```

## Error Handling

The job implements comprehensive error handling:

1. **Graceful Degradation**: Continues processing other prediction types if one fails
2. **Detailed Logging**: All operations logged to `predictive` channel
3. **Administrator Notifications**: Automatic alerts for failures and accuracy issues
4. **Retry Logic**: 3 attempts with 10-minute backoff

## Notifications

### Low Accuracy Alert
Sent when any prediction type has accuracy < 70%:
- Lists affected models with current accuracy
- Includes MAPE and RMSE values
- Provides top suggestions for improvement

### Accuracy Drop Alert
Sent when accuracy drops ≥10% from previous validation:
- Shows before/after accuracy percentages
- Highlights drop magnitude
- Requests investigation

### Job Failure Alert
Sent when job fails permanently:
- Error details and stack trace
- Execution time information
- Backup availability status

## Performance Considerations

- **Execution Time**: Designed to complete within 30 minutes
- **Data Volume**: Efficiently processes up to 30 days of cached predictions
- **Memory Usage**: Processes data in batches to avoid memory issues
- **Database Load**: Uses indexed queries for optimal performance

## Monitoring and Logging

All operations are logged to the `predictive` channel with appropriate log levels:

- **INFO**: Normal operations, completion status
- **DEBUG**: Detailed calculation results
- **WARNING**: Non-critical issues, low accuracy detection
- **ERROR**: Failures and exceptions

## Manual Execution

The job can be manually triggered for testing or immediate validation:

```bash
# Dispatch the job immediately
php artisan tinker
>>> App\Jobs\ValidateModelAccuracyJob::dispatch()

# Or run synchronously for testing
>>> (new App\Jobs\ValidateModelAccuracyJob())->handle(
...     app(App\Contracts\Predictive\IncomePredictorInterface::class),
...     app(App\Contracts\Predictive\ExpenseForecasterInterface::class),
...     app(App\Contracts\Predictive\CapacityAnalyzerInterface::class),
...     app(App\Contracts\Predictive\TrendDetectorInterface::class),
...     app(App\Contracts\PredictiveConfigInterface::class)
... )
```

## Integration with Other Components

The job integrates with:

- **UpdatePredictiveModelsJob**: Uses predictions generated by the daily update job
- **Predictive Services**: Leverages existing service interfaces for consistency
- **Configuration System**: Respects accuracy thresholds and algorithm settings
- **Notification System**: Sends alerts through the existing notification infrastructure

## Future Enhancements

Potential improvements for future versions:

1. **Machine Learning Integration**: Use ML models to predict accuracy trends
2. **Advanced Metrics**: Add additional accuracy metrics (MAE, R²)
3. **Automated Tuning**: Automatically adjust parameters based on accuracy feedback
4. **Visualization**: Generate accuracy trend charts and dashboards
5. **Comparative Analysis**: Compare accuracy across different time periods and conditions