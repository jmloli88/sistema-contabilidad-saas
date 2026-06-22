<?php

namespace Tests\Unit\Services;

use App\Models\TelegramUser;
use App\Models\User;
use App\Services\AiChat\ChatQueryService;
use App\Services\TelegramBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class TelegramBotServiceTest extends TestCase
{
    use RefreshDatabase;

    private ChatQueryService|Mockery\MockInterface $chatQueryMock;
    private TelegramBotService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chatQueryMock = Mockery::mock(ChatQueryService::class);
        $this->service = new TelegramBotService($this->chatQueryMock);
    }

    /** @test */
    public function creates_telegram_user_for_new_chat_id_and_returns_welcome_message(): void
    {
        $chatId = 123456789;
        $text = 'Hola';

        $response = $this->service->handleMessage($chatId, $text);

        // Must create a TelegramUser record
        $telegramUser = TelegramUser::where('chat_id', $chatId)->first();
        $this->assertNotNull($telegramUser);
        $this->assertFalse($telegramUser->is_authenticated);
        $this->assertNotNull($telegramUser->auth_token);

        // Response must contain the welcome message and the auth token
        $this->assertStringContainsString('Bienvenido', $response);
        $this->assertStringContainsString($telegramUser->auth_token, $response);
    }

    /** @test */
    public function existing_unauthenticated_user_gets_link_instructions_with_start_command(): void
    {
        $chatId = 123456789;
        $authToken = Str::random(32);

        TelegramUser::create([
            'chat_id' => $chatId,
            'auth_token' => $authToken,
            'is_authenticated' => false,
        ]);

        $response = $this->service->handleMessage($chatId, '/start');

        $this->assertStringContainsString($authToken, $response);
        $this->assertStringContainsString('vincularla', strtolower($response));
    }

    /** @test */
    public function existing_unauthenticated_user_gets_warning_without_start_command(): void
    {
        $chatId = 123456789;
        $authToken = Str::random(32);

        TelegramUser::create([
            'chat_id' => $chatId,
            'auth_token' => $authToken,
            'is_authenticated' => false,
        ]);

        $response = $this->service->handleMessage($chatId, 'Hola');

        $this->assertStringContainsString('autenticado', strtolower($response));
        $this->assertStringContainsString('/start', $response);
    }

    /** @test */
    public function authenticated_user_with_regular_message_calls_chat_query_service(): void
    {
        $chatId = 123456789;
        $user = User::factory()->create(['empresa_id' => 1]);

        TelegramUser::create([
            'chat_id' => $chatId,
            'user_id' => $user->id,
            'is_authenticated' => true,
            'auth_token' => null,
        ]);

        $this->chatQueryMock
            ->shouldReceive('ask')
            ->once()
            ->with('¿Cuántos repases hay?', $user->empresa_id, Mockery::type('array'))
            ->andReturn(['answer' => 'Hay 15 repases.', 'tokens' => 42]);

        $response = $this->service->handleMessage($chatId, '¿Cuántos repases hay?');

        $this->assertSame('Hay 15 repases.', $response);
    }

    /** @test */
    public function authenticated_user_with_unknown_command_receives_helpful_message(): void
    {
        $chatId = 123456789;
        $user = User::factory()->create();

        TelegramUser::create([
            'chat_id' => $chatId,
            'user_id' => $user->id,
            'is_authenticated' => true,
            'auth_token' => null,
        ]);

        $response = $this->service->handleMessage($chatId, '/unknown');

        $this->assertStringContainsString('comando', strtolower($response));
    }
}
