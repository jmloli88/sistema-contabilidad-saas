<?php

namespace App\Http\Controllers;

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

        $result = $this->chatQuery->ask(
            $validated['question'],
            (int) $request->user()->empresa_id,
        );

        return response()->json($result);
    }
}
