<?php

namespace App\Http\Controllers;

use App\Services\VoiceTransactionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VoiceTransactionController extends Controller
{
    public function __construct(
        private VoiceTransactionService $voiceService
    ) {}

    /**
     * Parse a voice transcript and create transactions.
     */
    public function parse(Request $request): JsonResponse
    {
        if (!Auth::user()->ai_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI features are not enabled for your account.',
            ], 403);
        }

        $validated = $request->validate([
            'transcript' => 'required|string|max:2000',
            'preview' => 'sometimes|boolean',
        ]);

        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active budget.',
            ], 403);
        }

        $method = ($validated['preview'] ?? false) ? 'preview' : 'parse';

        $result = $this->voiceService->$method(
            $validated['transcript'],
            $budget,
            Auth::user()
        );

        return response()->json($result);
    }

    /**
     * Resolve clarifications and create transactions.
     */
    public function clarify(Request $request): JsonResponse
    {
        if (!Auth::user()->ai_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI features are not enabled for your account.',
            ], 403);
        }

        $validated = $request->validate([
            'session_context' => 'required|string',
            'answers' => 'required|array',
            'answers.*.transaction_index' => 'required|integer',
            'answers.*.field' => 'required|string|in:account_id,category_id,to_account_id',
            'answers.*.value' => 'required|integer',
            'preview' => 'sometimes|boolean',
        ]);

        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active budget.',
            ], 403);
        }

        try {
            $method = ($validated['preview'] ?? false) ? 'previewClarify' : 'clarify';

            $result = $this->voiceService->$method(
                $validated['session_context'],
                $validated['answers'],
                $budget,
                Auth::user()
            );

            return response()->json($result);
        } catch (\Illuminate\Contracts\Encryption\DecryptException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Session expired. Please try again.',
            ], 400);
        }
    }

    /**
     * Create transactions from accumulated preview data.
     */
    public function batchCreate(Request $request): JsonResponse
    {
        if (!Auth::user()->ai_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI features are not enabled for your account.',
            ], 403);
        }

        $validated = $request->validate([
            'transactions' => 'required|array|min:1|max:50',
            'transactions.*.type' => 'required|string|in:expense,income,transfer',
            'transactions.*.amount' => 'required|numeric|min:0.01',
            'transactions.*.payee_name' => 'nullable|string|max:255',
            'transactions.*.account_id' => 'required|integer',
            'transactions.*.category_id' => 'nullable|integer',
            'transactions.*.to_account_id' => 'nullable|integer',
            'transactions.*.date' => 'required|date',
            'transactions.*.memo' => 'nullable|string|max:500',
            'transactions.*.splits' => 'nullable|array',
            'transactions.*.splits.*.category_id' => 'nullable|integer',
            'transactions.*.splits.*.amount' => 'required|numeric|min:0.01',
        ]);

        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active budget.',
            ], 403);
        }

        $result = $this->voiceService->batchCreate(
            $validated['transactions'],
            $budget,
            Auth::user()
        );

        return response()->json($result);
    }

    /**
     * Undo a voice batch (delete all transactions in the batch).
     */
    public function undoBatch(string $batchId): JsonResponse
    {
        if (!Auth::user()->ai_enabled) {
            return response()->json([
                'status' => 'error',
                'message' => 'AI features are not enabled for your account.',
            ], 403);
        }

        $budget = Auth::user()->currentBudget;

        if (!$budget) {
            return response()->json([
                'status' => 'error',
                'message' => 'No active budget.',
            ], 403);
        }

        $result = $this->voiceService->undoBatch($batchId, $budget);

        return response()->json($result);
    }
}
