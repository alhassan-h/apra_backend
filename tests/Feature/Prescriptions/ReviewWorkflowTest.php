<?php

use App\Models\Prescription;
use App\Models\PrescriptionExtraction;
use App\Models\User;

function makePrescriptionWithExtraction(User $user, array $extractionOverrides = []): Prescription
{
    $rx = Prescription::factory()->create([
        'uploaded_by' => $user->id,
        'status'      => 'EXTRACTED',
        'image_url'   => 'https://res.cloudinary.com/test/rx.jpg',
    ]);

    $defaultDrugs = [[
        'drug_name' => 'Paracetamol',
        'dosage'    => '500mg',
        'frequency' => 'BD',
        'duration'  => '5 days',
        'route'     => 'Oral',
    ]];

    PrescriptionExtraction::factory()->create(array_merge([
        'prescription_id'  => $rx->id,
        'drugs'            => $defaultDrugs,
        'confidence_score' => 0.92,
        'model_name'       => 'GPT4O_VISION',
        'model_version'    => 'gpt-4o',
        'raw_response'     => [],
    ], $extractionOverrides));

    return $rx;
}

describe('Review workflow', function () {
    it('pharmacist can approve a prescription unchanged', function () {
        $user = User::factory()->create();
        $rx   = makePrescriptionWithExtraction($user);

        $response = $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'drugs' => [[
                'drug_name' => 'Paracetamol',
                'dosage'    => '500mg',
                'frequency' => 'BD',
                'duration'  => '5 days',
                'route'     => 'Oral',
            ]],
            'review_status' => 'APPROVED',
        ]);

        $response->assertCreated()
            ->assertJsonPath('review_status', 'APPROVED');

        expect($rx->fresh()->status)->toBe('VERIFIED');
        $this->assertDatabaseMissing('prescription_corrections', ['prescription_id' => $rx->id]);
    });

    it('generates corrections when pharmacist modifies fields', function () {
        $user = User::factory()->create();
        $rx   = makePrescriptionWithExtraction($user, [
            'drugs' => [['drug_name' => 'PCM', 'dosage' => '250mg', 'frequency' => 'BD', 'duration' => '5 days', 'route' => 'Oral']],
        ]);

        $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'drugs' => [[
                'drug_name' => 'Paracetamol',
                'dosage'    => '500mg',
                'frequency' => 'BD',
                'duration'  => '5 days',
                'route'     => 'Oral',
            ]],
            'review_status' => 'MODIFIED',
        ])->assertCreated();

        $this->assertDatabaseHas('prescription_corrections', [
            'prescription_id' => $rx->id,
            'field_name'      => 'drugs',
        ]);

        expect($rx->fresh()->status)->toBe('VERIFIED');
    });

    it('sets status to REJECTED when pharmacist rejects', function () {
        $user = User::factory()->create();
        $rx   = makePrescriptionWithExtraction($user);

        $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'review_status' => 'REJECTED',
            'review_notes'  => 'Image too blurry to read.',
        ])->assertCreated();

        expect($rx->fresh()->status)->toBe('REJECTED');
    });

    it('prevents a second review via POST — use PUT to update', function () {
        $user = User::factory()->create();
        $rx   = makePrescriptionWithExtraction($user);

        $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'drugs'         => [['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'BD', 'duration' => '5 days', 'route' => 'Oral']],
            'review_status' => 'APPROVED',
        ]);

        $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'drugs'         => [['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'BD', 'duration' => '5 days', 'route' => 'Oral']],
            'review_status' => 'APPROVED',
        ])->assertConflict();
    });

    it('pharmacist can update an existing review via PUT', function () {
        $user = User::factory()->create();
        $rx   = makePrescriptionWithExtraction($user);

        $this->actingAs($user)->postJson("/api/prescriptions/{$rx->id}/review", [
            'drugs'         => [['drug_name' => 'Paracetamol', 'dosage' => '500mg', 'frequency' => 'BD', 'duration' => '5 days', 'route' => 'Oral']],
            'review_status' => 'APPROVED',
        ]);

        $this->actingAs($user)->putJson("/api/prescriptions/{$rx->id}/review", [
            'drugs'         => [['drug_name' => 'Ibuprofen', 'dosage' => '400mg', 'frequency' => 'TDS', 'duration' => '5 days', 'route' => 'Oral']],
            'review_status' => 'MODIFIED',
            'review_notes'  => 'Corrected drug name.',
        ])->assertOk()
            ->assertJsonPath('drugs.0.drug_name', 'Ibuprofen');

        expect($rx->fresh()->status)->toBe('VERIFIED');
    });

    it('another user cannot review someone else prescription', function () {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $rx    = makePrescriptionWithExtraction($owner);

        $this->actingAs($other)->postJson("/api/prescriptions/{$rx->id}/review", [
            'review_status' => 'APPROVED',
        ])->assertForbidden();
    });
});
