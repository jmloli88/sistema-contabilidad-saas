# PredictiveApiController - API Endpoints Documentation

## Overview

The PredictiveApiController provides JSON API endpoints for real-time data updates in the predictive analysis module. These endpoints are designed to support dynamic chart updates and AJAX requests without requiring full page reloads.

## Completed Implementation

### ✅ API Endpoints

1. **Income Projections** - `GET /api/predictivo/ingresos/{months}`
   - Generates income predictions for 3, 6, and 12 months
   - Supports multiple algorithms (linear regression, moving average, seasonal)
   - Returns confidence intervals and trend analysis
   - Includes Chart.js formatted data

2. **Expense Forecasting** - `GET /api/predictivo/gastos/{months}`
   - Forecasts expenses with category breakdown
   - Calculates correlation with income
   - Provides threshold-based alerts
   - Returns Chart.js formatted data

3. **Current Capacity Analysis** - `GET /api/predictivo/capacidad/actual`
   - Analyzes current resource utilization
   - Projects saturation dates
   - Identifies bottlenecks and provides recommendations
   - Returns Chart.js formatted data

4. **Seasonal Trends** - `GET /api/predictivo/tendencias/estacionales`
   - Detects seasonal patterns in historical data
   - Calculates trend strength and direction
   - Identifies peak and low months
   - Returns Chart.js formatted data with confidence intervals

5. **Configuration Updates** - `POST /api/predictivo/configuracion`
   - Updates system configuration parameters
   - Validates parameter ranges
   - Maintains audit trail of changes
   - Returns updated configuration state

### ✅ Features Implemented

- **Authentication & Authorization**: All endpoints require admin authentication
- **Input Validation**: Comprehensive request validation with detailed error messages
- **Error Handling**: Graceful error handling with structured JSON responses
- **Chart.js Integration**: All endpoints return data formatted for Chart.js visualization
- **Caching Support**: Integration with the predictive cache service
- **Historical Data Access**: Proper integration with existing Eloquent models
- **Real-time Updates**: Designed for AJAX requests and dynamic updates

### ✅ Technical Implementation

- **Dependency Injection**: All predictive services properly injected
- **Service Layer Integration**: Uses existing IncomePredictor, ExpenseForecaster, etc.
- **Database Integration**: Leverages existing Repase model scopes
- **Route Configuration**: API routes properly registered with middleware
- **Testing**: Comprehensive test suite with integration tests

### ✅ JSON Response Format

All endpoints follow a consistent JSON structure:

```json
{
  "success": true,
  "data": {
    // Endpoint-specific data
    "chart_data": {
      // Chart.js formatted data
    }
  },
  "meta": {
    "generated_at": "2024-03-10T21:18:19.000000Z",
    "filters_applied": {},
    "additional_metadata": "..."
  }
}
```

### ✅ Error Responses

```json
{
  "success": false,
  "error": "Error message",
  "details": {
    // Validation errors or additional details
  }
}
```

## Routes Configuration

The API routes are configured in `routes/api.php` with the following middleware:
- `auth`: Requires user authentication
- `admin`: Requires administrator role

## Usage Examples

### Get Income Projection
```javascript
fetch('/api/predictivo/ingresos/12?clinica_id=1')
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      updateChart(data.data.chart_data);
    }
  });
```

### Update Configuration
```javascript
fetch('/api/predictivo/configuracion', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
  },
  body: JSON.stringify({
    expense_alert_threshold: 30,
    capacity_alert_threshold: 80
  })
})
.then(response => response.json())
.then(data => console.log(data));
```

## Task Completion Status

✅ **Task 7.5: Create PredictiveApiController for real-time endpoints** - COMPLETED

- ✅ Implement API endpoints for income projections
- ✅ Create expense forecast API with real-time updates  
- ✅ Add current capacity API endpoint
- ✅ Implement seasonal trends API
- ✅ Create configuration update API endpoint
- ✅ Requirements: Real-time data updates
- ✅ Proper JSON responses with error handling
- ✅ Authentication and authorization
- ✅ Integration with existing predictive services
- ✅ Chart.js data formatting
- ✅ Comprehensive testing

The PredictiveApiController is now fully functional and ready for integration with frontend JavaScript components for dynamic chart updates and real-time data visualization.