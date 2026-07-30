<?php

namespace App\Http\Controllers;

use App\Models\CrabSpecies;
use App\Models\RecognitionRecord;
use App\Services\CrabRangeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RecognitionMapController extends Controller
{
    public function index(Request $request, CrabRangeService $rangeService)
    {
        $accessibleRecords = RecognitionRecord::query();
        if (! $request->user()->isAdmin()) {
            $accessibleRecords->whereBelongsTo($request->user());
        }

        $totalScans = (clone $accessibleRecords)->count();
        $locatedScans = (clone $accessibleRecords)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->count();
        $latestLocatedRecord = (clone $accessibleRecords)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->first(['created_at']);

        $focusedRecord = null;
        if ($request->filled('scan')) {
            $focusedQuery = (clone $accessibleRecords)
                ->with(['species', 'user']);

            $scan = (string) $request->input('scan');
            $focusedRecord = $focusedQuery
                ->where(function ($query) use ($scan) {
                    $query->where('scan_reference', $scan);

                    if (ctype_digit($scan)) {
                        $query->orWhereKey((int) $scan);
                    }
                })
                ->first();
        }

        $rangeQuery = (clone $accessibleRecords)
            ->with(['species', 'user'])
            ->latest();
        $this->applyFilters($rangeQuery, $request);

        $matchingRecords = $rangeQuery->get();
        if ($focusedRecord && ! $matchingRecords->contains(fn (RecognitionRecord $record) => $record->is($focusedRecord))) {
            $matchingRecords->prepend($focusedRecord);
        }

        $query = (clone $accessibleRecords)
            ->with(['species', 'user'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest();
        $this->applyFilters($query, $request);

        $filteredLocatedScans = (clone $query)->count();
        $records = $query->get();
        if ($focusedRecord && $this->hasCoordinates($focusedRecord) && ! $records->contains(fn (RecognitionRecord $record) => $record->is($focusedRecord))) {
            $records->prepend($focusedRecord);
        }
        $sixProviderReliablePoints = $records->filter(fn (RecognitionRecord $record) => $this->isReliableConsensus($record))->count();
        $rangeLayers = $matchingRecords
            ->map(fn (RecognitionRecord $record) => $rangeService->forRecord($record))
            ->filter()
            ->unique('key')
            ->values();
        $possibleLocationCount = $this->possibleLocationCount($rangeLayers);

        return view('recognition_map.index', [
            'records' => $records,
            'focusedRecord' => $focusedRecord,
            'mapStats' => [
                'total_scans' => $totalScans,
                'located_scans' => $locatedScans,
                'missing_location_scans' => max(0, $totalScans - $locatedScans),
                'filtered_points' => $filteredLocatedScans,
                'six_provider_reliable_points' => $sixProviderReliablePoints,
                'global_range_layers' => $rangeLayers->count(),
                'possible_location_markers' => $possibleLocationCount,
                'range_source_scans' => $matchingRecords->count(),
                'required_provider_count' => max(1, (int) config('services.ai.required_provider_count', 6)),
                'minimum_provider_agreement' => max(1, (int) config('services.ai.min_provider_agreement', 4)),
                'latest_located_at' => $latestLocatedRecord?->created_at,
            ],
            'species' => CrabSpecies::where('is_active', true)->orderBy('common_name')->get(),
            'rangeLayers' => $rangeLayers,
            'points' => $records->map(fn (RecognitionRecord $record) => [
                'id' => $record->id,
                'reference' => $record->scan_reference,
                'species' => $record->species?->common_name ?? $record->predicted_class ?? 'Unknown',
                'confidence' => $record->confidence === null ? null : round($record->confidence * 100, 1),
                'level' => $record->confidence_level,
                'latitude' => $record->latitude,
                'longitude' => $record->longitude,
                'accuracy' => $record->location_accuracy_meters === null ? null : round($record->location_accuracy_meters, 1),
                'image_url' => $record->original_image_path ? route('recognition.image', $record) : null,
                'location' => $record->location_label,
                'coordinates' => number_format($record->latitude, 6).', '.number_format($record->longitude, 6),
                'location_reliability' => $this->locationReliability($record),
                'date' => $record->created_at->format('M d, Y g:i A'),
                'captured_by' => $request->user()->isAdmin() ? $record->user?->name : null,
                'status' => $record->recognition_status,
                'ai_consensus' => $this->consensusSummary($record),
                'global_range' => $rangeService->forRecord($record),
                'focused' => $focusedRecord?->is($record) ?? false,
                'url' => route('recognition.show', $record),
                'maps_url' => 'https://www.google.com/maps/search/?api=1&query='.rawurlencode(number_format($record->latitude, 7, '.', '').','.number_format($record->longitude, 7, '.', '')),
            ]),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): void
    {
        if ($request->filled('species')) {
            $query->where('crab_species_id', $request->species);
        }
        if ($request->filled('confidence')) {
            $query->where('confidence_level', $request->confidence);
        }
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
    }

    private function hasCoordinates(RecognitionRecord $record): bool
    {
        return $record->latitude !== null && $record->longitude !== null;
    }

    private function possibleLocationCount($rangeLayers): int
    {
        return $rangeLayers
            ->flatMap(fn (array $layer) => $layer['regions'] ?? [])
            ->flatMap(fn (array $region) => $region['possible_locations'] ?? [])
            ->unique(fn (array $location) => ($location['label'] ?? '').'|'.($location['latitude'] ?? '').'|'.($location['longitude'] ?? ''))
            ->count();
    }

    private function locationReliability(RecognitionRecord $record): string
    {
        if (! $this->hasCoordinates($record)) {
            return 'No exact GPS saved';
        }

        $accuracy = $record->location_accuracy_meters;
        if ($accuracy === null) {
            return 'Exact coordinates saved; accuracy radius unavailable';
        }

        if ($accuracy <= $this->maxDeviceAccuracyMeters()) {
            return 'Reliable GPS within +/- '.number_format($accuracy, 0).' m';
        }

        return 'Low-accuracy legacy GPS (+/- '.number_format($accuracy, 0).' m)';
    }

    private function maxDeviceAccuracyMeters(): float
    {
        return max(1.0, (float) config('services.location.max_device_accuracy_meters', 100));
    }

    private function consensusSummary(RecognitionRecord $record): array
    {
        $consensus = (array) data_get($record->ai_response, 'consensus', []);
        $providerResults = (array) data_get($record->ai_response, 'provider_results', []);
        $providerErrors = (array) data_get($consensus, 'provider_errors', []);
        $requiredProviderCount = max(1, (int) data_get($consensus, 'required_provider_count', config('services.ai.required_provider_count', 6)));
        $minimumAgreement = max(1, (int) data_get($consensus, 'minimum_required', config('services.ai.min_provider_agreement', 4)));
        $providerCount = (int) data_get($consensus, 'provider_count', count($providerResults) + count($providerErrors));
        $usableProviderCount = (int) data_get($consensus, 'usable_provider_count', collect($providerResults)->where('success', true)->count());
        $agreementCount = (int) data_get($consensus, 'agreement_count', 0);
        $coverageMet = (bool) data_get($consensus, 'provider_coverage_met', $providerCount >= $requiredProviderCount);
        $agreementMet = (bool) data_get($consensus, 'agreement_met', $agreementCount >= $minimumAgreement);
        $reliable = $coverageMet && $agreementMet && filled(data_get($consensus, 'selected_class_name', $record->predicted_class));

        return [
            'provider_count' => $providerCount,
            'required_provider_count' => $requiredProviderCount,
            'usable_provider_count' => $usableProviderCount,
            'agreement_count' => $agreementCount,
            'minimum_agreement' => $minimumAgreement,
            'provider_errors_count' => count($providerErrors),
            'coverage_met' => $coverageMet,
            'agreement_met' => $agreementMet,
            'reliable' => $reliable,
            'label' => $reliable ? 'Six-provider verified' : 'Needs AI review',
        ];
    }

    private function isReliableConsensus(RecognitionRecord $record): bool
    {
        return $this->consensusSummary($record)['reliable'];
    }
}
