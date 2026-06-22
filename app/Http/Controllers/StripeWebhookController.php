<?php

namespace App\Http\Controllers;

use App\Models\Empresa;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;

class StripeWebhookController extends CashierWebhookController
{
    /**
     * Handle payment_intent.succeeded — create or extend subscription +30 days on the empresa.
     */
    protected function handlePaymentIntentSucceeded(array $payload)
    {
        $intent = $payload['data']['object'];
        $empresaId = $intent['metadata']['empresa_id'] ?? $intent['metadata']['user_id'] ?? null;

        if (! $empresaId) {
            return $this->successMethod();
        }

        // Try by empresa_id first, then fallback to user_id → empresa
        $empresa = Empresa::find($empresaId);

        if (! $empresa) {
            // Try to find via user's empresa
            $user = \App\Models\User::find($empresaId);
            if ($user && $user->empresa) {
                $empresa = $user->empresa;
            }
        }

        if (! $empresa) {
            return $this->successMethod();
        }

        $subscription = $empresa->activeSubscription();
        $plan = $intent['metadata']['plan'] ?? 'standard';
        $type = in_array($plan, ['premium', 'standard'], true) ? $plan : 'standard';
        $stripePrice = $type === 'premium' ? 'price_premium' : 'price_standard';

        if (!$subscription || $subscription->stripe_status === 'incomplete') {
            // First payment or incomplete: create subscription
            $empresa->subscriptions()->updateOrCreate(
                ['type' => $type],
                [
                    'stripe_id' => $intent['id'],
                    'stripe_status' => 'active',
                    'stripe_price' => $stripePrice,
                    'ends_at' => now()->addDays(30),
                    'quantity' => 1,
                ]
            );
        } else {
            // Already exists (created by X-Confirm-Payment) — don't add extra days
            $subscription->update(['stripe_status' => 'active']);
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
