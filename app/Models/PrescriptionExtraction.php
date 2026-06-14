<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrescriptionExtraction extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'prescription_id',
        'model_name',
        'model_version',
        'raw_response',
        'drug_name',
        'dosage',
        'frequency',
        'duration',
        'route',
        'confidence_score',
    ];

    protected function casts(): array
    {
        return [
            'raw_response' => 'array',
            'confidence_score' => 'float',
            'created_at' => 'datetime',
        ];
    }

    public function prescription(): BelongsTo
    {
        return $this->belongsTo(Prescription::class);
    }
}
