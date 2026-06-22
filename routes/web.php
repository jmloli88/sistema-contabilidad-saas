<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AiChatController;
use App\Http\Controllers\BillingController;
use App\Http\Controllers\CalendarioController;
use App\Http\Controllers\ClinicaController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\ExamenController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RepaseController;
use App\Http\Controllers\ReporteController;
use App\Http\Controllers\SaaSAdminController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Ruta raíz que redirija a dashboard o login según autenticación
Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

// SaaS Admin Login Routes (separate guard)
Route::middleware('guest:saas')->prefix('saas')->name('saas.')->group(function () {
    Route::get('login', [App\Http\Controllers\Auth\SaasLoginController::class, 'create'])
        ->name('login');
    Route::post('login', [App\Http\Controllers\Auth\SaasLoginController::class, 'store']);
});

// Grupo de rutas con middleware auth + subscription check (tenant-scoped)
Route::middleware(['auth', 'verified', 'subscription', 'empresa.scope'])->group(function () {
    // Dashboard - accesible para todos
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    // Calendario - accesible para todos
    Route::get('/calendario', [CalendarioController::class, 'index'])->name('calendario.index');
    Route::get('/calendario/events', [CalendarioController::class, 'events'])->name('calendario.events');
    
    // Agendas - accesible para todos
    Route::get('/agendas', [\App\Http\Controllers\AgendaController::class, 'index'])->name('agendas.index');
    Route::get('/agendas/events', [\App\Http\Controllers\AgendaController::class, 'events'])->name('agendas.events');
    Route::post('/agendas', [\App\Http\Controllers\AgendaController::class, 'store'])->name('agendas.store');
    Route::put('/agendas/{agenda}', [\App\Http\Controllers\AgendaController::class, 'update'])->name('agendas.update');
    Route::delete('/agendas/{agenda}', [\App\Http\Controllers\AgendaController::class, 'destroy'])->name('agendas.destroy');
    
    // Rutas de solo lectura para usuarios regulares
    Route::get('/clinicas', [ClinicaController::class, 'index'])->name('clinicas.index');
    // NOTA: /clinicas/{clinica} movido al grupo de admin para evitar conflicto con /clinicas/create
    
    Route::get('/repases', [RepaseController::class, 'index'])->name('repases.index');
    // NOTA: /repases/{repase} movido al grupo de admin para evitar conflicto con /repases/create
    
    // Billing page (SaaS subscription) - accesible para expired users
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/pay', [BillingController::class, 'pay'])->name('billing.pay');

    // AI Chat Assistant (PREMIUM only)
    Route::post('/api/chat/ask', [AiChatController::class, 'ask'])->middleware('premium')->name('chat.ask');
    Route::post('/api/chat/stream', [AiChatController::class, 'stream'])->middleware('premium')->name('chat.stream');
    Route::get('/api/chat/history', [AiChatController::class, 'history'])->middleware('premium')->name('chat.history');
    Route::delete('/api/chat/history', [AiChatController::class, 'clearHistory'])->middleware('premium')->name('chat.clear');

    // Profile routes - accesible para todos
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    // Telegram linking (PREMIUM only)
    Route::post('/profile/link-telegram', [ProfileController::class, 'linkTelegram'])->middleware('premium')->name('profile.link-telegram');
});

