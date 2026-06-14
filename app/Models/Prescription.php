<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Prescription extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'prescription_number',
        'image_url',
        'uploaded_by',
        'status',
        'uploaded_at',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'datetime',
        ];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function extraction(): HasOne
    {
        return $this->hasOne(PrescriptionExtraction::class);
    }

    public function review(): HasOne
    {
        return $this->hasOne(PrescriptionReview::class);
    }

    public function corrections(): HasMany
    {
        return $this->hasMany(PrescriptionCorrection::class);
    }
}
