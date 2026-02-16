<?php

namespace App\Services\Concerns;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

trait CallsClaudeApi
{
    private ?string $lastApiError = null;

    private function callClaudeApi(string $systemPrompt, string $userMessage): ?string
    {
        $this->lastApiError = null;

        $apiKey = config('services.anthropic.api_key');
        $model = config('services.anthropic.model');

        if (!$apiKey) {
            Log::error('Voice: ANTHROPIC_API_KEY not configured');
            $this->lastApiError = 'not_configured';
            return null;
        }

        try {
            $response = Http::timeout(15)->withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 1024,
                'system' => $systemPrompt,
                'messages' => [
                    ['role' => 'user', 'content' => $userMessage],
                ],
            ]);

            if (!$response->successful()) {
                $body = $response->body();
                Log::error('Voice: Claude API error', [
                    'status' => $response->status(),
                    'body' => $body,
                ]);

                if (str_contains($body, 'credit balance')) {
                    $this->lastApiError = 'billing';
                }

                return null;
            }

            return $response->json('content.0.text');
        } catch (\Exception $e) {
            Log::error('Voice: Claude API exception', ['error' => $e->getMessage()]);
            return null;
        }
    }

    private function getApiErrorMessage(): string
    {
        return match ($this->lastApiError) {
            'billing' => 'AI service credits have run out. Please add credits to your Anthropic account.',
            'not_configured' => 'AI service is not configured. Please set the API key.',
            default => 'Could not connect to AI service. Please try again.',
        };
    }

    private function parseClaudeResponse(?string $responseText): ?array
    {
        if (!$responseText) {
            return null;
        }

        // Claude might wrap JSON in ```json blocks
        $cleaned = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', trim($responseText));

        $data = json_decode($cleaned, true);

        if (!$data || !isset($data['status'])) {
            Log::warning('Voice: Failed to parse Claude response', ['response' => $responseText]);
            return null;
        }

        return $data;
    }
}
