<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class StripeWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function generateStripeSignature(string $payload, string $secret): string
    {
        $timestamp = now()->timestamp;
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        return "t={$timestamp},v1={$signature}";
    }

    protected function postWebhook(array $payload, ?string $signature = null)
    {
        $payloadJson = json_encode($payload);
        $secret = $signature === false ? null : config('cashier.webhook.secret');

        $headers = ['Content-Type' => 'application/json'];

        if ($signature === null && $secret) {
            $headers['Stripe-Signature'] = $this->generateStripeSignature($payloadJson, $secret);
        } elseif ($signature !== false) {
            $headers['Stripe-Signature'] = $signature ?? $this->generateStripeSignature($payloadJson, $secret ?? 'whsec_test');
        }

        return $this->postJson('/stripe/webhook', $payload, $headers);
    }

    protected function validPaymentIntentPayload(int $empresaId, string $intentId = 'pi_test_123'): array
    {
        return [
            'id' => 'evt_test_123',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => $intentId,
                    'amount' => 5000,
                    'currency' => 'brl',
                    'status' => 'succeeded',
                    'metadata' => [
                        'empresa_id' => (string) $empresaId,
                        'user_id' => (string) $empresaId,
                    ],
                ],
            ],
        ];
    }

    public function test_payment_intent_succeeded_creates_subscription_for_new_empresa(): void
    {
        $empresa = Empresa::factory()->create();

        $payload = $this->validPaymentIntentPayload($empresa->id);

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('subscriptions', [
            'empresa_id' => $empresa->id,
            'stripe_status' => 'active',
        ]);

        $subscription = $empresa->subscription('standard');
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->ends_at->isFuture());
    }

    public function test_payment_intent_succeeded_extends_existing_subscription_by_30_days(): void
    {
        $empresa = Empresa::factory()->create();
        $originalEndsAt = now()->addDays(10);

        $empresa->subscriptions()->create([
            'type' => 'standard',
            'stripe_id' => 'sub_existing',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => $originalEndsAt,
        ]);

        $payload = $this->validPaymentIntentPayload($empresa->id, 'pi_test_456');

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);

        $subscription = $empresa->fresh()->subscription('default');
        $this->assertNotNull($subscription);
        // Should be original + 30 = 40 days from original
        $this->assertTrue($subscription->ends_at->gt(now()->addDays(35)));
    }

    public function test_invalid_signature_returns_403(): void
    {
        $payload = $this->validPaymentIntentPayload(1);

        $response = $this->postWebhook($payload, 't=invalid,sig=bad');

        $response->assertStatus(403);
    }

    public function test_payment_intent_failed_logs_warning(): void
    {
        Log::shouldReceive('warning')
            ->once()
            ->withArgs(fn ($message) => str_contains($message, 'PIX payment failed'));

        $payload = [
            'id' => 'evt_test_failed',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_test_failed',
                    'metadata' => [],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
    }

    public function test_payment_intent_succeeded_without_empresa_id_still_returns_200(): void
    {
        $payload = [
            'id' => 'evt_test_no_empresa',
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_no_empresa',
                    'amount' => 5000,
                    'currency' => 'brl',
                    'metadata' => [],
                ],
            ],
        ];

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
    }

    public function test_payment_intent_succeeded_for_nonexistent_empresa_still_returns_200(): void
    {
        $payload = $this->validPaymentIntentPayload(99999);

        $response = $this->postWebhook($payload);

        $response->assertStatus(200);
    }
}
