<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\TelegramUser;
use App\Services\AiChat\ChatQueryService;
use Illuminate\Support\Str;

class TelegramBotService
{
    public function __construct(
        private readonly ChatQueryService $chatQuery,
    ) {}

    public function handleMessage(int $chatId, string $text): string
    {
        $telegramUser = TelegramUser::where('chat_id', $chatId)->first();

        if (!$telegramUser || !$telegramUser->is_authenticated) {
            return $this->handleUnauthenticated($telegramUser, $chatId, $text);
        }

        // Enforce PREMIUM subscription — Telegram chat is a PREMIUM-only feature.
        if (!$telegramUser->user->empresa || !$telegramUser->user->empresa->hasPremium()) {
            return "⚠️ Tu plan actual (STANDARD) no incluye el chat por Telegram.\n\n"
                . 'Actualizá a PREMIUM en: ' . url('/billing');
        }

        if (str_starts_with($text, '/')) {
            return $this->handleCommand($text, $telegramUser);
        }

        try {
            $user = $telegramUser->user;
            $empresaId = (int) $user->empresa_id;
            $sessionId = 'tg_' . $chatId;

            // Load conversation history for context (last 10 messages).
            $history = ChatMessage::where('user_id', $user->id)
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(['role', 'content'])
                ->reverse()
                ->values()
                ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                ->all();

            $result = $this->chatQuery->ask($text, $empresaId, $history);

            // Persist the exchange for future context.
            ChatMessage::create([
                'user_id' => $user->id,
                'empresa_id' => $empresaId,
                'session_id' => $sessionId,
                'role' => 'user',
                'content' => $text,
                'tokens_used' => 0,
            ]);
            ChatMessage::create([
                'user_id' => $user->id,
                'empresa_id' => $empresaId,
                'session_id' => $sessionId,
                'role' => 'assistant',
                'content' => $result['answer'],
                'tokens_used' => $result['tokens'] ?? 0,
            ]);

            return $result['answer'];
        } catch (\Exception $e) {
            \Log::error('TelegramBot error: ' . $e->getMessage(), [
                'chat_id' => $chatId,
                'user_id' => $telegramUser->user_id,
                'empresa_id' => $telegramUser->user->empresa_id,
                'text' => $text,
            ]);
            return '⚠️ Ocurrió un error. Intentá de nuevo.';
        }
    }

    private function handleUnauthenticated(?TelegramUser $telegramUser, int $chatId, string $text): string
    {
        if (!$telegramUser) {
            $token = Str::random(32);
            TelegramUser::create([
                'chat_id' => $chatId,
                'auth_token' => $token,
            ]);

            return "👋 ¡Bienvenido al bot de ContaMed!\n\n"
                . "Para vincular tu cuenta, ingresá este código en la aplicación web:\n\n"
                . "`{$token}`\n\n"
                . "O andá a: " . url('/profile') . " y vinculá tu cuenta de Telegram.";
        }

        if (str_starts_with($text, '/start')) {
            return "🔗 Tu cuenta ya está registrada pero no vinculada.\n"
                . "Usá el código `{$telegramUser->auth_token}` en la app para vincularla.";
        }

        return "⚠️ No estás autenticado. Usá /start para ver tu código de vinculación.";
    }

    private function handleCommand(string $command, TelegramUser $telegramUser): string
    {
        return match ($command) {
            '/start' => '👋 ¡Bienvenido! Podés consultar datos financieros escribiendo preguntas en lenguaje natural.',
            '/help' => '🤖 *Comandos disponibles:*' . "\n"
                . '🔄 /start - Iniciar el bot' . "\n"
                . '❓ /help - Mostrar esta ayuda' . "\n\n"
                . '📝 *Ejemplos de preguntas:*' . "\n"
                . '📊 "¿Cuántos repases hay en marzo?"' . "\n"
                . '💰 "¿Cuál fue el ingreso total del mes pasado?"' . "\n"
                . '💸 "Mostrame los gastos por categoría"' . "\n"
                . '🏥 "¿Cuál es mi último repase?"' . "\n"
                . '📅 "¿Qué repases tengo de la semana pasada?"',
            default => "❌ Comando desconocido. Usá /help para ver los comandos disponibles.",
        };
    }
}