// Rutas solo para administradores (subscription gated + administrador role, tenant-scoped)
Route::middleware(['auth', 'verified', 'subscription', 'admin', 'empresa.scope'])->group(function () {
    // Ruta de prueba
    Route::get('/test-admin', function() {
        return 'Admin access works! User: ' . auth()->user()->name . ' Role: ' . auth()->user()->role;
    });

    // Google Calendar integration (PREMIUM only)
    Route::prefix('google-calendar')->middleware('premium')->name('google-calendar.')->group(function () {
        Route::get('/redirect', [App\Http\Controllers\GoogleCalendarController::class, 'redirect'])->name('redirect');
        Route::get('/status', [App\Http\Controllers\GoogleCalendarController::class, 'status'])->name('status');
        Route::post('/disconnect', [App\Http\Controllers\GoogleCalendarController::class, 'disconnect'])->name('disconnect');
        Route::post('/sync', [App\Http\Controllers\GoogleCalendarController::class, 'sync'])->name('sync');
    });
    
    // Clínicas - crear, editar, eliminar (y ver detalle)
    Route::get('/clinicas/create', [ClinicaController::class, 'create'])->name('clinicas.create');
    Route::post('/clinicas', [ClinicaController::class, 'store'])->name('clinicas.store');
    Route::get('/clinicas/{clinica}', [ClinicaController::class, 'show'])->name('clinicas.show');
    Route::get('/clinicas/{clinica}/edit', [ClinicaController::class, 'edit'])->name('clinicas.edit');
    Route::put('/clinicas/{clinica}', [ClinicaController::class, 'update'])->name('clinicas.update');
    Route::delete('/clinicas/{clinica}', [ClinicaController::class, 'destroy'])->name('clinicas.destroy');
    
    // Repases - crear, editar, eliminar (y ver detalle)
    Route::get('/repases/create', [RepaseController::class, 'create'])->name('repases.create');
    Route::post('/repases', [RepaseController::class, 'store'])
        ->middleware('prevent.duplicate.submissions')
        ->name('repases.store');
    Route::get('/repases/{repase}', [RepaseController::class, 'show'])->name('repases.show');
    Route::get('/repases/{repase}/edit', [RepaseController::class, 'edit'])->name('repases.edit');
    Route::put('/repases/{repase}', [RepaseController::class, 'update'])
        ->middleware('prevent.duplicate.submissions')
        ->name('repases.update');
    Route::delete('/repases/{repase}', [RepaseController::class, 'destroy'])->name('repases.destroy');
    
    // Gestión de usuarios
    Route::resource('users', UserController::class);
    
    // Gestión de exámenes (precios)
    Route::get('/examenes', [ExamenController::class, 'index'])->name('examenes.index');
    Route::get('/examenes/create', [ExamenController::class, 'create'])->name('examenes.create');
    Route::post('/examenes', [ExamenController::class, 'store'])->name('examenes.store');
    Route::patch('/examenes/{examen}/toggle', [ExamenController::class, 'toggle'])->name('examenes.toggle');
    Route::delete('/examenes/{examen}', [ExamenController::class, 'destroy'])->name('examenes.destroy');
    Route::get('/examenes/{examen}/edit', [ExamenController::class, 'edit'])->name('examenes.edit');
    Route::put('/examenes/{examen}', [ExamenController::class, 'update'])->name('examenes.update');
    
    // Módulo de Reportes Avanzados
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/', [ReporteController::class, 'index'])->name('index');
        Route::get('/rentabilidad-clinica', [ReporteController::class, 'rentabilidadClinica'])->name('rentabilidad-clinica');
        Route::get('/rentabilidad-examen', [ReporteController::class, 'rentabilidadExamen'])->name('rentabilidad-examen');
        Route::get('/productividad', [ReporteController::class, 'productividad'])->name('productividad');
        Route::get('/comparativo', [ReporteController::class, 'comparativo'])->name('comparativo');
        Route::get('/comparacion-clinicas', [ReporteController::class, 'comparacionClinicas'])->name('comparacion-clinicas');
        Route::get('/analisis-consultas', [ReporteController::class, 'analisisConsultas'])->name('analisis-consultas');
        Route::post('/export/excel', [ReporteController::class, 'exportExcel'])->name('export.excel');
        Route::post('/export/pdf', [ReporteController::class, 'exportPdf'])->name('export.pdf');
    });
    
    // Módulo de Análisis Predictivo
    Route::prefix('predictivo')->name('predictivo.')->group(function () {
        Route::get('/', [\App\Http\Controllers\PredictiveController::class, 'dashboard'])->name('dashboard');
        Route::get('/ingresos', [\App\Http\Controllers\PredictiveController::class, 'incomeProjection'])->name('ingresos');
        Route::get('/gastos', [\App\Http\Controllers\PredictiveController::class, 'expenseForecast'])->name('gastos');
        Route::get('/capacidad', [\App\Http\Controllers\PredictiveController::class, 'capacityAnalysis'])->name('capacidad');
        Route::get('/tendencias', [\App\Http\Controllers\PredictiveController::class, 'trendAnalysis'])->name('tendencias');
    });

    // Módulo de Balances
    Route::prefix('balances')->name('balances.')->group(function () {
        Route::get('/', [\App\Http\Controllers\BalanceController::class, 'index'])->name('index');
        Route::get('/mensual', [\App\Http\Controllers\BalanceController::class, 'mensual'])->name('mensual');
        Route::get('/trimestral', [\App\Http\Controllers\BalanceController::class, 'trimestral'])->name('trimestral');
        Route::get('/semestral', [\App\Http\Controllers\BalanceController::class, 'semestral'])->name('semestral');
        Route::get('/anual', [\App\Http\Controllers\BalanceController::class, 'anual'])->name('anual');
        Route::get('/detalle', [\App\Http\Controllers\BalanceController::class, 'detallePeriodo'])->name('detalle');
    });
});

