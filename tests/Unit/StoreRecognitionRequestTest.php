<?php

namespace Tests\Unit;

use App\Http\Requests\StoreRecognitionRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreRecognitionRequestTest extends TestCase
{
    private const CAPTURE_CHECKS = [
        'one_crab',
        'bright_lighting',
        'whole_crab',
        'minimal_clutter',
        'no_covered_parts',
    ];

    public function test_valid_capture_checklist_passes_validation(): void
    {
        $validator = $this->validatorForPayload($this->validPayload());

        $this->assertFalse($validator->fails(), $validator->errors()->toJson());
    }

    public function test_capture_checklist_requires_every_item(): void
    {
        $payload = $this->validPayload([
            'capture_checks' => array_slice(self::CAPTURE_CHECKS, 0, 4),
        ]);

        $validator = $this->validatorForPayload($payload);

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('capture_checks', $validator->errors()->messages());
    }

    public function test_location_coordinates_must_be_submitted_as_a_pair(): void
    {
        $validator = $this->validatorForPayload($this->validPayload([
            'latitude' => '14.599512',
            'longitude' => null,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('longitude', $validator->errors()->messages());
    }

    public function test_location_is_required_for_map_integration(): void
    {
        $validator = $this->validatorForPayload($this->validPayload([
            'latitude' => null,
            'longitude' => null,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('latitude', $validator->errors()->messages());
        $this->assertArrayHasKey('longitude', $validator->errors()->messages());
    }

    public function test_location_accuracy_must_be_a_reasonable_meter_value(): void
    {
        $validator = $this->validatorForPayload($this->validPayload([
            'latitude' => '14.599512',
            'longitude' => '120.984222',
            'location_accuracy_meters' => '-5',
        ]));

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('location_accuracy_meters', $validator->errors()->messages());
    }

    private function validPayload(array $overrides = []): array
    {
        return array_replace([
            'image' => UploadedFile::fake()->image('crab.jpg', 640, 480),
            'latitude' => '14.599512',
            'longitude' => '120.984222',
            'location_accuracy_meters' => '18.5',
            'capture_checks' => self::CAPTURE_CHECKS,
        ], $overrides);
    }

    private function validatorForPayload(array $payload)
    {
        $file = $payload['image'];
        unset($payload['image']);

        $request = StoreRecognitionRequest::create('/recognition', 'POST', $payload, [], ['image' => $file]);
        $request->setContainer(app());

        $validator = Validator::make(array_merge($request->all(), $request->allFiles()), $request->rules());
        $request->withValidator($validator);

        return $validator;
    }
}
