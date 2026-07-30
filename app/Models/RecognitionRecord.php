<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class RecognitionRecord extends Model
{
    use SoftDeletes;

    protected $fillable = ['user_id', 'crab_species_id', 'scan_reference', 'original_image_path', 'annotated_image_path', 'predicted_class', 'confidence', 'confidence_level', 'recognition_status', 'blur_score', 'brightness_score', 'quality_warnings', 'bounding_box', 'processing_time_ms', 'model_name', 'model_version', 'ai_response', 'failure_reason', 'latitude', 'longitude', 'location_accuracy_meters', 'location_label', 'capture_notes', 'expert_species_id', 'needs_retraining', 'admin_notes'];

    protected function casts(): array
    {
        return ['confidence' => 'float', 'blur_score' => 'float', 'brightness_score' => 'float', 'quality_warnings' => 'array', 'bounding_box' => 'array', 'ai_response' => 'array', 'latitude' => 'float', 'longitude' => 'float', 'location_accuracy_meters' => 'float', 'needs_retraining' => 'boolean'];
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function species(): BelongsTo { return $this->belongsTo(CrabSpecies::class, 'crab_species_id'); }
    public function expertSpecies(): BelongsTo { return $this->belongsTo(CrabSpecies::class, 'expert_species_id'); }
    public function feedback(): HasMany { return $this->hasMany(RecognitionFeedback::class); }
}
