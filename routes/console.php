<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Jobs\UpdatePredictiveModelsJob;
use App\Jobs\ValidateModelAccuracyJob;
use App\Console\Commands\WarnExpiringSubscriptions;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Schedule predictive models update job daily at 02:00 AM
Schedule::job(new UpdatePredictiveModelsJob())
    ->dailyAt('02:00')
    ->name('update-predictive-models')
    ->description('Update predictive models with latest data')
    ->withoutOverlapping(600) // Prevent overlapping runs, timeout after 10 minutes
    ->onOneServer(); // Run on only one server in multi-server setup

// Schedule model accuracy validation job weekly on Sundays at 03:00 AM
Schedule::job(new ValidateModelAccuracyJob())
    ->weeklyOn(0, '03:00') // Sunday at 03:00 AM
    ->name('validate-model-accuracy')
    ->description('Validate prediction accuracy and generate reports')
    ->withoutOverlapping(1800) // Prevent overlapping runs, timeout after 30 minutes
    ->onOneServer(); // Run on only one server in multi-server setup

// Schedule subscription expiry warning check daily at 08:00 AM
Schedule::command(WarnExpiringSubscriptions::class)->daily();
