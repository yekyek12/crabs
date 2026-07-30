<?php

namespace App\Services;

use App\Models\CrabSpecies;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;

class CrabRecognitionService
{
    private ?array $speciesAliases = null;
    private ?array $speciesMeta = null;

    public function predict(UploadedFile $image): array
    {
        if (filter_var(config('services.ai.fast_mode_enabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return $this->predictFast($image);
        }

        $results = [];
        $errors = [];
        $consensusEnabled = filter_var(config('services.ai.consensus_enabled', true), FILTER_VALIDATE_BOOLEAN);

        foreach (config('services.ai.provider_order', ['local']) as $provider) {
            try {
                $payload = $this->predictWithProvider($image, $provider);

                if (! $consensusEnabled && ($payload['success'] ?? false)) {
                    return $payload;
                }

                $results[] = $payload;
            } catch (Throwable $e) {
                report($e);
                $errors[] = "{$provider}: {$e->getMessage()}";
            }
        }

        if ($results !== []) {
            return $this->buildConsensus($results, $errors);
        }

        return $this->unknownPayload([], $errors, [
            'No AI recognition provider was available. Try again when the network and provider APIs are reachable.',
        ]);
    }

    private function predictFast(UploadedFile $image): array
    {
        $started = hrtime(true);
        $results = [];
        $errors = [];
        $providers = config('services.ai.fast_provider_order', ['local', 'gemini']);

        foreach ($providers as $provider) {
            try {
                $payload = $this->predictWithProvider($image, $provider);
                $payload['processing_time_ms'] ??= $this->elapsedMilliseconds($started);
                $results[] = $payload;

                if ($payload['success'] ?? false) {
                    return $this->withFastReliabilityMetadata($payload, $results, $errors);
                }
            } catch (Throwable $e) {
                report($e);
                $errors[] = "{$provider}: {$e->getMessage()}";
            }
        }

        $payload = $this->unknownPayload($results, $errors, [
            'Fast scan did not produce a usable crab species identification.',
        ]);
        $payload['processing_time_ms'] = $this->elapsedMilliseconds($started);
        $payload['analysis_mode'] = 'fast_single_provider';

        return $payload;
    }

    private function predictWithProvider(UploadedFile $image, string $provider): array
    {
        return match ($provider) {
            'gemini' => $this->predictWithGemini($image),
            'anthropic' => $this->predictWithAnthropic($image),
            'groq' => $this->predictWithOpenAiCompatible($image, 'groq', 'https://api.groq.com/openai/v1/chat/completions'),
            'openrouter' => $this->predictWithOpenAiCompatible($image, 'openrouter', 'https://openrouter.ai/api/v1/chat/completions'),
            'cohere' => $this->predictWithCohere($image),
            'wisdomgate' => $this->predictWithOpenAiCompatible($image, 'wisdomgate', 'https://api.wisdomgate.ai/v1/chat/completions'),
            'local' => $this->predictWithLocalService($image),
            default => throw new RuntimeException("Unknown AI provider [{$provider}]."),
        };
    }

    private function predictWithGemini(UploadedFile $image): array
    {
        $key = (string) config('services.ai.providers.gemini.key');
        $model = (string) config('services.ai.providers.gemini.model');
        if ($key === '' || $model === '') throw new RuntimeException('Gemini is not configured.');

        $response = $this->client()
            ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$key}", [
                'contents' => [[
                    'role' => 'user',
                    'parts' => [
                        ['text' => $this->prompt()],
                        ['inline_data' => [
                            'mime_type' => $image->getMimeType() ?: 'image/jpeg',
                            'data' => base64_encode(file_get_contents($image->getRealPath())),
                        ]],
                    ],
                ]],
                'generationConfig' => [
                    'temperature' => 0.0,
                    'response_mime_type' => 'application/json',
                ],
            ]);

        $text = data_get($this->json($response), 'candidates.0.content.parts.0.text');

