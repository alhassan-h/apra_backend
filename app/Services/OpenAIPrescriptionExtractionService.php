<?php

namespace App\Services;

use App\Contracts\PrescriptionExtractionServiceInterface;
use App\Data\ExtractionResult;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use RuntimeException;

class OpenAIPrescriptionExtractionService implements PrescriptionExtractionServiceInterface
{
    private const MODEL_NAME = 'GPT4O_VISION';
    private const MODEL_VERSION = 'gpt-4o';

    private const SYSTEM_PROMPT = <<<'PROMPT'
You are a medical prescription extraction assistant specialized in Nigerian healthcare.
Extract ALL drugs from the provided prescription image and return JSON only.
A prescription may contain one or more drugs — extract every one you find.

For each drug, extract (return null if uncertain or not visible):
- drug_name: the medication name (normalize Nigerian aliases — e.g. PCM → Paracetamol)
- dosage: the dose amount and unit (e.g. "500mg", "5ml")
- frequency: how often to take (e.g. "BD", "TDS", "3 times daily")
- duration: how long to take (e.g. "5 days", "1 week")
- route: administration route (e.g. "Oral", "IV", "Topical")

Also include:
- confidence_score: a single float between 0.0 and 1.0 representing your overall confidence across all drugs

Return ONLY valid JSON. Do not include any explanation or markdown.
Example format (single drug):
{"drugs":[{"drug_name":"Paracetamol","dosage":"500mg","frequency":"BD","duration":"5 days","route":"Oral"}],"confidence_score":0.92}
Example format (multiple drugs):
{"drugs":[{"drug_name":"Paracetamol","dosage":"500mg","frequency":"BD","duration":"5 days","route":"Oral"},{"drug_name":"Amoxicillin","dosage":"250mg","frequency":"TDS","duration":"7 days","route":"Oral"}],"confidence_score":0.87}
PROMPT;

    public function extract(string $imageUrl): ExtractionResult
    {
        $response = OpenAI::chat()->create([
            'model' => self::MODEL_VERSION,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => self::SYSTEM_PROMPT,
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Analyze this handwritten prescription image and return JSON only.',
                        ],
                        [
                            'type' => 'image_url',
                            'image_url' => ['url' => $imageUrl, 'detail' => 'high'],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 1024,
            'temperature' => 0.1,
        ]);

        $rawResponse = $response->toArray();
        $content = $response->choices[0]->message->content ?? '';

        $parsed = $this->parseJsonContent($content);

        // Resilience: model may return a flat array [{...},{...}] instead of {"drugs":[...],"confidence_score":...}
        // When per-drug confidence_score values are present, average them for the overall score.
        if (array_is_list($parsed)) {
            $drugsRaw      = $parsed;
            $perDrugScores = array_filter(array_column($drugsRaw, 'confidence_score'), 'is_numeric');
            $confidenceRaw = count($perDrugScores) > 0
                ? array_sum($perDrugScores) / count($perDrugScores)
                : 0.0;
        } else {
            $drugsRaw      = $parsed['drugs'] ?? [];
            $confidenceRaw = $parsed['confidence_score'] ?? 0.0;
        }

        $drugs = array_map(fn (array $d) => [
            'drug_name'        => $d['drug_name'] ?? null,
            'dosage'           => $d['dosage'] ?? null,
            'frequency'        => $d['frequency'] ?? null,
            'duration'         => $d['duration'] ?? null,
            'route'            => $d['route'] ?? null,
            'confidence_score' => isset($d['confidence_score']) ? (float) $d['confidence_score'] : null,
        ], $drugsRaw);

        return new ExtractionResult(
            drugs: $drugs,
            confidenceScore: (float) $confidenceRaw,
            rawResponse: $rawResponse,
            modelName: self::MODEL_NAME,
            modelVersion: self::MODEL_VERSION,
        );
    }

    public function getModelName(): string
    {
        return self::MODEL_NAME;
    }

    public function getModelVersion(): string
    {
        return self::MODEL_VERSION;
    }

    private function parseJsonContent(string $content): array
    {
        // Strip markdown fences if present
        $content = preg_replace('/^```(?:json)?\s*/m', '', $content);
        $content = preg_replace('/```\s*$/m', '', $content);
        $content = trim($content);

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('OpenAI prescription extraction returned non-JSON', ['content' => $content]);
            throw new RuntimeException('AI model returned an unparseable response: ' . json_last_error_msg());
        }

        return $decoded;
    }
}
