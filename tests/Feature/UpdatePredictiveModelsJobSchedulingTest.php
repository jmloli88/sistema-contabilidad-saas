<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UpdatePredictiveModelsJobSchedulingTest extends TestCase
{
    use RefreshDatabase;

    public function test_predictive_models_update_job_is_scheduled()
    {
        // Simply test that the console routes file contains the scheduling
        $consoleRoutes = file_get_contents(base_path('routes/console.php'));
        
        $this->assertStringContainsString('UpdatePredictiveModelsJob', $consoleRoutes);
        $this->assertStringContainsString('dailyAt(\'02:00\')', $consoleRoutes);
        $this->assertStringContainsString('update-predictive-models', $consoleRoutes);
    }

    public function test_manual_command_exists()
    {
        // Test that the manual command is registered
        $this->artisan('predictive:update-models --help')
            ->assertExitCode(0);
    }
}