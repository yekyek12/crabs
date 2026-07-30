<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;

class ImageLocationService
{
    public function fromUploadedFile(UploadedFile $image): ?array
    {
        return $this->fromPath($image->getRealPath() ?: '');
    }

    public function fromPath(string $path): ?array
    {
        if ($path === '' || ! is_file($path) || ! function_exists('exif_read_data')) {
            return null;
        }

        try {
            $exif = @exif_read_data($path, 'GPS', true);
        } catch (\Throwable) {
            return null;
        }

        if (! is_array($exif)) {
            return null;
        }

        $gps = (array) ($exif['GPS'] ?? $exif);
        $latitude = $this->coordinate($gps['GPSLatitude'] ?? null, $gps['GPSLatitudeRef'] ?? null);
        $longitude = $this->coordinate($gps['GPSLongitude'] ?? null, $gps['GPSLongitudeRef'] ?? null);

        if ($latitude === null || $longitude === null) {
            return null;
        }

        return [
            'latitude' => round($latitude, 7),
            'longitude' => round($longitude, 7),
            'accuracy' => $this->number($gps['GPSHPositioningError'] ?? null),
            'source' => 'image_exif',
            'label' => 'Image GPS metadata',
        ];
    }

    private function coordinate(mixed $parts, mixed $reference): ?float
    {
        if (! is_array($parts) || count($parts) < 3) {
            return null;
        }

        $degrees = $this->number($parts[0] ?? null);
        $minutes = $this->number($parts[1] ?? null);
        $seconds = $this->number($parts[2] ?? null);

        if ($degrees === null || $minutes === null || $seconds === null) {
            return null;
        }

        $coordinate = $degrees + ($minutes / 60) + ($seconds / 3600);
        $reference = strtoupper(trim((string) $reference));

        if (in_array($reference, ['S', 'W'], true)) {
            $coordinate *= -1;
        }

        return $coordinate;
    }

    private function number(mixed $value): ?float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        if (str_contains($value, '/')) {
            [$numerator, $denominator] = array_pad(explode('/', $value, 2), 2, null);
            if (! is_numeric($numerator) || ! is_numeric($denominator) || (float) $denominator == 0.0) {
                return null;
            }

            return (float) $numerator / (float) $denominator;
        }

        return is_numeric($value) ? (float) $value : null;
    }
}
