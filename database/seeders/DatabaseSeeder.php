<?php

namespace Database\Seeders;

use App\Models\CrabSpecies;
use App\Models\ModelVersion;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $forceDefaultSeeder = filter_var(env('FORCE_DEFAULT_SEEDER', false), FILTER_VALIDATE_BOOLEAN);

        if (! $forceDefaultSeeder && $this->hasExistingSeedData()) {
            $this->command?->info('Existing database data detected; skipping default seed data. Set FORCE_DEFAULT_SEEDER=true to reapply defaults.');

            return;
        }

        User::updateOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrator',
            'password' => 'password',
            'role' => 'admin',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        User::updateOrCreate(['email' => 'test@example.com'], [
            'name' => 'Test User',
            'password' => 'password',
            'role' => 'user',
            'account_status' => 'active',
            'email_verified_at' => now(),
        ]);

        $species = [
            [
                'common_name' => 'Giant Mud Crab',
                'scientific_name' => 'Scylla serrata',
                'family' => 'Portunidae',
                'classification' => 'Brachyura, Portunidae',
                'habitat' => 'Associated with Indo-Pacific estuaries, mangroves, tidal flats, and sheltered muddy areas.',
                'visual_characteristics' => 'Broad dark green to brown carapace, heavy claws, strong lateral spines, and flattened rear swimming legs.',
                'edible_status' => 'Commercially important edible crab; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Scylla%20serrata.jpg',
                'reference_name' => 'FAO Fisheries and Aquaculture',
                'reference_url' => 'https://www.fao.org/fishery/culturedspecies/scylla_serrata/en',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Scylla serrata',
                'model_class_id' => 1,
            ],
            [
                'common_name' => 'Blue Swimming Crab',
                'scientific_name' => 'Portunus pelagicus',
                'family' => 'Portunidae',
                'classification' => 'Brachyura, Portunidae',
                'habitat' => 'Tropical and subtropical coastal waters, often over sandy or muddy bottoms and seagrass areas.',
                'visual_characteristics' => 'Males often show vivid blue legs and claws, white-spotted carapace, long lateral spines, and paddle-shaped rear legs.',
                'edible_status' => 'Edible and widely fished; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Portunus%20pelagicus.jpg',
                'reference_name' => 'WoRMS / SeaLifeBase',
                'reference_url' => 'https://www.marinespecies.org/aphia.php?id=107404&p=taxdetails',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Portunus pelagicus',
                'model_class_id' => 2,
            ],
            [
                'common_name' => 'Atlantic Blue Crab',
                'scientific_name' => 'Callinectes sapidus',
                'family' => 'Portunidae',
                'classification' => 'Brachyura, Portunidae',
                'habitat' => 'Estuaries, underwater grasses, oyster reefs, and brackish to saltier coastal waters.',
                'visual_characteristics' => 'Olive to blue-green carapace, bright blue claws in many males, paddle-like rear legs, and pronounced side spines.',
                'edible_status' => 'Edible and economically important; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Callinectes%20sapidus.jpg',
                'reference_name' => 'NOAA Fisheries',
                'reference_url' => 'https://www.fisheries.noaa.gov/species/blue-crab',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Callinectes sapidus',
                'model_class_id' => 3,
            ],
            [
                'common_name' => 'Dungeness Crab',
                'scientific_name' => 'Cancer magister',
                'family' => 'Cancridae',
                'classification' => 'Brachyura, Cancridae',
                'habitat' => 'Nearshore and estuarine Pacific waters, commonly on sand, mud, gravel, and eelgrass-associated bottoms.',
                'visual_characteristics' => 'Wide oval brownish carapace with pale-tipped claws and no swimming paddles.',
                'edible_status' => 'Edible and commercially important; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Cancer%20magister.jpg',
                'reference_name' => 'California Marine Species Portal',
                'reference_url' => 'https://marinespecies.wildlife.ca.gov/dungeness-crab/',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Cancer magister',
                'model_class_id' => 4,
            ],
            [
                'common_name' => 'European Green Crab',
                'scientific_name' => 'Carcinus maenas',
                'family' => 'Carcinidae',
                'classification' => 'Brachyura, Carcinidae',
                'habitat' => 'Shallow nearshore and estuarine areas including mudflats, salt marshes, rocky shores, eelgrass, and sheltered beaches.',
                'visual_characteristics' => 'Small shore crab with five teeth behind each eye, variable green-brown coloration, and no paddle-like rear legs.',
                'edible_status' => 'Invasive in many regions; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Carcinus%20maenas.jpg',
                'reference_name' => 'Fisheries and Oceans Canada',
                'reference_url' => 'https://www.dfo-mpo.gc.ca/species-especes/profiles-profils/europeangreencrab-crabevert-eng.html',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Carcinus maenas',
                'model_class_id' => 5,
            ],
            [
                'common_name' => 'Red King Crab',
                'scientific_name' => 'Paralithodes camtschaticus',
                'family' => 'Lithodidae',
                'classification' => 'Anomura, Lithodidae',
                'habitat' => 'Cold North Pacific waters; juveniles use shallow complex habitats and adults often occur on deeper sandy or muddy bottoms.',
                'visual_characteristics' => 'Large spiny crab with long legs, burgundy-red tones, and a bulky armored carapace.',
                'edible_status' => 'Edible and commercially important; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Paralithodes%20camtschaticus%2C%201.jpg',
                'reference_name' => 'NOAA Fisheries',
                'reference_url' => 'https://www.fisheries.noaa.gov/species/red-king-crab',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Paralithodes camtschaticus',
                'model_class_id' => 6,
            ],
            [
                'common_name' => 'Three-Spot Swimming Crab',
                'scientific_name' => 'Portunus sanguinolentus',
                'family' => 'Portunidae',
                'classification' => 'Brachyura, Portunidae',
                'habitat' => 'Indo-Pacific coastal waters and offshore sandy or muddy bottoms.',
                'visual_characteristics' => 'Olive to dark green carapace with three prominent maroon to red spots on the posterior carapace.',
                'edible_status' => 'Edible portunid crab; this app does not certify food safety.',
                'reference_image_path' => 'https://commons.wikimedia.org/wiki/Special:FilePath/Portunus%20sanguinolentus.jpg',
                'reference_name' => 'SeaLifeBase',
                'reference_url' => 'https://www.sealifebase.ca/summary/Portunus-sanguinolentus.html',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Portunus sanguinolentus',
                'model_class_id' => 7,
            ],
            [
                'common_name' => 'Crucifix Crab',
                'scientific_name' => 'Charybdis feriata',
                'family' => 'Portunidae',
                'classification' => 'Brachyura, Portunidae',
                'habitat' => 'Tropical and subtropical waters, often reported from sandy-muddy bottoms and trawl grounds.',
                'visual_characteristics' => 'Bold cross-like red and cream markings on the carapace, swimming paddles, and robust portunid claws.',
                'edible_status' => 'Commercially used in parts of Asia; this app does not certify food safety.',
                'reference_image_path' => '/images/crab-species-fallback.svg',
                'reference_name' => 'SeaLifeBase',
                'reference_url' => 'https://www.sealifebase.ca/summary/Charybdis-feriata.html',
                'image_credit' => 'Wikimedia Commons',
                'model_class_name' => 'Charybdis feriata',
                'model_class_id' => 8,
            ],
        ];

        foreach ($species as $item) {
            CrabSpecies::updateOrCreate(['scientific_name' => $item['scientific_name']], $item + [
                'caution_notes' => 'Decision-support only; verify species with local fisheries or taxonomic references before use.',
                'is_supported' => true,
                'is_active' => true,
            ]);
        }

        ModelVersion::updateOrCreate(['name' => 'YOLO Crab Recognition Adapter', 'version' => 'placeholder-1.0.0'], [
            'description' => 'Placeholder adapter record. Replace after trained model metadata is available.',
            'classes' => collect($species)->map(fn ($item) => ['id' => $item['model_class_id'], 'name' => $item['model_class_name']])->all(),
            'confidence_threshold' => 0.600,
            'is_active' => true,
        ]);
    }

    private function hasExistingSeedData(): bool
    {
        return User::query()->exists()
            || CrabSpecies::withTrashed()->exists()
            || ModelVersion::query()->exists();
    }
}
