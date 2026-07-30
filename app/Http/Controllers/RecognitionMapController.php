<?php

namespace App\Http\Controllers;

use App\Models\CrabSpecies;
use App\Models\RecognitionRecord;
use App\Services\CrabRangeService;
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
                ->with(['species', 'user'])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

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

        $query = (clone $accessibleRecords)
            ->with(['species', 'user'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest();

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

        $filteredLocatedScans = (clone $query)->count();
        $records = $query->get();
        if ($focusedRecord && ! $records->contains(fn (RecognitionRecord $record) => $record->is($focusedRecord))) {
            $records->prepend($focusedRecord);
        }
        $sixProviderReliablePoints = $records->filter(fn (RecognitionRecord $record) => $this->isReliableConsensus($record))->count();
        $rangeLayers = $records
            ->map(fn (RecognitionRecord $record) => $rangeService->forRecord($record))
            ->filter()
            ->unique('key')
            ->values();

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
                'location' => $record->location_label,
                'coordinates' => number_format($record->latitude, 6).', '.number_format($record->longitude, 6),
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
