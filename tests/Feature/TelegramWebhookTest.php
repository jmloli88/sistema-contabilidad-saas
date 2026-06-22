<?php

namespace Tests\Feature;

use App\Models\TelegramUser;
use App\Models\User;
use App\Services\AiChat\ChatQueryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    private const CHAT_ID = 123456789;

    /** @test */
    public function webhook_ignores_update_without_message(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 12345,
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ignored']);
    }

    /** @test */
    public function webhook_ignores_update_without_text(): void
    {
        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'chat' => ['id' => self::CHAT_ID],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ignored']);
    }

    /** @test */
    public function webhook_creates_user_and_returns_welcome_for_new_chat(): void
    {
        Http::fake();

        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'chat' => ['id' => self::CHAT_ID],
                'text' => 'Hola',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        $this->assertDatabaseHas('telegram_users', [
            'chat_id' => self::CHAT_ID,
            'is_authenticated' => false,
        ]);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot/sendMessage'
                && $request['chat_id'] == self::CHAT_ID;
        });
    }

    /** @test */
    public function webhook_authenticated_user_queries_chat(): void
    {
        $user = User::factory()->create(['empresa_id' => 1]);

        TelegramUser::create([
            'chat_id' => self::CHAT_ID,
            'user_id' => $user->id,
            'is_authenticated' => true,
            'auth_token' => null,
        ]);

        Http::fake();

        $this->mock(ChatQueryService::class, function ($mock) use ($user) {
            $mock->shouldReceive('ask')
                ->once()
                ->with('¿Cuántos repases hay?', $user->empresa_id)
                ->andReturn(['answer' => 'Hay 15 repases.', 'tokens' => 42]);
        });

        $response = $this->postJson('/telegram/webhook', [
            'update_id' => 12345,
            'message' => [
                'chat' => ['id' => self::CHAT_ID],
                'text' => '¿Cuántos repases hay?',
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => 'ok']);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.telegram.org/bot/sendMessage'
                && $request['chat_id'] == self::CHAT_ID
                && $request['text'] === 'Hay 15 repases.';
        });
    }
}
