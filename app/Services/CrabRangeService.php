<?php

namespace App\Services;

use App\Models\RecognitionRecord;
use Illuminate\Support\Str;

class CrabRangeService
{
    private const CURATED_RANGES = [
        'scylla serrata' => [
            'label' => 'Indo-West Pacific mangrove and estuary range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Indo-West Pacific', 'bounds' => [[-35, 30], [32, 180]]],
            ],
        ],
        'portunus pelagicus' => [
            'label' => 'Indo-West Pacific tropical and subtropical coastal range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Indo-West Pacific', 'bounds' => [[-35, 32], [35, 180]]],
            ],
        ],
        'callinectes sapidus' => [
            'label' => 'Western Atlantic native range with introduced eastern Atlantic and Mediterranean records',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Western Atlantic', 'bounds' => [[-35, -100], [48, -45]]],
                ['label' => 'Eastern Atlantic and Mediterranean introductions', 'bounds' => [[30, -12], [46, 38]]],
            ],
        ],
        'cancer magister' => [
            'label' => 'Northeast Pacific coast range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Northeast Pacific', 'bounds' => [[31, -180], [62, -117]]],
            ],
        ],
        'metacarcinus magister' => [
            'label' => 'Northeast Pacific coast range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Northeast Pacific', 'bounds' => [[31, -180], [62, -117]]],
            ],
        ],
        'carcinus maenas' => [
            'label' => 'Native northeast Atlantic range plus major invaded temperate coastlines',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Northeast Atlantic and Europe', 'bounds' => [[30, -25], [70, 45]]],
                ['label' => 'Eastern North America', 'bounds' => [[30, -82], [50, -55]]],
                ['label' => 'Western North America', 'bounds' => [[30, -130], [55, -115]]],
                ['label' => 'Southern Australia and New Zealand', 'bounds' => [[-46, 110], [-30, 180]]],
                ['label' => 'South Africa', 'bounds' => [[-36, 16], [-30, 30]]],
                ['label' => 'Southern South America', 'bounds' => [[-55, -75], [-35, -55]]],
            ],
        ],
        'paralithodes camtschaticus' => [
            'label' => 'Cold North Pacific range plus Barents Sea introduction',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Northwest Pacific', 'bounds' => [[42, 125], [72, 180]]],
                ['label' => 'Northeast Pacific and Bering Sea', 'bounds' => [[50, -180], [72, -125]]],
                ['label' => 'Barents Sea introduction', 'bounds' => [[66, 15], [78, 60]]],
            ],
        ],
        'portunus sanguinolentus' => [
            'label' => 'Indo-West Pacific coastal and offshore range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Indo-West Pacific', 'bounds' => [[-35, 35], [35, 180]]],
            ],
        ],
        'charybdis feriata' => [
            'label' => 'Indo-West Pacific tropical and subtropical range',
            'source' => 'supported_species_reference',
            'regions' => [
                ['label' => 'Indo-West Pacific', 'bounds' => [[-35, 35], [35, 155]]],
            ],
        ],
    ];

    private const KEYWORD_RANGES = [
        'indo pacific' => [
            'label' => 'Possible Indo-Pacific range',
            'source' => 'ai_range_text',
            'regions' => [
                ['label' => 'Indo-Pacific', 'bounds' => [[-35, 30], [35, 180]]],
            ],
        ],
        'indo west pacific' => [
            'label' => 'Possible Indo-West Pacific range',
            'source' => 'ai_range_text',
            'regions' => [
                ['label' => 'Indo-West Pacific', 'bounds' => [[-35, 30], [35, 180]]],
            ],
        ],
        'western atlantic' => [
            'label' => 'Possible western Atlantic range',
            'source' => 'ai_range_text',
            'regions' => [
                ['label' => 'Western Atlantic', 'bounds' => [[-40, -100], [50, -45]]],
            ],
        ],
        'north pacific' => [
            'label' => 'Possible North Pacific range',
            'source' => 'ai_range_text',
            'regions' => [
                ['label' => 'Northwest Pacific', 'bounds' => [[35, 120], [72, 180]]],
                ['label' => 'Northeast Pacific', 'bounds' => [[35, -180], [72, -115]]],
            ],
        ],
        'mediterranean' => [
            'label' => 'Possible Mediterranean range',
            'source' => 'ai_range_text',
            'regions' => [
                ['label' => 'Mediterranean Sea', 'bounds' => [[30, -6], [46, 37]]],
            ],
        ],
    ];

    public function forRecord(RecognitionRecord $record): ?array
    {
        $scientificName = $this->scientificName($record);
        $rangeText = trim((string) data_get($record->ai_response, 'global_species.geographic_range', ''));
        $key = $this->normalizeKey($scientificName);
        $range = self::CURATED_RANGES[$key] ?? $this->rangeFromText($rangeText);

        if ($range === null) {
            return $rangeText === '' ? null : [
                'key' => 'text-'.md5($scientificName.'|'.$rangeText),
                'species' => $record->species?->common_name ?? data_get($record->ai_response, 'global_species.common_name') ?? $record->predicted_class ?? 'Unknown crab',
                'scientific_name' => $scientificName ?: 'Unknown',
                'label' => 'AI-described possible range',
                'source' => 'ai_range_text',
                'range_text' => $rangeText,
                'regions' => [],
            ];
        }

        return [
            'key' => $key ?: 'range-'.md5($scientificName.'|'.$rangeText),
            'species' => $record->species?->common_name ?? data_get($record->ai_response, 'global_species.common_name') ?? $record->predicted_class ?? 'Unknown crab',
            'scientific_name' => $scientificName ?: 'Unknown',
            'label' => $range['label'],
            'source' => $range['source'],
            'range_text' => $rangeText,
            'regions' => $range['regions'],
        ];
    }

    private function rangeFromText(string $rangeText): ?array
    {
        $key = $this->normalizeKey($rangeText);
        if ($key === '') {
            return null;
        }

        foreach (self::KEYWORD_RANGES as $keyword => $range) {
            if (str_contains($key, $keyword)) {
                return $range;
            }
        }

        return null;
    }

    private function scientificName(RecognitionRecord $record): string
    {
        return trim((string) (
            $record->species?->scientific_name
            ?? data_get($record->ai_response, 'global_species.scientific_name')
            ?? $record->predicted_class
            ?? ''
        ));
    }

    private function normalizeKey(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
