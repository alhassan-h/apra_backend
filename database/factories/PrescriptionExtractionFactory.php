<?php

namespace Database\Factories;

use App\Models\Prescription;
use App\Models\PrescriptionExtraction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PrescriptionExtraction>
 */
class PrescriptionExtractionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'prescription_id'  => Prescription::factory(),
            'model_name'       => 'GPT4O_VISION',
            'model_version'    => 'gpt-4o',
            'raw_response'     => ['choices' => [['message' => ['content' => '{}']]]],
            'drugs'            => [
                [
                    'drug_name' => fake()->randomElement(['Paracetamol', 'Amoxicillin', 'Metronidazole', 'Ciprofloxacin']),
                    'dosage'    => fake()->randomElement(['500mg', '250mg', '200mg', '400mg']),
                    'frequency' => fake()->randomElement(['BD', 'TDS', 'OD', 'QDS']),
                    'duration'  => fake()->randomElement(['5 days', '7 days', '3 days', '10 days']),
                    'route'     => fake()->randomElement(['Oral', 'IV', 'IM', 'Topical']),
                ],
            ],
            'confidence_score' => fake()->randomFloat(2, 0.6, 0.99),
        ];
    }
}
