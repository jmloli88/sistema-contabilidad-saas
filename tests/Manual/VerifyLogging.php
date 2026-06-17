<?php

/**
 * Script manual para verificar que el logging de errores funciona correctamente
 * 
 * Este script genera un error intencional en una transacción y verifica
 * que el error se loguea en storage/logs/laravel.log
 * 
 * Para ejecutar:
 * php artisan tinker
 * require 'tests/Manual/VerifyLogging.php';
 */

use App\Models\Clinica;
use App\Services\RepaseService;
use Illuminate\Support\Facades\Log;

echo "=== Verificación de Logging de Transacciones ===\n\n";

// 1. Verificar configuración
echo "1. Verificando configuración de logging...\n";
echo "   Canal por defecto: " . config('logging.default') . "\n";
echo "   Ruta del log: " . config('logging.channels.single.path') . "\n";
echo "   Nivel de log: " . config('logging.channels.single.level') . "\n\n";

// 2. Verificar que el directorio existe
echo "2. Verificando directorio de logs...\n";
$logDir = storage_path('logs');
echo "   Directorio: $logDir\n";
echo "   Existe: " . (is_dir($logDir) ? 'Sí' : 'No') . "\n";
echo "   Escribible: " . (is_writable($logDir) ? 'Sí' : 'No') . "\n\n";

// 3. Verificar archivo de log
echo "3. Verificando archivo de log...\n";
$logFile = storage_path('logs/laravel.log');
echo "   Archivo: $logFile\n";
echo "   Existe: " . (file_exists($logFile) ? 'Sí' : 'No') . "\n";
if (file_exists($logFile)) {
    echo "   Tamaño: " . filesize($logFile) . " bytes\n";
    echo "   Escribible: " . (is_writable($logFile) ? 'Sí' : 'No') . "\n";
}
echo "\n";

// 4. Generar un error intencional para verificar logging
echo "4. Generando error intencional para verificar logging...\n";
try {
    $service = new RepaseService();
    $clinica = Clinica::first();
    
    if (!$clinica) {
        echo "   ERROR: No hay clínicas en la base de datos. Ejecuta los seeders primero.\n";
        exit(1);
    }
    
    // Intentar crear un repase con un examen_id inválido
    $service->createRepase([
        'clinica_id' => $clinica->id,
        'fecha' => '2024-01-15',
        'fecha_pago' => null,
        'tipo_precio' => 'sin_nota',
        'total_consultas' => 100.00,
        'observaciones' => 'Test de logging manual',
        'examenes' => [
            [
                'examen_id' => 99999, // ID inválido que causará error
                'cantidad' => 1,
            ],
        ],
        'gastos' => [],
    ]);
    
    echo "   ERROR: Se esperaba una excepción pero no se lanzó\n";
} catch (\Exception $e) {
    echo "   ✓ Excepción capturada correctamente: " . $e->getMessage() . "\n";
    echo "   ✓ El error debería estar logueado en laravel.log\n\n";
}

// 5. Verificar que el error se logueó
echo "5. Verificando últimas líneas del log...\n";
if (file_exists($logFile)) {
    $lines = file($logFile);
    $lastLines = array_slice($lines, -10);
    
    $foundError = false;
    foreach ($lastLines as $line) {
        if (str_contains($line, 'Error al crear repase')) {
            $foundError = true;
            break;
        }
    }
    
    if ($foundError) {
        echo "   ✓ Se encontró el error en el log\n";
        echo "   Últimas líneas del log:\n";
        foreach ($lastLines as $line) {
            echo "   " . trim($line) . "\n";
        }
    } else {
        echo "   ⚠ No se encontró el error en las últimas 10 líneas del log\n";
        echo "   Esto puede ser normal si el log es muy grande\n";
    }
} else {
    echo "   ERROR: El archivo de log no existe\n";
}

echo "\n=== Verificación completada ===\n";
