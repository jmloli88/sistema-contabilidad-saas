<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBotService $bot): JsonResponse
    {
        $update = $request->all();

        if (!isset($update['message']['chat']['id']) || !isset($update['message']['text'])) {
            return response()->json(['status' => 'ignored']);
        }

        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];

        $response = $bot->handleMessage($chatId, $text);

        Http::post('https://api.telegram.org/bot' . config('telegram.bot_token') . '/sendMessage', [
            'chat_id' => $chatId,
            'text' => $response,
            'parse_mode' => 'HTML',
        ]);

        return response()->json(['status' => 'ok']);
    }
}
