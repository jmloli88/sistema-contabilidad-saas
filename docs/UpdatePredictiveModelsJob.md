# UpdatePredictiveModelsJob Documentation

## Overview

The `UpdatePredictiveModelsJob` is a daily scheduled job that automatically updates all predictive models with the latest data. It runs every day at 02:00 AM to ensure predictions remain accurate and up-to-date.

## Features

### Automatic Scheduling
- **Schedule**: Daily at 02:00 AM
- **Timeout**: 10 minutes maximum execution time
- **Overlap Protection**: Prevents multiple instances from running simultaneously
- **Single Server**: Runs on only one server in multi-server setups

### Model Updates
The job recalculates all predictive models:
- **Income Predictions**: Updates all active algorithms (linear regression, moving average, seasonal)
- **Expense Forecasts**: Recalculates expense projections and correlations
- **Capacity Analysis**: Updates utilization metrics and saturation projections
- **Trend Analysis**: Refreshes seasonal patterns (if sufficient data available)

### Backup System
- **Automatic Backup**: Creates backup tables before any updates
- **Rollback Capability**: Can restore from backup if updates fail
- **Cleanup**: Automatically removes backups older than 7 days

### Error Handling
- **Graceful Degradation**: Continues if individual services fail
- **Administrator Notifications**: Sends alerts on critical failures
- **Comprehensive Logging**: Detailed logs for debugging and monitoring
- **Retry Logic**: Attempts up to 3 times with 5-minute backoff

### Performance Monitoring
- **Execution Time Tracking**: Monitors job completion time
- **Performance Alerts**: Warns if execution exceeds 10 minutes
- **Cache Optimization**: Invalidates and warms cache for optimal performance

## Usage

### Automatic Execution
The job runs automatically every day at 02:00 AM. No manual intervention is required.

### Manual Execution
You can manually trigger the job using the Artisan command:

```bash
# Queue the job for background execution
php artisan predictive:update-models

# Run synchronously for immediate feedback
php artisan predictive:update-models --sync

# Force update even if recent update exists
php artisan predictive:update-models --force

# Combine flags
php artisan predictive:update-models --sync --force
```

### Command Options
- `--sync`: Run synchronously instead of queuing (useful for testing)
- `--force`: Force update even if a recent update was completed
- `--help`: Display command help and options

## Monitoring

### Logs
All job activities are logged to the `predictive` log channel:
- **Location**: `storage/logs/predictive.log`
- **Rotation**: Daily rotation with 30-day retention
- **Levels**: Info, Warning, Error, Debug

### Key Log Events
- Job start and completion
- Model recalculation progress
- Backup creation and restoration
- Performance metrics
- Error details and stack traces

### Cache Monitoring
The job updates cache statistics that can be monitored:
- Last successful update timestamp
- Execution time metrics
- Cache hit rates and performance

## Configuration

### Job Settings
The job behavior can be configured through:
- **Active Algorithms**: Configure which prediction algorithms to use
- **Cache Duration**: Set cache TTL for predictions
- **Alert Thresholds**: Configure when to send administrator notifications

### Performance Tuning
- **Timeout**: Maximum 10 minutes (600 seconds)
- **Memory Limit**: Inherits from PHP configuration
- **Queue**: Uses default queue (can be customized)

## Troubleshooting

### Common Issues

#### Job Fails to Start
- Check queue worker is running: `php artisan queue:work`
- Verify database connectivity
- Check disk space availability

#### Execution Timeout
- Review system performance
- Check for large datasets causing slow queries
- Consider optimizing database indexes

#### Service Failures
- Individual service failures are logged but don't stop the job
- Check specific service logs for detailed error information
- Verify data availability and quality

#### Backup Failures
- Ensure sufficient disk space
- Check database permissions
- Verify SQLite database integrity

### Recovery Procedures

#### Restore from Backup
If manual restoration is needed:
```sql
-- List available backups
SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'prediction_%_backup_%';

-- Restore from specific backup (replace timestamp)
DELETE FROM prediction_cache;
INSERT INTO prediction_cache SELECT * FROM prediction_cache_backup_2024_03_10_02_00_00;
```

#### Force Job Restart
```bash
# Clear failed jobs
php artisan queue:flush

# Restart queue worker
php artisan queue:restart

# Manually trigger update
php artisan predictive:update-models --force --sync
```

## Requirements Compliance

This job satisfies the following requirements:
- **1.5**: Update predictions automatically every 24 hours
- **9.1**: Execute updates daily at 02:00 AM
- **9.2**: Incorporate new data in next update cycle
- **9.3**: Log errors and notify administrator on failures
- **9.4**: Maintain backup of previous model before updates
- **9.5**: Complete updates in less than 10 minutes

## Integration

### Service Dependencies
The job requires these services to be properly configured:
- `IncomePredictorInterface`
- `ExpenseForecasterInterface`
- `CapacityAnalyzerInterface`
- `TrendDetectorInterface`
- `CacheServiceInterface`
- `PredictiveConfigInterface`

### Database Tables
The job interacts with:
- `prediction_cache`: Main cache storage
- `prediction_accuracy_log`: Accuracy tracking
- `prediction_configurations`: Configuration settings
- Backup tables (created dynamically)

### Queue Configuration
Ensure your queue configuration supports:
- Job timeouts (10+ minutes)
- Retry mechanisms
- Failed job handling
- Background processing

## Testing

### Unit Tests
Run the comprehensive test suite:
```bash
php artisan test tests/Unit/Jobs/UpdatePredictiveModelsJobTest.php
```

### Integration Tests
Test scheduling and command functionality:
```bash
php artisan test tests/Feature/UpdatePredictiveModelsJobSchedulingTest.php
```

### Manual Testing
Test the job manually:
```bash
# Test command help
php artisan predictive:update-models --help

# Test dry run (queue only)
php artisan predictive:update-models

# Test synchronous execution
php artisan predictive:update-models --sync --force
```

## Security Considerations

- **Administrator Notifications**: Currently logged only (email integration pending)
- **Backup Access**: Backup tables contain sensitive prediction data
- **Log Security**: Ensure log files are properly secured
- **Queue Security**: Protect queue workers from unauthorized access

## Future Enhancements

- Email notification integration
- Slack/Teams notification support
- Advanced performance metrics
- Configurable backup retention
- Distributed execution support
- Real-time monitoring dashboard