        return $this->normalizeTextPayload($text, 'gemini', $model);
    }

    private function predictWithAnthropic(UploadedFile $image): array
    {
        $key = (string) config('services.ai.providers.anthropic.key');
        $model = (string) config('services.ai.providers.anthropic.model');
        if ($key === '' || $model === '') throw new RuntimeException('Anthropic is not configured.');

        $response = $this->client()
            ->withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => (string) config('services.ai.providers.anthropic.version', '2023-06-01'),
            ])
            ->post('https://api.anthropic.com/v1/messages', [
                'model' => $model,
                'max_tokens' => 700,
                'temperature' => 0.0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'image',
                            'source' => [
                                'type' => 'base64',
                                'media_type' => $image->getMimeType() ?: 'image/jpeg',
                                'data' => base64_encode(file_get_contents($image->getRealPath())),
                            ],
                        ],
                        ['type' => 'text', 'text' => $this->prompt()],
                    ],
                ]],
            ]);

        $text = data_get($this->json($response), 'content.0.text');

        return $this->normalizeTextPayload($text, 'anthropic', $model);
    }

    private function predictWithOpenAiCompatible(UploadedFile $image, string $provider, string $url): array
    {
        $key = (string) config("services.ai.providers.{$provider}.key");
        $model = (string) config("services.ai.providers.{$provider}.model");
        if ($key === '' || $model === '') throw new RuntimeException("{$provider} is not configured.");

        $headers = ['Authorization' => "Bearer {$key}"];
        if ($provider === 'openrouter') {
            $headers += ['HTTP-Referer' => config('app.url'), 'X-Title' => config('app.name')];
        }

        $response = $this->client()
            ->withHeaders($headers)
            ->post($url, [
                'model' => $model,
                'temperature' => 0.0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->prompt()],
                        ['type' => 'image_url', 'image_url' => ['url' => $this->dataUrl($image)]],
                    ],
                ]],
            ]);

        $text = data_get($this->json($response), 'choices.0.message.content');

        return $this->normalizeTextPayload($text, $provider, $model);
    }

    private function predictWithCohere(UploadedFile $image): array
    {
        $key = (string) config('services.ai.providers.cohere.key');
        $model = (string) config('services.ai.providers.cohere.model');
        if ($key === '' || $model === '') throw new RuntimeException('Cohere is not configured.');

        $response = $this->client()
            ->withToken($key)
            ->post('https://api.cohere.com/v2/chat', [
                'model' => $model,
                'temperature' => 0.0,
                'messages' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'text', 'text' => $this->prompt()],
                        ['type' => 'image_url', 'image_url' => ['url' => $this->dataUrl($image)]],
                    ],
                ]],
            ]);

        $payload = $this->json($response);
        $text = data_get($payload, 'message.content.0.text') ?? data_get($payload, 'text');

        return $this->normalizeTextPayload($text, 'cohere', $model);
    }

    private function predictWithLocalService(UploadedFile $image): array
    {
        $baseUrl = rtrim((string) config('services.ai.url'), '/');
        if ($baseUrl === '') throw new RuntimeException('AI service URL is not configured.');

        $request = $this->client();
        if ($token = config('services.ai.token')) $request = $request->withToken($token);

        try {
            $response = $request->attach('image', fopen($image->getRealPath(), 'r'), $image->hashName())->post($baseUrl.'/api/v1/predict');
        } catch (ConnectionException $e) {
            report($e);
            throw new RuntimeException('The local AI recognition service is unavailable.');
        }

        $payload = $this->json($response);
        if (! array_key_exists('success', $payload)) throw new RuntimeException('The local AI recognition service returned an invalid response.');

        return $this->normalizeStructuredPayload($payload, 'local', data_get($payload, 'model.version', 'local'));
    }

    private function buildConsensus(array $results, array $errors): array
    {
        $usable = array_values(array_filter($results, fn (array $result) => filled(data_get($result, 'prediction.class_name'))));

        if ($usable === []) {
            return $this->unknownPayload($results, $errors, ['No AI provider returned a usable crab species identification.']);
        }

        $groups = [];
        foreach ($usable as $result) {
            $className = (string) data_get($result, 'prediction.class_name');
            $key = $this->normalizeKey($className);
            $groups[$key]['class_name'] = $className;
            $groups[$key]['results'][] = $result;
        }

        uasort($groups, function (array $a, array $b) {
            $countCompare = count($b['results']) <=> count($a['results']);
            if ($countCompare !== 0) return $countCompare;

            return $this->averageConfidence($b['results']) <=> $this->averageConfidence($a['results']);
        });

        $best = reset($groups);
        $usableCount = count($usable);
        $agreementCount = count($best['results']);
        $providerCount = count($results) + count($errors);
        $requiredProviderCount = max(1, (int) config('services.ai.required_provider_count', 6));
        $minAgreement = max(1, (int) config('services.ai.min_provider_agreement', 4));
        $allowSingleProvider = filter_var(config('services.ai.allow_single_provider_result', false), FILTER_VALIDATE_BOOLEAN);
        $hasRequiredProviderCoverage = $providerCount >= $requiredProviderCount;
        $hasRequiredAgreement = $agreementCount >= $minAgreement;
        $singleProviderFallback = $usableCount === 1 && $allowSingleProvider;
        $success = $hasRequiredProviderCoverage && ($hasRequiredAgreement || $singleProviderFallback);
        $warnings = $this->collectWarnings($results);

        if (! $hasRequiredProviderCoverage) {
            $warnings[] = "Only {$providerCount} AI provider(s) were attempted. This scan requires {$requiredProviderCount} providers for reliable consensus.";
        }

        if (! $hasRequiredAgreement) {
            $warnings[] = $singleProviderFallback
                ? 'Only one AI provider returned a crab species identification. Treat this as low-confidence and verify against references.'
                : 'AI providers did not reach the required agreement. Treat this scan as unresolved.';
        }

        if ($errors !== []) {
            $warnings[] = 'Some AI providers were unavailable or could not process this image.';
        }

        $averageConfidence = $this->averageConfidence($best['results']);
        $agreementRatio = $agreementCount / max(1, $usableCount);
        $confidence = $success ? min(0.99, $averageConfidence * (0.72 + (0.28 * $agreementRatio))) : null;

        if ($singleProviderFallback && $confidence !== null) {
            $confidence = min($confidence, 0.59);
        }

        return [
            'success' => $success,
            'prediction' => [
                'class_name' => $success ? $best['class_name'] : null,
                'class_id' => $success ? data_get($this->speciesMeta(), $best['class_name'].'.model_class_id') : null,
                'confidence' => $confidence,
                'bounding_box' => null,
            ],
            'global_species' => $success ? $this->bestGlobalDetails($best['results']) : null,
            'image_quality' => [
                'blur_score' => null,
                'brightness_score' => null,
                'warnings' => array_values(array_unique(array_filter($warnings))),
            ],
            'processing_time_ms' => null,
            'model' => [
                'provider' => 'consensus',
                'name' => 'Multi-provider crab consensus',
                'version' => "{$agreementCount}/{$usableCount} agree",
            ],
            'consensus' => [
                'provider_count' => $providerCount,
                'required_provider_count' => $requiredProviderCount,
                'usable_provider_count' => $usableCount,
                'agreement_count' => $agreementCount,
                'minimum_required' => $minAgreement,
                'provider_coverage_met' => $hasRequiredProviderCoverage,
                'agreement_met' => $hasRequiredAgreement,
                'reliability_label' => $success ? 'six-provider consensus' : 'needs review',
                'selected_class_name' => $success ? $best['class_name'] : null,
                'provider_errors' => $errors,
            ],
            'provider_results' => array_map(fn (array $result) => [
                'provider' => data_get($result, 'model.provider'),
                'model' => data_get($result, 'model.version'),
                'class_name' => data_get($result, 'prediction.class_name'),
                'common_name' => data_get($result, 'global_species.common_name'),
                'scientific_name' => data_get($result, 'global_species.scientific_name'),
                'confidence' => data_get($result, 'prediction.confidence'),
                'success' => $result['success'] ?? false,
            ], $results),
        ];
    }

    private function normalizeTextPayload(?string $text, string $provider, string $model): array
    {
        return $this->normalizeStructuredPayload($this->decodeJsonText($text), $provider, $model, $text);
    }

    private function normalizeStructuredPayload(array $data, string $provider, string $model, ?string $rawText = null): array
    {
        $reportedName = trim((string) (data_get($data, 'prediction.class_name') ?? $data['class_name'] ?? $data['species'] ?? ''));
        $scientificName = trim((string) (data_get($data, 'prediction.scientific_name') ?? $data['scientific_name'] ?? ''));
        $commonName = trim((string) (data_get($data, 'prediction.common_name') ?? $data['common_name'] ?? ''));
        $candidateName = $scientificName ?: $reportedName ?: $commonName;
        $warnings = array_values(array_filter((array) (data_get($data, 'image_quality.warnings') ?? $data['quality_warnings'] ?? $data['warnings'] ?? [])));
        $isUnknown = $this->isUnknownSpecies($candidateName);
        $globalDetection = filter_var(config('services.ai.global_detection', true), FILTER_VALIDATE_BOOLEAN);
        $supportedName = $isUnknown ? null : $this->canonicalSupportedSpeciesName($candidateName);
        $className = $isUnknown ? null : ($supportedName ?: ($globalDetection ? ($scientificName ?: $reportedName ?: $commonName) : null));

        if (! $globalDetection && ! $supportedName && $candidateName !== '' && ! $isUnknown) {
            $warnings[] = 'Provider reported a species outside the supported library, but global detection is disabled.';
        } elseif ($className && ! $supportedName) {
            $warnings[] = 'This appears to be outside the local supported species library. Showing global AI identification details.';
        }

        $confidence = data_get($data, 'prediction.confidence') ?? $data['confidence'] ?? null;
        $confidence = $confidence === null ? null : max(0, min(1, (float) $confidence));
        $globalSpecies = [
            'common_name' => $commonName ?: ($supportedName ? data_get($this->speciesMeta(), $supportedName.'.common_name') : null),
            'scientific_name' => $scientificName ?: $className,
            'family' => data_get($data, 'family') ?? data_get($data, 'taxonomy.family'),
            'classification' => data_get($data, 'classification') ?? data_get($data, 'taxonomy.order'),
            'habitat' => $data['habitat'] ?? null,
            'visual_characteristics' => $data['visual_characteristics'] ?? null,
            'geographic_range' => $data['geographic_range'] ?? null,
            'edible_status' => $data['edible_status'] ?? null,
            'caution_notes' => $data['caution_notes'] ?? null,
            'reference_hint' => $data['reference_hint'] ?? null,
            'is_supported_library_match' => (bool) $supportedName,
        ];

        return [
            'success' => $className !== null,
            'prediction' => [
                'class_name' => $className,
                'class_id' => $supportedName ? data_get($this->speciesMeta(), $supportedName.'.model_class_id') : null,
                'confidence' => $confidence,
                'bounding_box' => data_get($data, 'prediction.bounding_box') ?? $data['bounding_box'] ?? null,
            ],
            'global_species' => $globalSpecies,
            'image_quality' => [
                'blur_score' => data_get($data, 'image_quality.blur_score') ?? $data['blur_score'] ?? null,
                'brightness_score' => data_get($data, 'image_quality.brightness_score') ?? $data['brightness_score'] ?? null,
                'warnings' => $warnings,
            ],
            'processing_time_ms' => data_get($data, 'processing_time_ms'),
            'model' => [
                'provider' => $provider,
                'name' => $provider,
                'version' => $model,
            ],
            'raw_text' => $rawText,
        ];
    }

    private function unknownPayload(array $results, array $errors, array $warnings): array
    {
        return [
            'success' => false,
            'prediction' => [
                'class_name' => null,
                'class_id' => null,
                'confidence' => null,
                'bounding_box' => null,
            ],
            'image_quality' => [
                'blur_score' => null,
                'brightness_score' => null,
                'warnings' => array_values(array_unique(array_merge($this->collectWarnings($results), $warnings))),
            ],
            'processing_time_ms' => null,
            'model' => [
                'provider' => 'consensus',
                'name' => 'Multi-provider crab consensus',
                'version' => 'unresolved',
            ],
            'consensus' => [
                'provider_count' => count($results) + count($errors),
                'required_provider_count' => max(1, (int) config('services.ai.required_provider_count', 6)),
                'usable_provider_count' => 0,
                'agreement_count' => 0,
                'minimum_required' => (int) config('services.ai.min_provider_agreement', 4),
                'provider_coverage_met' => (count($results) + count($errors)) >= max(1, (int) config('services.ai.required_provider_count', 6)),
                'agreement_met' => false,
                'reliability_label' => 'needs review',
                'selected_class_name' => null,
                'provider_errors' => $errors,
            ],
            'provider_results' => array_map(fn (array $result) => [
                'provider' => data_get($result, 'model.provider'),
                'model' => data_get($result, 'model.version'),
                'class_name' => data_get($result, 'prediction.class_name'),
                'common_name' => data_get($result, 'global_species.common_name'),
                'scientific_name' => data_get($result, 'global_species.scientific_name'),
                'confidence' => data_get($result, 'prediction.confidence'),
                'success' => $result['success'] ?? false,
            ], $results),
        ];
    }

    private function collectWarnings(array $results): array
    {
        return collect($results)
            ->flatMap(fn (array $result) => (array) data_get($result, 'image_quality.warnings', []))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function withFastReliabilityMetadata(array $payload, array $results, array $errors): array
    {
        $confidence = data_get($payload, 'prediction.confidence');
        $minConfidence = (float) config('services.ai.fast_min_confidence', config('services.ai.confidence_threshold', 0.60));
        $warnings = (array) data_get($payload, 'image_quality.warnings', []);

        if ($confidence !== null && (float) $confidence < $minConfidence) {
            $warnings[] = 'Fast scan confidence is below the configured reliability threshold. Treat this as low-confidence and verify against references.';
        }

        $payload['analysis_mode'] = 'fast_single_provider';
        $payload['image_quality']['warnings'] = array_values(array_unique(array_filter($warnings)));
        $payload['consensus'] = [
            'provider_count' => count($results) + count($errors),
            'required_provider_count' => 1,
            'usable_provider_count' => ($payload['success'] ?? false) ? 1 : 0,
            'agreement_count' => ($payload['success'] ?? false) ? 1 : 0,
            'minimum_required' => 1,
            'provider_coverage_met' => true,
            'agreement_met' => (bool) ($payload['success'] ?? false),
            'reliability_label' => 'single-provider fast scan',
            'selected_class_name' => data_get($payload, 'prediction.class_name'),
            'provider_errors' => $errors,
        ];
        $payload['provider_results'] = array_map(fn (array $result) => [
            'provider' => data_get($result, 'model.provider'),
            'model' => data_get($result, 'model.version'),
            'class_name' => data_get($result, 'prediction.class_name'),
            'common_name' => data_get($result, 'global_species.common_name'),
            'scientific_name' => data_get($result, 'global_species.scientific_name'),
            'confidence' => data_get($result, 'prediction.confidence'),
            'success' => $result['success'] ?? false,
        ], $results);

        return $payload;
    }

    private function elapsedMilliseconds(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function averageConfidence(array $results): float
    {
        $values = array_values(array_filter(array_map(
            fn (array $result) => data_get($result, 'prediction.confidence'),
            $results
        ), fn ($confidence) => $confidence !== null));

        if ($values === []) return 0.5;

        return array_sum($values) / count($values);
    }

    private function bestGlobalDetails(array $results): array
    {
        $sorted = $results;
        usort($sorted, fn (array $a, array $b) => (data_get($b, 'prediction.confidence') ?? 0) <=> (data_get($a, 'prediction.confidence') ?? 0));

        $details = (array) data_get($sorted[0] ?? [], 'global_species', []);
        foreach ($sorted as $result) {
            foreach ((array) data_get($result, 'global_species', []) as $key => $value) {
                if (($details[$key] ?? null) === null && $value !== null && $value !== '') {
                    $details[$key] = $value;
                }
            }
        }

        return $details;
    }

    private function client(): PendingRequest
    {
        $request = Http::timeout((int) config('services.ai.timeout', 60))->acceptJson();

        return filter_var(config('services.ai.retry_enabled', true), FILTER_VALIDATE_BOOLEAN)
            ? $request->retry(1, 250)
            : $request;
    }

    private function json($response): array
    {
        if (! $response->successful()) {
            throw new RuntimeException('Provider request failed with HTTP '.$response->status().'.');
        }

        $payload = $response->json();
        if (! is_array($payload)) throw new RuntimeException('Provider returned a non-JSON response.');

        return $payload;
    }

    private function decodeJsonText(?string $text): array
    {
        if (! is_string($text) || trim($text) === '') throw new RuntimeException('Provider returned an empty answer.');

        $clean = trim($text);
        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/m', '', $clean) ?: $clean;

        if (! str_starts_with($clean, '{')) {
            preg_match('/\{.*\}/s', $clean, $match);
            $clean = $match[0] ?? $clean;
        }

        $data = json_decode($clean, true);
        if (! is_array($data)) throw new RuntimeException('Provider did not return valid recognition JSON.');

        return $data;
    }

    private function dataUrl(UploadedFile $image): string
    {
        return 'data:'.($image->getMimeType() ?: 'image/jpeg').';base64,'.base64_encode(file_get_contents($image->getRealPath()));
    }

    private function isUnknownSpecies(string $name): bool
    {
        $key = $this->normalizeKey($name);

        return $key === ''
            || str_contains($key, 'unknown')
            || str_contains($key, 'not a crab')
            || str_contains($key, 'no crab')
            || str_contains($key, 'cannot identify')
            || str_contains($key, 'uncertain crab');
    }

    private function canonicalSupportedSpeciesName(string $name): ?string
    {
        $name = trim($name);
        if ($name === '') return null;

        $key = $this->normalizeKey($name);

        $aliases = $this->speciesAliases();
        if (isset($aliases[$key])) return $aliases[$key];

        foreach ($aliases as $alias => $scientificName) {
            if (strlen($alias) > 5 && str_contains($key, $alias)) {
                return $scientificName;
            }
        }

        return null;
    }

    private function speciesAliases(): array
    {
        if ($this->speciesAliases !== null) return $this->speciesAliases;

        $this->speciesAliases = [];

        CrabSpecies::query()
            ->where('is_supported', true)
            ->where('is_active', true)
            ->get(['common_name', 'scientific_name', 'local_name', 'model_class_name', 'model_class_id'])
            ->each(function (CrabSpecies $species) {
                foreach ([$species->common_name, $species->scientific_name, $species->local_name, $species->model_class_name] as $alias) {
                    if ($alias) $this->speciesAliases[$this->normalizeKey($alias)] = $species->scientific_name;
                }
            });

        return $this->speciesAliases;
    }

    private function speciesMeta(): array
    {
        if ($this->speciesMeta !== null) return $this->speciesMeta;

        $this->speciesMeta = CrabSpecies::query()
            ->where('is_supported', true)
            ->where('is_active', true)
            ->get(['common_name', 'scientific_name', 'model_class_id'])
            ->keyBy('scientific_name')
            ->map(fn (CrabSpecies $species) => [
                'common_name' => $species->common_name,
                'model_class_id' => $species->model_class_id,
            ])
            ->all();

        return $this->speciesMeta;
    }

    private function normalizeKey(string $value): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', ' ', strtolower($value)) ?: '');
    }

    private function prompt(): string
    {
        $species = CrabSpecies::query()
            ->where('is_supported', true)
            ->where('is_active', true)
            ->orderBy('common_name')
            ->get(['common_name', 'scientific_name'])
            ->map(fn (CrabSpecies $species) => "- {$species->common_name} ({$species->scientific_name})")
            ->implode("\n");

        return <<<PROMPT
You are a careful global crab recognition assistant. Inspect the image and identify the crab as accurately as possible worldwide, not only from the local supported list.

If the crab matches one of these local supported species, use its scientific name exactly:
{$species}

Return only valid JSON with this exact shape:
{
  "class_name": "best scientific name worldwide, or Unknown crab",
  "common_name": "best common name, or Unknown crab",
  "scientific_name": "best scientific name, or Unknown crab",
  "class_id": null,
  "confidence": 0.0,
  "family": null,
  "classification": null,
  "habitat": null,
  "visual_characteristics": null,
  "geographic_range": null,
  "edible_status": null,
  "caution_notes": null,
  "reference_hint": null,
  "quality_warnings": [],
  "blur_score": null,
  "brightness_score": null,
  "bounding_box": null
}
Use confidence from 0 to 1. Prefer scientific names. If the image is blurry, dark, partial, contains multiple animals, is not a crab, or cannot be identified beyond a broad group, return "Unknown crab" with low confidence and warnings. Include concise helpful habitat, range, visual traits, and safety caution when known. Do not include markdown or extra text.
PROMPT;
    }
}
