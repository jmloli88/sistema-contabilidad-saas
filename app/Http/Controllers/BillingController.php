<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BillingController extends Controller
{
    /**
     * Display the billing page with subscription status, PIX payment, and history.
     */
    public function index()
    {
        $user = auth()->user();
        $subscription = $user->subscription('default');

        $isActive = false;
        $isExpired = false;
        $daysRemaining = 0;
        $endsAt = null;

        if ($subscription && $subscription->ends_at) {
            $endsAt = $subscription->ends_at;
            $secondsRemaining = max(0, $endsAt->timestamp - now()->timestamp);
            $daysRemaining = (int) ceil($secondsRemaining / 86400);

            if ($endsAt->isFuture()) {
                $isActive = true;
            } else {
                $isExpired = true;
            }
        } elseif (!$subscription) {
            $isExpired = true;
        }

        // Mock payment history — real history would come from a payments table
        $paymentHistory = collect();

        return view('billing.index', compact(
            'subscription',
            'isActive',
            'isExpired',
            'daysRemaining',
            'endsAt',
            'paymentHistory'
        ));
    }

    /**
     * Create a Stripe PaymentIntent for PIX payment (R$50).
     */
    public function pay(Request $request)
    {
        $user = auth()->user();

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $intent = PaymentIntent::create([
                'amount' => 5000,
                'currency' => 'brl',
                'payment_method_types' => ['pix'],
                'metadata' => [
                    'user_id' => $user->id,
                ],
            ]);

            // Store the PaymentIntent ID temporarily on the user
            $user->update(['stripe_id' => $intent->id]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => __('Error al procesar el pago. Intente nuevamente.'),
            ], 500);
        }
    }
}
