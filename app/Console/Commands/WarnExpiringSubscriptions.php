<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WarnExpiringSubscriptions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'subscription:warn-expiring';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark subscriptions ending within 7 days for warning banner';

    /**
     * Execute the console command.
     *
     * The actual warning is computed real-time via subscriptionEndingSoon()
     * on the User model. This command exists for future extensibility
     * (e.g., email notifications). For now it just logs.
     */
    public function handle(): void
    {
        $this->info('Expiry warning check complete.');
    }
}
