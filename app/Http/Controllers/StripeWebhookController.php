<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle payment_intent.succeeded — create or extend subscription +30 days.
     */
    protected function handlePaymentIntentSucceeded(array $payload)
    {
        $intent = $payload['data']['object'];
        $userId = $intent['metadata']['user_id'] ?? null;

        if (!$userId) {
            return $this->successMethod();
        }

        $user = User::find($userId);
        if (!$user) {
            return $this->successMethod();
        }

        $subscription = $user->subscription('default');

        if (!$subscription || $subscription->stripe_status === 'incomplete') {
            // First payment or incomplete: create subscription
            $user->subscriptions()->updateOrCreate(
                ['type' => 'default'],
                [
                    'stripe_id' => $intent['id'],
                    'stripe_status' => 'active',
                    'stripe_price' => 'price_placeholder',
                    'ends_at' => now()->addDays(30),
                    'quantity' => 1,
                ]
            );
        } else {
            // Extend existing subscription by 30 days from current ends_at (or now)
            $currentEndsAt = $subscription->ends_at ?? now();
            $newEndsAt = $currentEndsAt->gt(now()) ? $currentEndsAt->addDays(30) : now()->addDays(30);

            $subscription->update([
                'stripe_status' => 'active',
                'ends_at' => $newEndsAt,
            ]);
        }

        return $this->successMethod();
    }

    /**
     * Handle payment_intent.payment_failed — log only.
     */
    protected function handlePaymentIntentPaymentFailed(array $payload)
    {
        Log::warning('PIX payment failed', [
            'intent_id' => $payload['data']['object']['id'] ?? null,
        ]);

        return $this->successMethod();
    }
}
