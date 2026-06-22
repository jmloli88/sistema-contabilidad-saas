<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Services\AiChat\ChatQueryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AiChatController extends Controller
{
    public function __construct(
        private readonly ChatQueryService $chatQuery,
    ) {}

    public function ask(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'question' => 'required|string|max:500',
        ]);

        if (!$request->user() || !$request->user()->empresa_id) {
            return response()->json(['answer' => 'No se pudo identificar tu empresa. Contactá al administrador.'], 400);
        }

        $user = $request->user();
        $empresaId = (int) $user->empresa_id;
        $sessionId = ChatMessage::getSessionId($user->id);

        // Load conversation history for context.
        $history = $this->loadHistory($user->id, $sessionId);

        try {
            $result = $this->chatQuery->ask($validated['question'], $empresaId, $history);

            // Persist the exchange so future messages have context.
            $this->saveMessage($user->id, $empresaId, $sessionId, 'user', $validated['question']);
            $this->saveMessage($user->id, $empresaId, $sessionId, 'assistant', $result['answer'], $result['tokens']);

            return response()->json($result);
        } catch (\Exception $e) {
            \Log::error('AiChatController error: ' . $e->getMessage());
            return response()->json([
                'answer' => 'Ocurrió un error al procesar tu consulta. Por favor, intentá de nuevo más tarde.',
            ], 500);
        }
    }

    public function stream(Request $request): \Symfony\Component\HttpFoundation\StreamedResponse
    {
        $validated = $request->validate(['question' => 'required|string|max:500']);

        return response()->stream(function () use ($validated, $request) {
            // Disable all output buffer levels so each echo goes directly to
            // the network without waiting for a buffer to fill.
            while (ob_get_level() > 0) {
                ob_end_flush();
            }

            if (!$request->user() || !$request->user()->empresa_id) {
                echo "data: " . json_encode(['token' => 'Error: empresa no identificada', 'done' => true]) . "\n\n";
                flush();
                return;
            }

            $user = $request->user();
            $empresaId = (int) $user->empresa_id;
            $sessionId = ChatMessage::getSessionId($user->id);
            $history = $this->loadHistory($user->id, $sessionId);

            // Save the user's question immediately so it's persisted even if streaming fails.
            $this->saveMessage($user->id, $empresaId, $sessionId, 'user', $validated['question']);

            $generator = $this->chatQuery->askStreaming(
                $validated['question'],
                $empresaId,
                $history,
            );

            $fullResponse = '';
            foreach ($generator as $chunk) {
                if (!empty($chunk['delta'])) {
                    $fullResponse .= $chunk['delta'];
                }
                echo "data: " . json_encode([
                    'token' => $chunk['delta'],
                    'done' => $chunk['done'],
                    'status' => $chunk['status'] ?? null,
                ]) . "\n\n";
                flush();
            }

            // Persist the assistant's response after streaming completes.
            $this->saveMessage($user->id, $empresaId, $sessionId, 'assistant', $fullResponse);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $messages = ChatMessage::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'role', 'content', 'created_at'])
            ->reverse()
            ->values();

        return response()->json(['messages' => $messages]);
    }

    public function clearHistory(Request $request): JsonResponse
    {
        ChatMessage::where('user_id', $request->user()->id)->delete();
        return response()->json(['status' => 'ok']);
    }

    /**
     * Load the recent conversation history as an array of {role, content} pairs.
     *
     * @return array<int, array{role: string, content: string}>
     */
    private function loadHistory(int $userId, string $sessionId): array
    {
        return ChatMessage::where('user_id', $userId)
            ->where('session_id', $sessionId)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get(['role', 'content'])
            ->reverse()
            ->values()
            ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
            ->all();
    }

    private function saveMessage(int $userId, int $empresaId, string $sessionId, string $role, string $content, int $tokens = 0): void
    {
        ChatMessage::create([
            'user_id' => $userId,
            'empresa_id' => $empresaId,
            'session_id' => $sessionId,
            'role' => $role,
            'content' => $content,
            'tokens_used' => $tokens,
        ]);
    }
}
