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

    private const POSSIBLE_LOCATIONS_BY_REGION = [
        'Indo-West Pacific' => [
            ['label' => 'New Buswang, Kalibo, Aklan, Philippines', 'type' => 'Barangay', 'admin_area' => 'Kalibo, Aklan', 'country' => 'Philippines', 'latitude' => 11.7044, 'longitude' => 122.3841, 'note' => 'Mangrove and brackish-water habitat example inside this species range.'],
            ['label' => 'Sasmuan, Pampanga, Philippines', 'type' => 'Municipality', 'admin_area' => 'Pampanga', 'country' => 'Philippines', 'latitude' => 14.9439, 'longitude' => 120.6229, 'note' => 'Estuary, fishpond, and coastal wetland example inside this range.'],
            ['label' => 'Roxas City, Capiz, Philippines', 'type' => 'City', 'admin_area' => 'Capiz', 'country' => 'Philippines', 'latitude' => 11.5853, 'longitude' => 122.7511, 'note' => 'Coastal market and nearshore habitat example inside this range.'],
            ['label' => 'Panabo City, Davao del Norte, Philippines', 'type' => 'City', 'admin_area' => 'Davao del Norte', 'country' => 'Philippines', 'latitude' => 7.3081, 'longitude' => 125.6841, 'note' => 'Mangrove and aquaculture area example inside this range.'],
            ['label' => 'Can Gio, Ho Chi Minh City, Vietnam', 'type' => 'District', 'admin_area' => 'Ho Chi Minh City', 'country' => 'Vietnam', 'latitude' => 10.4114, 'longitude' => 106.9547, 'note' => 'Mangrove-estuary example inside this range.'],
            ['label' => 'Satun, Thailand', 'type' => 'Province', 'admin_area' => 'Satun', 'country' => 'Thailand', 'latitude' => 6.6238, 'longitude' => 100.0674, 'note' => 'Andaman Sea coastal habitat example inside this range.'],
            ['label' => 'Sundarbans coastal delta', 'type' => 'Delta region', 'admin_area' => 'Bangladesh / India', 'country' => 'Bangladesh / India', 'latitude' => 21.9497, 'longitude' => 89.1833, 'note' => 'Large mangrove delta example inside this range.'],
            ['label' => 'Darwin Harbour, Northern Territory, Australia', 'type' => 'Harbour', 'admin_area' => 'Northern Territory', 'country' => 'Australia', 'latitude' => -12.47, 'longitude' => 130.8456, 'note' => 'Tropical northern Australia coastal example inside this range.'],
        ],
        'Indo-Pacific' => [
            ['label' => 'New Buswang, Kalibo, Aklan, Philippines', 'type' => 'Barangay', 'admin_area' => 'Kalibo, Aklan', 'country' => 'Philippines', 'latitude' => 11.7044, 'longitude' => 122.3841, 'note' => 'Mangrove and brackish-water habitat example inside this species range.'],
            ['label' => 'Sasmuan, Pampanga, Philippines', 'type' => 'Municipality', 'admin_area' => 'Pampanga', 'country' => 'Philippines', 'latitude' => 14.9439, 'longitude' => 120.6229, 'note' => 'Estuary, fishpond, and coastal wetland example inside this range.'],
            ['label' => 'Bali coastal waters, Indonesia', 'type' => 'Province', 'admin_area' => 'Bali', 'country' => 'Indonesia', 'latitude' => -8.4095, 'longitude' => 115.1889, 'note' => 'Tropical coastal example inside the broader Indo-Pacific range.'],
            ['label' => 'Gulf of Mannar, Sri Lanka', 'type' => 'Gulf', 'admin_area' => 'Northern / Eastern Sri Lanka', 'country' => 'Sri Lanka', 'latitude' => 8.75, 'longitude' => 79.15, 'note' => 'Shallow coastal habitat example inside this range.'],
            ['label' => 'Zanzibar coastal waters, Tanzania', 'type' => 'Island region', 'admin_area' => 'Zanzibar', 'country' => 'Tanzania', 'latitude' => -6.1659, 'longitude' => 39.2026, 'note' => 'Western Indian Ocean example inside this range.'],
            ['label' => 'Queensland tropical coast, Australia', 'type' => 'State coast', 'admin_area' => 'Queensland', 'country' => 'Australia', 'latitude' => -16.92, 'longitude' => 145.78, 'note' => 'Tropical coastal example inside this range.'],
        ],
        'Western Atlantic' => [
            ['label' => 'Chesapeake Bay, Maryland, USA', 'type' => 'Bay', 'admin_area' => 'Maryland / Virginia', 'country' => 'United States', 'latitude' => 38.5, 'longitude' => -76.3, 'note' => 'Estuary example inside the western Atlantic range.'],
            ['label' => 'Mobile Bay, Alabama, USA', 'type' => 'Bay', 'admin_area' => 'Alabama', 'country' => 'United States', 'latitude' => 30.62, 'longitude' => -87.95, 'note' => 'Gulf Coast estuary example inside this range.'],
            ['label' => 'Apalachicola Bay, Florida, USA', 'type' => 'Bay', 'admin_area' => 'Florida', 'country' => 'United States', 'latitude' => 29.72, 'longitude' => -84.98, 'note' => 'Gulf Coast bay example inside this range.'],
            ['label' => 'Laguna Madre, Texas, USA', 'type' => 'Lagoon', 'admin_area' => 'Texas', 'country' => 'United States', 'latitude' => 26.5, 'longitude' => -97.33, 'note' => 'Lagoon example inside this range.'],
            ['label' => 'Veracruz coastal waters, Mexico', 'type' => 'State coast', 'admin_area' => 'Veracruz', 'country' => 'Mexico', 'latitude' => 19.18, 'longitude' => -96.13, 'note' => 'Western Gulf of Mexico example inside this range.'],
            ['label' => 'Lake Maracaibo coast, Venezuela', 'type' => 'Lake / lagoon coast', 'admin_area' => 'Zulia', 'country' => 'Venezuela', 'latitude' => 10.64, 'longitude' => -71.61, 'note' => 'Tropical western Atlantic-connected coastal example inside this range.'],
        ],
        'Eastern Atlantic and Mediterranean introductions' => [
            ['label' => 'Ebro Delta, Tarragona, Spain', 'type' => 'Delta', 'admin_area' => 'Catalonia', 'country' => 'Spain', 'latitude' => 40.72, 'longitude' => 0.73, 'note' => 'Eastern Atlantic / Mediterranean introduced-range example.'],
            ['label' => 'Venice Lagoon, Veneto, Italy', 'type' => 'Lagoon', 'admin_area' => 'Veneto', 'country' => 'Italy', 'latitude' => 45.44, 'longitude' => 12.33, 'note' => 'Mediterranean lagoon example inside this introduced range.'],
            ['label' => 'Izmir Bay, Turkey', 'type' => 'Bay', 'admin_area' => 'Izmir', 'country' => 'Turkey', 'latitude' => 38.45, 'longitude' => 27.1, 'note' => 'Aegean coast example inside this introduced range.'],
            ['label' => 'Nile Delta coast, Egypt', 'type' => 'Delta coast', 'admin_area' => 'Nile Delta', 'country' => 'Egypt', 'latitude' => 31.2, 'longitude' => 30.0, 'note' => 'Southeast Mediterranean coastal example inside this introduced range.'],
        ],
        'Mediterranean Sea' => [
            ['label' => 'Ebro Delta, Tarragona, Spain', 'type' => 'Delta', 'admin_area' => 'Catalonia', 'country' => 'Spain', 'latitude' => 40.72, 'longitude' => 0.73, 'note' => 'Mediterranean coastal wetland example.'],
            ['label' => 'Venice Lagoon, Veneto, Italy', 'type' => 'Lagoon', 'admin_area' => 'Veneto', 'country' => 'Italy', 'latitude' => 45.44, 'longitude' => 12.33, 'note' => 'Mediterranean lagoon example.'],
            ['label' => 'Izmir Bay, Turkey', 'type' => 'Bay', 'admin_area' => 'Izmir', 'country' => 'Turkey', 'latitude' => 38.45, 'longitude' => 27.1, 'note' => 'Aegean coast example.'],
            ['label' => 'Nile Delta coast, Egypt', 'type' => 'Delta coast', 'admin_area' => 'Nile Delta', 'country' => 'Egypt', 'latitude' => 31.2, 'longitude' => 30.0, 'note' => 'Southeast Mediterranean coastal example.'],
        ],
        'Northeast Pacific' => [
            ['label' => 'Puget Sound, Washington, USA', 'type' => 'Sound', 'admin_area' => 'Washington', 'country' => 'United States', 'latitude' => 47.6, 'longitude' => -122.33, 'note' => 'Temperate northeast Pacific coast example.'],
            ['label' => 'Grays Harbor, Washington, USA', 'type' => 'Harbor', 'admin_area' => 'Washington', 'country' => 'United States', 'latitude' => 46.97, 'longitude' => -123.85, 'note' => 'Coastal bay example inside this range.'],
            ['label' => 'Newport, Oregon, USA', 'type' => 'City', 'admin_area' => 'Oregon', 'country' => 'United States', 'latitude' => 44.64, 'longitude' => -124.05, 'note' => 'Oregon coast example inside this range.'],
            ['label' => 'Humboldt Bay, California, USA', 'type' => 'Bay', 'admin_area' => 'California', 'country' => 'United States', 'latitude' => 40.75, 'longitude' => -124.2, 'note' => 'Northern California bay example inside this range.'],
            ['label' => 'Bodega Bay, California, USA', 'type' => 'Bay', 'admin_area' => 'California', 'country' => 'United States', 'latitude' => 38.33, 'longitude' => -123.05, 'note' => 'California coastal example inside this range.'],
            ['label' => 'Vancouver Island coast, British Columbia, Canada', 'type' => 'Island coast', 'admin_area' => 'British Columbia', 'country' => 'Canada', 'latitude' => 49.3, 'longitude' => -124.0, 'note' => 'Canadian northeast Pacific example inside this range.'],
        ],
        'Northeast Atlantic and Europe' => [
            ['label' => 'Galway Bay, Ireland', 'type' => 'Bay', 'admin_area' => 'County Galway', 'country' => 'Ireland', 'latitude' => 53.2, 'longitude' => -9.0, 'note' => 'Northeast Atlantic bay example inside this range.'],
            ['label' => 'Wadden Sea coast', 'type' => 'Coastal region', 'admin_area' => 'Netherlands / Germany / Denmark', 'country' => 'Europe', 'latitude' => 53.5, 'longitude' => 6.5, 'note' => 'Intertidal coast example inside this range.'],
            ['label' => 'Thames Estuary, England', 'type' => 'Estuary', 'admin_area' => 'England', 'country' => 'United Kingdom', 'latitude' => 51.5, 'longitude' => 0.7, 'note' => 'Estuary example inside this range.'],
            ['label' => 'Ria de Aveiro, Portugal', 'type' => 'Lagoon / estuary', 'admin_area' => 'Aveiro', 'country' => 'Portugal', 'latitude' => 40.64, 'longitude' => -8.73, 'note' => 'Atlantic lagoon example inside this range.'],
            ['label' => 'Oslofjord, Norway', 'type' => 'Fjord', 'admin_area' => 'Oslofjord', 'country' => 'Norway', 'latitude' => 59.0, 'longitude' => 10.7, 'note' => 'Northern Europe coast example inside this range.'],
        ],
        'Eastern North America' => [
            ['label' => 'Gulf of Maine, USA', 'type' => 'Gulf', 'admin_area' => 'Maine / New Hampshire / Massachusetts', 'country' => 'United States', 'latitude' => 43.5, 'longitude' => -70.0, 'note' => 'Northwest Atlantic coast example inside this invaded range.'],
            ['label' => 'Long Island Sound, New York, USA', 'type' => 'Sound', 'admin_area' => 'New York / Connecticut', 'country' => 'United States', 'latitude' => 41.0, 'longitude' => -72.7, 'note' => 'Estuary example inside this invaded range.'],
            ['label' => 'Barnegat Bay, New Jersey, USA', 'type' => 'Bay', 'admin_area' => 'New Jersey', 'country' => 'United States', 'latitude' => 39.8, 'longitude' => -74.1, 'note' => 'Coastal lagoon example inside this invaded range.'],
            ['label' => 'Halifax Harbour, Nova Scotia, Canada', 'type' => 'Harbour', 'admin_area' => 'Nova Scotia', 'country' => 'Canada', 'latitude' => 44.65, 'longitude' => -63.57, 'note' => 'Canadian Atlantic coast example inside this invaded range.'],
        ],
        'Western North America' => [
            ['label' => 'San Francisco Bay, California, USA', 'type' => 'Bay', 'admin_area' => 'California', 'country' => 'United States', 'latitude' => 37.77, 'longitude' => -122.38, 'note' => 'Pacific coast bay example inside this invaded range.'],
            ['label' => 'Coos Bay, Oregon, USA', 'type' => 'Bay', 'admin_area' => 'Oregon', 'country' => 'United States', 'latitude' => 43.37, 'longitude' => -124.22, 'note' => 'Oregon coast example inside this invaded range.'],
            ['label' => 'Victoria Harbour, British Columbia, Canada', 'type' => 'Harbour', 'admin_area' => 'British Columbia', 'country' => 'Canada', 'latitude' => 48.42, 'longitude' => -123.37, 'note' => 'Canadian Pacific coast example inside this invaded range.'],
        ],
        'Southern Australia and New Zealand' => [
            ['label' => 'Port Phillip Bay, Victoria, Australia', 'type' => 'Bay', 'admin_area' => 'Victoria', 'country' => 'Australia', 'latitude' => -38.0, 'longitude' => 144.9, 'note' => 'Southern Australia bay example inside this invaded range.'],
            ['label' => 'Tamar River estuary, Tasmania, Australia', 'type' => 'Estuary', 'admin_area' => 'Tasmania', 'country' => 'Australia', 'latitude' => -41.3, 'longitude' => 146.9, 'note' => 'Tasmanian estuary example inside this invaded range.'],
            ['label' => 'Otago Harbour, New Zealand', 'type' => 'Harbour', 'admin_area' => 'Otago', 'country' => 'New Zealand', 'latitude' => -45.82, 'longitude' => 170.64, 'note' => 'New Zealand harbour example inside this invaded range.'],
            ['label' => 'Lyttelton Harbour, New Zealand', 'type' => 'Harbour', 'admin_area' => 'Canterbury', 'country' => 'New Zealand', 'latitude' => -43.61, 'longitude' => 172.72, 'note' => 'New Zealand harbour example inside this invaded range.'],
        ],
        'South Africa' => [
            ['label' => 'Table Bay, Western Cape, South Africa', 'type' => 'Bay', 'admin_area' => 'Western Cape', 'country' => 'South Africa', 'latitude' => -33.9, 'longitude' => 18.44, 'note' => 'South African coast example inside this invaded range.'],
            ['label' => 'Saldanha Bay, Western Cape, South Africa', 'type' => 'Bay', 'admin_area' => 'Western Cape', 'country' => 'South Africa', 'latitude' => -33.02, 'longitude' => 17.94, 'note' => 'South African bay example inside this invaded range.'],
        ],
        'Southern South America' => [
            ['label' => 'Puerto Madryn, Chubut, Argentina', 'type' => 'City coast', 'admin_area' => 'Chubut', 'country' => 'Argentina', 'latitude' => -42.77, 'longitude' => -65.03, 'note' => 'Southern South America coast example inside this invaded range.'],
            ['label' => 'Beagle Channel, Tierra del Fuego', 'type' => 'Channel', 'admin_area' => 'Tierra del Fuego', 'country' => 'Argentina / Chile', 'latitude' => -54.8, 'longitude' => -68.3, 'note' => 'Cold southern channel example inside this range.'],
        ],
        'Northwest Pacific' => [
            ['label' => 'Kamchatka Peninsula coast, Russia', 'type' => 'Peninsula coast', 'admin_area' => 'Kamchatka', 'country' => 'Russia', 'latitude' => 53.0, 'longitude' => 158.0, 'note' => 'Cold northwest Pacific example inside this range.'],
            ['label' => 'Hokkaido coast, Japan', 'type' => 'Island coast', 'admin_area' => 'Hokkaido', 'country' => 'Japan', 'latitude' => 43.0, 'longitude' => 145.0, 'note' => 'Cold northwest Pacific example inside this range.'],
            ['label' => 'Sea of Okhotsk coast', 'type' => 'Sea coast', 'admin_area' => 'Russian Far East', 'country' => 'Russia', 'latitude' => 55.0, 'longitude' => 145.0, 'note' => 'Cold northwest Pacific example inside this range.'],
        ],
        'Northeast Pacific and Bering Sea' => [
            ['label' => 'Dutch Harbor, Alaska, USA', 'type' => 'Harbour', 'admin_area' => 'Alaska', 'country' => 'United States', 'latitude' => 53.88, 'longitude' => -166.54, 'note' => 'Bering Sea coast example inside this range.'],
            ['label' => 'Kodiak Island, Alaska, USA', 'type' => 'Island', 'admin_area' => 'Alaska', 'country' => 'United States', 'latitude' => 57.79, 'longitude' => -152.41, 'note' => 'Alaska coast example inside this range.'],
            ['label' => 'Bristol Bay, Alaska, USA', 'type' => 'Bay', 'admin_area' => 'Alaska', 'country' => 'United States', 'latitude' => 58.75, 'longitude' => -157.0, 'note' => 'Bering Sea bay example inside this range.'],
        ],
        'Barents Sea introduction' => [
            ['label' => 'Kirkenes, Finnmark, Norway', 'type' => 'Town / fjord coast', 'admin_area' => 'Finnmark', 'country' => 'Norway', 'latitude' => 69.73, 'longitude' => 30.04, 'note' => 'Barents Sea introduced-range example.'],
            ['label' => 'Murmansk coast, Russia', 'type' => 'City coast', 'admin_area' => 'Murmansk Oblast', 'country' => 'Russia', 'latitude' => 68.97, 'longitude' => 33.08, 'note' => 'Barents Sea introduced-range example.'],
        ],
        'North Pacific' => [
            ['label' => 'Kamchatka Peninsula coast, Russia', 'type' => 'Peninsula coast', 'admin_area' => 'Kamchatka', 'country' => 'Russia', 'latitude' => 53.0, 'longitude' => 158.0, 'note' => 'North Pacific example inside this broad range.'],
            ['label' => 'Hokkaido coast, Japan', 'type' => 'Island coast', 'admin_area' => 'Hokkaido', 'country' => 'Japan', 'latitude' => 43.0, 'longitude' => 145.0, 'note' => 'North Pacific example inside this broad range.'],
            ['label' => 'Dutch Harbor, Alaska, USA', 'type' => 'Harbour', 'admin_area' => 'Alaska', 'country' => 'United States', 'latitude' => 53.88, 'longitude' => -166.54, 'note' => 'North Pacific example inside this broad range.'],
            ['label' => 'Puget Sound, Washington, USA', 'type' => 'Sound', 'admin_area' => 'Washington', 'country' => 'United States', 'latitude' => 47.6, 'longitude' => -122.33, 'note' => 'North Pacific coast example inside this broad range.'],
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
            'regions' => $this->withPossibleLocations($range['regions']),
        ];
    }

    private function withPossibleLocations(array $regions): array
    {
        return array_map(function (array $region): array {
            $label = (string) ($region['label'] ?? '');
            $locations = self::POSSIBLE_LOCATIONS_BY_REGION[$label] ?? [];

            if ($label === 'Northwest Pacific' || $label === 'Northeast Pacific') {
                $locations = array_merge($locations, self::POSSIBLE_LOCATIONS_BY_REGION['North Pacific'] ?? []);
            }

            $region['possible_locations'] = array_values($this->locationsInsideBounds($locations, $region['bounds'] ?? null));

            return $region;
        }, $regions);
    }

    private function locationsInsideBounds(array $locations, mixed $bounds): array
    {
        if (! is_array($bounds) || count($bounds) !== 2) {
            return $locations;
        }

        $south = min((float) $bounds[0][0], (float) $bounds[1][0]);
        $north = max((float) $bounds[0][0], (float) $bounds[1][0]);
        $west = min((float) $bounds[0][1], (float) $bounds[1][1]);
        $east = max((float) $bounds[0][1], (float) $bounds[1][1]);

        return array_filter($locations, function (array $location) use ($south, $north, $west, $east): bool {
            $latitude = (float) ($location['latitude'] ?? 999);
            $longitude = (float) ($location['longitude'] ?? 999);

            return $latitude >= $south
                && $latitude <= $north
                && $longitude >= $west
                && $longitude <= $east;
        });
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
