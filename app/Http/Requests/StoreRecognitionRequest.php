<?php

namespace App\Http\Requests;

use App\Services\ImageLocationService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

class StoreRecognitionRequest extends FormRequest
{
    private const CAPTURE_CHECKS = [
        'one_crab',
        'bright_lighting',
        'whole_crab',
        'minimal_clutter',
        'no_covered_parts',
    ];

    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'mimetypes:image/jpeg,image/png,image/webp', 'max:8192', 'dimensions:min_width=320,min_height=240,max_width=6000,max_height=6000'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'location_accuracy_meters' => ['nullable', 'numeric', 'between:0,100000'],
            'location_label' => ['nullable', 'string', 'max:255'],
            'capture_notes' => ['nullable', 'string', 'max:1000'],
            'capture_checks' => ['required', 'array', 'size:'.count(self::CAPTURE_CHECKS)],
            'capture_checks.*' => ['required', 'string', Rule::in(self::CAPTURE_CHECKS), 'distinct'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if ($validator->errors()->has('image')) {
                return;
            }

            $hasCoordinates = filled($this->input('latitude')) && filled($this->input('longitude'));
            if ($hasCoordinates) {
                return;
            }

            $image = $this->file('image');
            $hasImageGps = $image instanceof \Illuminate\Http\UploadedFile
                && app(ImageLocationService::class)->fromUploadedFile($image) !== null;

            if (! $hasImageGps) {
                $message = 'Attach device GPS location or upload a photo that contains GPS metadata before analysis.';
                $validator->errors()->add('latitude', $message);
                $validator->errors()->add('longitude', $message);
            }
        });
    }
}
