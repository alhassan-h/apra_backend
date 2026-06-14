<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DrugDictionary extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'drug_dictionary';

    protected $fillable = [
        'generic_name',
        'brand_name',
        'aliases',
        'nafdac_code',
        'is_active',
    ];

    protected $attributes = [
        'aliases' => '[]',
    ];

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    public function matchesName(string $name): bool
    {
        $name = strtolower(trim($name));

        if (strtolower($this->generic_name) === $name) {
            return true;
        }

        if ($this->brand_name && strtolower($this->brand_name) === $name) {
            return true;
        }

        foreach ($this->aliases ?? [] as $alias) {
            if (strtolower($alias) === $name) {
                return true;
            }
        }

        return false;
    }

    public static function findByName(string $name): ?self
    {
        $name = strtolower(trim($name));

        return static::where('is_active', true)->get()->first(
            fn (self $drug) => $drug->matchesName($name)
        );
    }
}
