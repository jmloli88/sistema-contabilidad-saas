<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Contracts\PredictiveConfigInterface;

class DemoConfigurationSystem extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'predictive:demo-config';

    /**
     * The console command description.
     */
    protected $description = 'Demonstrate the predictive configuration management system';

    /**
     * Execute the console command.
     */
    public function handle(PredictiveConfigInterface $config): int
    {
        $this->info('=== Predictive Configuration Management System Demo ===');
        $this->newLine();

        // Show current configuration
        $this->info('1. Current Configuration Values:');
        $all = $config->getAll();
        foreach ($all as $key => $value) {
            $displayValue = is_array($value) ? json_encode($value) : $value;
            $this->line("   {$key}: {$displayValue}");
        }
        $this->newLine();

        // Show available parameters
        $this->info('2. Available Parameters with Metadata:');
        $parameters = $config->getAvailableParameters();
        foreach ($parameters as $key => $meta) {
            $this->line("   {$key}:");
            $this->line("     - Default: " . (is_array($meta['default']) ? json_encode($meta['default']) : $meta['default']));
            $this->line("     - Type: {$meta['type']}");
            $this->line("     - Validation: {$meta['validation']}");
            $this->line("     - Description: {$meta['description']}");
        }
        $this->newLine();

        // Demonstrate configuration change
        $this->info('3. Changing Configuration (expense_alert_threshold from 25 to 30):');
        $oldValue = $config->get('expense_alert_threshold');
        $this->line("   Current value: {$oldValue}");
        
        $config->set('expense_alert_threshold', 30, 1);
        $newValue = $config->get('expense_alert_threshold');
        $this->line("   New value: {$newValue}");
        $this->newLine();

        // Show audit trail
        $this->info('4. Configuration Audit Trail:');
        $auditTrail = $config->getAuditTrail('expense_alert_threshold', 5);
        foreach ($auditTrail as $entry) {
            $this->line("   {$entry->created_at}: {$entry->config_key} changed from {$entry->old_value} to {$entry->new_value} (User: {$entry->user_id})");
        }
        $this->newLine();

        // Demonstrate override functionality
        $this->info('5. Temporary Override Functionality:');
        $this->line("   Current value: " . $config->get('expense_alert_threshold'));
        $this->line("   Setting temporary override to 35...");
        
        $config->override('expense_alert_threshold', 35);
        $this->line("   Value with override: " . $config->getWithOverride('expense_alert_threshold'));
        $this->line("   Original value (without override): " . $config->get('expense_alert_threshold'));
        
        $config->clearOverrides();
        $this->line("   After clearing overrides: " . $config->getWithOverride('expense_alert_threshold'));
        $this->newLine();

        // Demonstrate validation
        $this->info('6. Configuration Validation:');
        try {
            $config->set('expense_alert_threshold', 100); // Should fail (max is 50)
        } catch (\InvalidArgumentException $e) {
            $this->error("   Validation error (expected): " . $e->getMessage());
        }
        
        try {
            $config->set('unknown_parameter', 'value'); // Should fail
        } catch (\InvalidArgumentException $e) {
            $this->error("   Unknown parameter error (expected): " . $e->getMessage());
        }
        $this->newLine();

        // Reset to original value
        $this->info('7. Resetting to Original Value:');
        $config->set('expense_alert_threshold', $oldValue, 1);
        $this->line("   Reset expense_alert_threshold back to: " . $config->get('expense_alert_threshold'));
        $this->newLine();

        $this->info('=== Demo Complete ===');
        $this->info('The configuration management system provides:');
        $this->line('✓ Parameter validation with acceptable ranges');
        $this->line('✓ Configuration override functionality');
        $this->line('✓ Complete audit trail with user tracking');
        $this->line('✓ Cache invalidation on changes');
        $this->line('✓ Integration with existing predictive services');

        return Command::SUCCESS;
    }
}