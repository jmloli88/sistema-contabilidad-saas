<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AiChat\ChatQueryService;
use App\Http\Middleware\EnsureSubscriptionIsActive;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiChatEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Skip subscription check — endpoint testing is about chat behavior, not billing
        $this->withoutMiddleware(EnsureSubscriptionIsActive::class);
    }

    public function test_authenticated_user_can_ask_question(): void
    {
        $user = User::factory()->create();

        $this->mock(ChatQueryService::class, function ($mock) use ($user) {
            $mock->shouldReceive('ask')
                ->once()
                ->with('¿Cuántos repases hay?', $user->empresa_id)
                ->andReturn([
                    'answer' => 'Hay 15 repases en total.',
                    'tokens' => 42,
                ]);
        });

        $response = $this->actingAs($user)->postJson('/api/chat/ask', [
            'question' => '¿Cuántos repases hay?',
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'answer' => 'Hay 15 repases en total.',
                'tokens' => 42,
            ]);
    }

    public function test_unauthenticated_user_gets_401(): void
    {
        $response = $this->postJson('/api/chat/ask', [
            'question' => '¿Cuántos repases hay?',
        ]);

        $response->assertStatus(401);
    }

    public function test_rate_limited_user_gets_429(): void
    {
        $user = User::factory()->create();

        $this->mock(ChatQueryService::class, function ($mock) {
            $mock->shouldReceive('ask')
                ->zeroOrMoreTimes()
                ->andReturn([
                    'answer' => 'Respuesta de prueba.',
                    'tokens' => 10,
                ]);
        });

        $this->actingAs($user);

        // Exhaust the 30-request-per-minute throttle limit
        for ($i = 0; $i < 30; $i++) {
            $response = $this->postJson('/api/chat/ask', [
                'question' => "Pregunta de prueba {$i}",
            ]);
            $response->assertStatus(200); // Ensure each normal request works
        }

        // The 31st request within the same minute should be throttled
        $response = $this->postJson('/api/chat/ask', [
            'question' => 'Una pregunta más',
        ]);

        $response->assertStatus(429);
    }

    public function test_empty_question_gets_422(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/chat/ask', [
            'question' => '',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['question']);
    }
}
