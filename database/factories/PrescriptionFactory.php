<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Prescription>
 */
class PrescriptionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_number' => 'RX-' . strtoupper(Str::random(8)),
            'image_url'           => fake()->imageUrl(800, 600, 'medical'),
            'uploaded_by'         => User::factory(),
            'status'              => 'UPLOADED',
            'uploaded_at'         => now(),
        ];
    }

    public function extracted(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'EXTRACTED']);
    }

    public function verified(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'VERIFIED']);
    }

    public function rejected(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'REJECTED']);
    }
}
