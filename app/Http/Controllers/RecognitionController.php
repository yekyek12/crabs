<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecognitionRequest;
use App\Models\CrabSpecies;
use App\Models\RecognitionRecord;
use App\Services\CrabRecognitionService;
use App\Services\ImageLocationService;
use App\Services\ImageQualityService;
use App\Services\RecognitionReferenceService;
use App\Services\RecognitionGuidanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class RecognitionController extends Controller
{
    public function create() { return view('recognition.create'); }

    public function store(StoreRecognitionRequest $request, ImageQualityService $quality, CrabRecognitionService $recognizer, ImageLocationService $imageLocation)
    {
        $image = $request->file('image');
        $location = $this->scanLocation($request, $imageLocation);
        $qualityResult = $quality->assess($image);
        $path = $image->store('recognitions/originals');
        $payload = null; $failure = null;
        try { $payload = $recognizer->predict($image); } catch (\Throwable $e) { report($e); $failure = $e->getMessage(); }

        $prediction = data_get($payload, 'prediction', []);
        $confidence = data_get($prediction, 'confidence');
        $predictedClass = data_get($prediction, 'class_name');
        $predictedClassId = data_get($prediction, 'class_id');
        $species = ($predictedClass || $predictedClassId) ? CrabSpecies::query()
            ->where(function ($query) use ($predictedClass, $predictedClassId) {
                if ($predictedClass) {
                    $query->where('scientific_name', $predictedClass)
                        ->orWhere('common_name', $predictedClass)
                        ->orWhere('model_class_name', $predictedClass);
                }

                if ($predictedClassId) {
                    $query->orWhere('model_class_id', $predictedClassId);
                }
            })
            ->first() : null;
        $status = $failure ? 'failed' : (($payload['success'] ?? false) ? 'recognized' : 'no_detection');
        if ($confidence !== null && RecognitionReferenceService::confidenceLevel((float) $confidence) === 'low') $status = 'low_confidence';

        $record = DB::transaction(fn () => RecognitionRecord::create([
            'user_id' => $request->user()->id,
            'crab_species_id' => $species?->id,
            'scan_reference' => RecognitionReferenceService::generate(),
            'original_image_path' => $path,
            'predicted_class' => $predictedClass,
            'confidence' => $confidence,
            'confidence_level' => RecognitionReferenceService::confidenceLevel($confidence === null ? null : (float) $confidence),
            'recognition_status' => $status,
            'blur_score' => data_get($payload, 'image_quality.blur_score', $qualityResult['blur_score']),
            'brightness_score' => data_get($payload, 'image_quality.brightness_score', $qualityResult['brightness_score']),
            'quality_warnings' => array_values(array_unique(array_merge($qualityResult['warnings'], data_get($payload, 'image_quality.warnings', [])))),
            'bounding_box' => data_get($prediction, 'bounding_box'),
            'processing_time_ms' => data_get($payload, 'processing_time_ms'),
            'model_name' => data_get($payload, 'model.name'),
            'model_version' => data_get($payload, 'model.version'),
            'ai_response' => $payload,
            'failure_reason' => $failure,
            'latitude' => $location['latitude'],
            'longitude' => $location['longitude'],
            'location_accuracy_meters' => $location['accuracy'],
            'location_label' => $request->input('location_label') ?: $location['label'],
            'capture_notes' => $request->input('capture_notes'),
        ]));

        return redirect()->route('recognition.show', $record);
    }

    public function show(RecognitionRecord $recognitionRecord, RecognitionGuidanceService $guidance)
    {
        abort_unless(auth()->user()->isAdmin() || $recognitionRecord->user_id === auth()->id(), 403);
        $recognitionRecord->load(['species', 'expertSpecies', 'feedback']);

        return view('recognition.show', ['record' => $recognitionRecord, 'guidance' => $guidance->for($recognitionRecord)]);
    }

    public function image(RecognitionRecord $recognitionRecord): Response
    {
        abort_unless(auth()->user()->isAdmin() || $recognitionRecord->user_id === auth()->id(), 403);
        abort_unless(Storage::exists($recognitionRecord->original_image_path), 404);
        return response(Storage::get($recognitionRecord->original_image_path), 200, ['Content-Type' => Storage::mimeType($recognitionRecord->original_image_path) ?? 'image/jpeg']);
    }

    public function destroy(RecognitionRecord $recognitionRecord)
    {
        abort_unless($recognitionRecord->user_id === auth()->id(), 403);

        $paths = collect([$recognitionRecord->original_image_path, $recognitionRecord->annotated_image_path])
            ->filter()
            ->all();

        if ($paths !== []) {
            Storage::delete($paths);
        }

        $recognitionRecord->delete();
        return redirect()->route('recognition.history')->with('status', 'Recognition record deleted.');
    }

    private function scanLocation(StoreRecognitionRequest $request, ImageLocationService $imageLocation): array
    {
        if ($request->filled('latitude') && $request->filled('longitude')) {
            $accuracy = $request->filled('location_accuracy_meters')
                ? round((float) $request->input('location_accuracy_meters'), 2)
                : null;

            if ($accuracy === null || $accuracy > $this->maxDeviceAccuracyMeters()) {
                throw ValidationException::withMessages([
                    'latitude' => 'GPS signal is too broad for a reliable scan location. Move outdoors, enable precise location, and try again.',
                    'longitude' => 'GPS signal is too broad for a reliable scan location. Move outdoors, enable precise location, and try again.',
                    'location_accuracy_meters' => 'GPS accuracy must be within '.number_format($this->maxDeviceAccuracyMeters(), 0).' meters.',
                ]);
            }

            return [
                'latitude' => (float) $request->input('latitude'),
                'longitude' => (float) $request->input('longitude'),
                'accuracy' => $accuracy,
                'label' => null,
            ];
        }

        $metadataLocation = $imageLocation->fromUploadedFile($request->file('image'));
        if ($metadataLocation !== null) {
            if ($metadataLocation['accuracy'] !== null && (float) $metadataLocation['accuracy'] > $this->maxDeviceAccuracyMeters()) {
                throw ValidationException::withMessages([
                    'latitude' => 'Photo GPS metadata is too broad for a reliable scan location. Use live device location or a more precise GPS-tagged photo.',
                    'longitude' => 'Photo GPS metadata is too broad for a reliable scan location. Use live device location or a more precise GPS-tagged photo.',
                ]);
            }

            return $metadataLocation;
        }

        throw ValidationException::withMessages([
            'latitude' => 'Attach device GPS location or upload a photo that contains GPS metadata before analysis.',
        ]);
    }

    private function maxDeviceAccuracyMeters(): float
    {
        return max(1.0, (float) config('services.location.max_device_accuracy_meters', 100));
    }
}
