<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class BillingController extends Controller
{
    /**
     * Display the billing page.
     */
    public function index()
    {
        $user = auth()->user();

        if (! $user->empresa) {
            return view('billing.index', [
                'subscriptionType' => null,
                'isActive' => false,
                'isExpired' => true,
                'daysRemaining' => 0,
                'endsAt' => null,
                'paymentHistory' => collect(),
            ]);
        }

        $empresa = $user->empresa;
        $subscriptionType = $empresa->activeSubscriptionType();
        $subscription = $empresa->activeSubscription();

        $isActive = false;
        $isExpired = false;
        $daysRemaining = 0;
        $endsAt = null;

        if ($subscription && $subscription->ends_at) {
            $endsAt = $subscription->ends_at;
            $secondsRemaining = max(0, $endsAt->timestamp - now()->timestamp);
            $daysRemaining = (int) ceil($secondsRemaining / 86400);

            if ($endsAt->startOfDay()->isFuture()) {
                $isActive = true;
            } else {
                $isExpired = true;
            }
        } elseif (!$subscription) {
            $isExpired = true;
        }

        $paymentHistory = $empresa->subscriptions()->orderByDesc('created_at')->get();

        return view('billing.index', compact(
            'subscriptionType',
            'subscription',
            'isActive',
            'isExpired',
            'daysRemaining',
            'endsAt',
            'paymentHistory'
        ));
    }

    /**
     * Create a Stripe PaymentIntent for the chosen plan.
     */
    public function pay(Request $request)
    {
        $user = auth()->user();

        if (! $user->empresa) {
            return response()->json(['error' => 'No se encontró la empresa del usuario.'], 400);
        }

        $empresa = $user->empresa;

        $plan = $request->input('plan', 'standard');
        if (!in_array($plan, ['standard', 'premium'], true)) {
            return response()->json(['error' => 'Plan inválido.'], 400);
        }

        $amount = $plan === 'premium' ? 9000 : 5000;
        $type = $plan;

        // Confirm payment — activate subscription
        if ($request->header('X-Confirm-Payment')) {
            $empresa->subscriptions()->updateOrCreate(
                ['type' => $type],
                [
                    'stripe_id' => $request->header('X-Confirm-Payment'),
                    'stripe_status' => 'active',
                    'stripe_price' => $plan === 'premium' ? 'price_premium' : 'price_standard',
                    'ends_at' => now()->addDays(30),
                ]
            );
            return response()->json(['status' => 'ok']);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        try {
            $intent = PaymentIntent::create([
                'amount' => $amount,
                'currency' => 'brl',
                'payment_method_types' => ['card'],
                'metadata' => [
                    'empresa_id' => $empresa->id,
                    'user_id' => $user->id,
                    'plan' => $plan,
                ],
            ]);

            $empresa->update(['stripe_id' => $intent->id]);

            return response()->json([
                'client_secret' => $intent->client_secret,
                'payment_intent_id' => $intent->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Stripe PaymentIntent error: ' . $e->getMessage(), [
                'user_id' => $user->id,
                'empresa_id' => $empresa->id,
            ]);
            return response()->json([
                'error' => __('Error al procesar el pago. Intente nuevamente.'),
            ], 500);
        }
    }
}
