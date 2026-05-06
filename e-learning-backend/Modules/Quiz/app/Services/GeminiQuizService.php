<?php

namespace Modules\Quiz\Services;

use Illuminate\Support\Facades\Http;

class GeminiQuizService
{
    private string $apiKey;

    private string $model = 'gemini-1.5-flash';

    public function __construct()
    {
        $this->apiKey = config('quiz.gemini_api_key') ?? env('GEMINI_API_KEY');
    }

    public function generateQuestions(string $context, int $count = 5): array
    {
        if (! $this->apiKey) {
            throw new \Exception('GEMINI_API_KEY is not configured');
        }

        $prompt = $this->buildPrompt($context, $count);
        $response = $this->callGeminiApi($prompt);

        return $this->parseQuestions($response);
    }

    private function buildPrompt(string $context, int $count): string
    {
        return <<<PROMPT
Generate exactly {$count} multiple-choice questions about the following topic:

Topic: {$context}

Requirements:
- Each question must be in English
- Each question should have 4 options (A, B, C, D)
- One correct answer per question
- Questions should be challenging but fair

Return ONLY valid JSON (no markdown, no comments) in this exact format:
[
  {
    "question": "...",
    "option_a": "...",
    "option_b": "...",
    "option_c": "...",
    "option_d": "...",
    "correct_option": "A"
  }
]

Do not include any text outside the JSON array.
PROMPT;
    }

    private function callGeminiApi(string $prompt): string
    {
        $response = Http::timeout(30)
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                        ],
                    ],
                ],
            ], [
                'key' => $this->apiKey,
            ])
            ->throw();

        $data = $response->json();

        if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            throw new \Exception('Invalid Gemini API response structure');
        }

        return $data['candidates'][0]['content']['parts'][0]['text'];
    }

    private function parseQuestions(string $response): array
    {
        $text = trim($response);

        // Try to extract JSON if wrapped in markdown code blocks
        if (str_starts_with($text, '```json')) {
            $text = substr($text, 7);
            $text = substr($text, 0, strrpos($text, '```'));
        } elseif (str_starts_with($text, '```')) {
            $text = substr($text, 3);
            $text = substr($text, 0, strrpos($text, '```'));
        }

        $text = trim($text);
        $questions = json_decode($text, true);

        if (! is_array($questions)) {
            throw new \Exception('Failed to parse Gemini response as JSON');
        }

        return array_map(fn ($q) => [
            'question' => $q['question'] ?? '',
            'option_a' => $q['option_a'] ?? '',
            'option_b' => $q['option_b'] ?? '',
            'option_c' => $q['option_c'] ?? '',
            'option_d' => $q['option_d'] ?? '',
            'correct_option' => strtoupper($q['correct_option'] ?? 'A'),
            'order' => 0,
        ], $questions);
    }
}
