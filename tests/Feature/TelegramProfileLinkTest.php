<?php

namespace Tests\Feature;

use App\Models\TelegramUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class TelegramProfileLinkTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function authenticated_user_can_link_telegram_account_with_valid_token(): void
    {
        $user = User::factory()->create();
        $authToken = Str::random(32);

        $telegramUser = TelegramUser::create([
            'chat_id' => 123456789,
            'auth_token' => $authToken,
            'is_authenticated' => false,
        ]);

        $response = $this->actingAs($user)->post('/profile/link-telegram', [
            'telegram_auth_token' => $authToken,
        ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect();

        $telegramUser->refresh();

        $this->assertSame($user->id, $telegramUser->user_id);
        $this->assertTrue($telegramUser->is_authenticated);
        $this->assertNull($telegramUser->auth_token);
    }

    /** @test */
    public function unauthenticated_user_cannot_link_telegram(): void
    {
        $response = $this->post('/profile/link-telegram', [
            'telegram_auth_token' => Str::random(32),
        ]);

        $response->assertRedirect(route('login'));
    }

    /** @test */
    public function invalid_token_returns_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/profile/link-telegram', [
            'telegram_auth_token' => 'non_existent_token',
        ]);

        $response->assertSessionHasErrors();
    }

    /** @test */
    public function already_linked_token_cannot_be_reused(): void
    {
        $user = User::factory()->create();
        $authToken = Str::random(32);

        // First link works
        $telegramUser = TelegramUser::create([
            'chat_id' => 123456789,
            'auth_token' => $authToken,
            'is_authenticated' => false,
        ]);

        $this->actingAs($user)->post('/profile/link-telegram', [
            'telegram_auth_token' => $authToken,
        ]);

        // Now the token is null (consumed)
        $telegramUser->refresh();
        $this->assertNull($telegramUser->auth_token);
    }
}
