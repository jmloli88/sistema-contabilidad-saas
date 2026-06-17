<?php

namespace Tests\Feature\Logging;

use App\Models\Clinica;
use App\Models\Examen;
use App\Models\User;
use App\Services\RepaseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Test para verificar que los errores de transacciones se loguean correctamente
 * 
 * Feature: contabilidad-medica
 * Validates: Requirements 19.5
 */
class TransactionLoggingTest extends TestCase
{
    use RefreshDatabase;

    protected RepaseService $repaseService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repaseService = new RepaseService();
        
        // Seed de exámenes necesarios para las pruebas
        $this->seed(\Database\Seeders\ExamenSeeder::class);
    }

    /**
     * Test: Verificar que errores en createRepase se loguean correctamente
     */
    public function test_create_repase_logs_errors_on_failure(): void
    {
        // Arrange: Crear usuario y clínica
        $user = User::factory()->create();
        $clinica = Clinica::factory()->create();
        
        // Capturar logs
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) {
                return str_contains($message, 'Error al crear repase') &&
                       isset($context['data']) &&
                       isset($context['exception']);
            });

        // Act & Assert: Intentar crear repase con examen_id inválido
        try {
            $this->repaseService->createRepase([
                'clinica_id' => $clinica->id,
                'fecha' => '2024-01-15',
                'fecha_pago' => null,
                'tipo_precio' => 'sin_nota',
                'total_consultas' => 100.00,
                'observaciones' => 'Test de logging',
                'examenes' => [
                    [
                        'examen_id' => 99999, // ID inválido
                        'cantidad' => 2,
                    ],
                ],
                'gastos' => [],
            ]);
            
            $this->fail('Se esperaba una excepción');
        } catch (\Exception $e) {
            // Se espera que falle y que se loguee el error
            $this->assertTrue(true);
        }
    }

    /**
     * Test: Verificar que errores en updateRepase se loguean correctamente
     */
    public function test_update_repase_logs_errors_on_failure(): void
    {
        // Arrange: Crear usuario, clínica y repase válido
        $user = User::factory()->create();
        $clinica = Clinica::factory()->create();
        $examen = Examen::first();
        
        $repase = $this->repaseService->createRepase([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'fecha_pago' => null,
            'tipo_precio' => 'sin_nota',
            'total_consultas' => 100.00,
            'observaciones' => 'Test inicial',
            'examenes' => [
                [
                    'examen_id' => $examen->id,
                    'cantidad' => 1,
                ],
            ],
            'gastos' => [],
        ]);
        
        // Capturar logs
        Log::shouldReceive('error')
            ->once()
            ->withArgs(function ($message, $context) use ($repase) {
                return str_contains($message, 'Error al actualizar repase') &&
                       isset($context['repase_id']) &&
                       $context['repase_id'] === $repase->id &&
                       isset($context['data']) &&
                       isset($context['exception']);
            });

        // Act & Assert: Intentar actualizar con examen_id inválido
        try {
            $this->repaseService->updateRepase($repase, [
                'clinica_id' => $clinica->id,
                'fecha' => '2024-01-16',
                'fecha_pago' => null,
                'tipo_precio' => 'sin_nota',
                'total_consultas' => 150.00,
                'observaciones' => 'Test actualización',
                'examenes' => [
                    [
                        'examen_id' => 99999, // ID inválido
                        'cantidad' => 2,
                    ],
                ],
                'gastos' => [],
            ]);
            
            $this->fail('Se esperaba una excepción');
        } catch (\Exception $e) {
            // Se espera que falle y que se loguee el error
            $this->assertTrue(true);
        }
    }

    /**
     * Test: Verificar que el archivo de log existe y es escribible
     */
    public function test_log_file_exists_and_is_writable(): void
    {
        $logPath = storage_path('logs/laravel.log');
        
        // Verificar que el directorio existe
        $this->assertTrue(
            is_dir(storage_path('logs')),
            'El directorio storage/logs debe existir'
        );
        
        // Verificar que el directorio es escribible
        $this->assertTrue(
            is_writable(storage_path('logs')),
            'El directorio storage/logs debe ser escribible'
        );
        
        // Si el archivo existe, verificar que es escribible
        if (file_exists($logPath)) {
            $this->assertTrue(
                is_writable($logPath),
                'El archivo laravel.log debe ser escribible'
            );
        }
    }

    /**
     * Test: Verificar configuración de logging en config
     */
    public function test_logging_configuration_is_correct(): void
    {
        // Verificar que el canal por defecto está configurado
        $defaultChannel = config('logging.default');
        $this->assertNotEmpty($defaultChannel, 'El canal de logging por defecto debe estar configurado');
        
        // Verificar que el canal 'single' apunta a laravel.log
        $singleChannelPath = config('logging.channels.single.path');
        $expectedPath = storage_path('logs/laravel.log');
        $this->assertEquals(
            $expectedPath,
            $singleChannelPath,
            'El canal single debe apuntar a storage/logs/laravel.log'
        );
        
        // Verificar que el nivel de log está configurado
        $logLevel = config('logging.channels.single.level');
        $this->assertNotEmpty($logLevel, 'El nivel de log debe estar configurado');
    }

    /**
     * Test: Verificar que operaciones exitosas no generan logs de error
     */
    public function test_successful_operations_do_not_log_errors(): void
    {
        // Arrange: Crear usuario y clínica
        $user = User::factory()->create();
        $clinica = Clinica::factory()->create();
        $examen = Examen::first();
        
        // No debe haber logs de error
        Log::shouldReceive('error')->never();

        // Act: Crear repase válido
        $repase = $this->repaseService->createRepase([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'fecha_pago' => null,
            'tipo_precio' => 'sin_nota',
            'total_consultas' => 100.00,
            'observaciones' => 'Test exitoso',
            'examenes' => [
                [
                    'examen_id' => $examen->id,
                    'cantidad' => 2,
                ],
            ],
            'gastos' => [
                [
                    'tipo' => 'gasolina',
                    'descripcion' => null,
                    'monto' => 50.00,
                ],
            ],
        ]);

        // Assert: Verificar que el repase se creó correctamente
        $this->assertNotNull($repase);
        $this->assertDatabaseHas('repases', [
            'id' => $repase->id,
            'clinica_id' => $clinica->id,
        ]);
    }
}