// SaaS admin routes (protected by saas guard)
Route::middleware(['auth:saas'])->prefix('saas/admin')->name('saas.admin.')->group(function () {
    Route::get('/', [SaaSAdminController::class, 'dashboard'])->name('dashboard');
    Route::get('/usuarios', [SaaSAdminController::class, 'index'])->name('index');
    Route::post('/usuarios', [SaaSAdminController::class, 'storeUser'])->name('users.store');
    Route::post('/{user}/extend', [SaaSAdminController::class, 'extend'])->name('extend');
    Route::post('/{user}/cancel', [SaaSAdminController::class, 'cancel'])->name('cancel');
    Route::post('/{user}/expiry', [SaaSAdminController::class, 'setExpiry'])->name('expiry');
    Route::post('/{user}/update', [SaaSAdminController::class, 'updateUser'])->name('update');
    Route::get('/{user}/edit', [SaaSAdminController::class, 'edit'])->name('edit');
    Route::get('/{user}/history', [SaaSAdminController::class, 'history'])->name('history');

    // Empresa management
    Route::get('/empresas', [EmpresaController::class, 'index'])->name('empresas.index');
    Route::get('/empresas/create', [EmpresaController::class, 'create'])->name('empresas.create');
    Route::post('/empresas', [EmpresaController::class, 'store'])->name('empresas.store');
    Route::get('/empresas/{empresa}', [EmpresaController::class, 'show'])->name('empresas.show');
    Route::get('/empresas/{empresa}/edit', [EmpresaController::class, 'edit'])->name('empresas.edit');
    Route::put('/empresas/{empresa}', [EmpresaController::class, 'update'])->name('empresas.update');
    Route::delete('/empresas/{empresa}', [EmpresaController::class, 'destroy'])->name('empresas.destroy');
});

// SaaS logout
Route::post('saas/logout', [App\Http\Controllers\Auth\SaasLoginController::class, 'destroy'])
    ->middleware('auth:saas')
    ->name('saas.logout');

// Webhook de Stripe — sin autenticación, sin CSRF
Route::post('/stripe/webhook', [StripeWebhookController::class, 'handleWebhook'])
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Telegram webhook — sin autenticación, sin CSRF
Route::post('/telegram/webhook', TelegramWebhookController::class)
    ->withoutMiddleware([\App\Http\Middleware\VerifyCsrfToken::class]);

// Google Calendar OAuth callback — Google redirects here without auth session
Route::get('/google-calendar/callback', [\App\Http\Controllers\GoogleCalendarController::class, 'callback']);

// Rutas de autenticación de Breeze
require __DIR__.'/auth.php';
