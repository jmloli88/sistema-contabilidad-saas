# Predictive Module Routing and Middleware Configuration

## Overview

This document describes the routing and middleware configuration implemented for the Predictive Analysis module as part of Task 7.7.

## Web Routes

All web routes are configured in `routes/web.php` under the predictive module section:

```php
// Módulo de Análisis Predictivo
Route::prefix('predictivo')->name('predictivo.')->group(function () {
    Route::get('/', [\App\Http\Controllers\PredictiveController::class, 'dashboard'])->name('dashboard');
    Route::get('/ingresos', [\App\Http\Controllers\PredictiveController::class, 'incomeProjection'])->name('ingresos');
    Route::get('/gastos', [\App\Http\Controllers\PredictiveController::class, 'expenseForecast'])->name('gastos');
    Route::get('/capacidad', [\App\Http\Controllers\PredictiveController::class, 'capacityAnalysis'])->name('capacidad');
    Route::get('/tendencias', [\App\Http\Controllers\PredictiveController::class, 'trendAnalysis'])->name('tendencias');
});
```

### Web Route Middleware

All web routes are protected by:
- `auth` - Requires user authentication
- `verified` - Requires email verification
- `admin` - Requires administrator role (`administrador`)

## API Routes

API routes are configured in `routes/api.php` with rate limiting:

```php
// API Routes para Módulo de Análisis Predictivo
Route::prefix('predictivo')->middleware(['auth', 'admin', 'throttle:60,1'])->name('api.predictivo.')->group(function () {
    Route::get('/ingresos/{months}', [PredictiveApiController::class, 'getIncomeProjection'])->name('ingresos');
    Route::get('/gastos/{months}', [PredictiveApiController::class, 'getExpenseForecast'])->name('gastos');
    Route::get('/capacidad/actual', [PredictiveApiController::class, 'getCurrentCapacity'])->name('capacidad');
    Route::get('/tendencias/estacionales', [PredictiveApiController::class, 'getSeasonalTrends'])->name('tendencias');
    Route::post('/configuracion', [PredictiveApiController::class, 'updateConfiguration'])->name('configuracion');
});
```

### API Route Middleware

All API routes are protected by:
- `auth` - Requires user authentication
- `admin` - Requires administrator role (`administrador`)
- `throttle:60,1` - Rate limiting: 60 requests per minute per user

## Middleware Configuration

### Admin Middleware

The admin middleware is configured in `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->alias([
        'admin' => \App\Http\Middleware\EnsureUserIsAdmin::class,
    ]);
})
```

The `EnsureUserIsAdmin` middleware checks that:
1. User is authenticated
2. User has role `administrador`
3. Returns 403 error if conditions are not met

### Rate Limiting

Rate limiting is implemented using Laravel's built-in `throttle` middleware:
- **Limit**: 60 requests per minute per user
- **Scope**: Applied to all API endpoints
- **Response**: Returns 429 status code when limit exceeded

## Route Names

### Web Routes
- `predictivo.dashboard` - Main dashboard
- `predictivo.ingresos` - Income projections
- `predictivo.gastos` - Expense forecasts
- `predictivo.capacidad` - Capacity analysis
- `predictivo.tendencias` - Trend analysis

### API Routes
- `api.predictivo.ingresos` - Income projection API
- `api.predictivo.gastos` - Expense forecast API
- `api.predictivo.capacidad` - Capacity analysis API
- `api.predictivo.tendencias` - Seasonal trends API
- `api.predictivo.configuracion` - Configuration update API

## Security Features

1. **Authentication Required**: All routes require user login
2. **Role-Based Access**: Only administrators can access predictive features
3. **Rate Limiting**: API endpoints are protected against abuse
4. **CSRF Protection**: Web routes include CSRF token validation
5. **Input Validation**: All endpoints validate input parameters

## Testing

Comprehensive routing tests are implemented in `tests/Feature/PredictiveRoutingTest.php` covering:
- Authentication requirements
- Authorization (admin role) requirements
- Route accessibility for authorized users
- Rate limiting functionality
- Configuration endpoint security

## Requirements Compliance

This implementation satisfies the following requirements:
- **Requirement 10.2**: Laravel 11 conventions for routes and middleware
- **Requirement 10.4**: Integration with existing authentication and authorization system

## Usage Examples

### Accessing Web Routes
```php
// Generate URL for dashboard
route('predictivo.dashboard')

// Generate URL for income projections
route('predictivo.ingresos')
```

### Calling API Endpoints
```javascript
// Get income projection for 6 months
fetch('/api/predictivo/ingresos/6', {
    headers: {
        'Authorization': 'Bearer ' + token,
        'Accept': 'application/json'
    }
})

// Update configuration
fetch('/api/predictivo/configuracion', {
    method: 'POST',
    headers: {
        'Authorization': 'Bearer ' + token,
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        expense_alert_threshold: 30
    })
})
```