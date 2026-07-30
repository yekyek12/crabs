<?php

use App\Models\RecognitionRecord;
use App\Services\ImageLocationService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('recognitions:backfill-gps {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');
    $checked = 0;
    $updated = 0;
    $missingGps = 0;
    $missingFile = 0;
    $locations = app(ImageLocationService::class);

    RecognitionRecord::query()
        ->where(function ($query) {
            $query->whereNull('latitude')->orWhereNull('longitude');
        })
        ->whereNotNull('original_image_path')
        ->orderBy('id')
        ->each(function (RecognitionRecord $record) use ($dryRun, $locations, &$checked, &$updated, &$missingGps, &$missingFile) {
            $checked++;

            if (! Storage::exists($record->original_image_path)) {
                $missingFile++;
                return;
            }

            $location = $locations->fromPath(Storage::path($record->original_image_path));
            if ($location === null) {
                $missingGps++;
                return;
            }

            if (! $dryRun) {
                $record->forceFill([
                    'latitude' => $location['latitude'],
                    'longitude' => $location['longitude'],
                    'location_accuracy_meters' => $record->location_accuracy_meters ?? $location['accuracy'],
                    'location_label' => $record->location_label ?: $location['label'],
                ])->save();
            }

            $updated++;
        });

    $this->info(($dryRun ? 'Would update' : 'Updated')." {$updated} recognition record(s).");
    $this->line("Checked: {$checked}");
    $this->line("Images without GPS metadata: {$missingGps}");
    $this->line("Missing image files: {$missingFile}");
})->purpose('Backfill missing recognition GPS coordinates from image EXIF metadata');
