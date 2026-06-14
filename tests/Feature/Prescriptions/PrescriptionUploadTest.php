<?php

use App\Contracts\PrescriptionExtractionServiceInterface;
use App\Data\ExtractionResult;
use App\Jobs\ProcessPrescriptionJob;
use App\Models\Prescription;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('local');
    Queue::fake();
});

describe('Prescription Upload', function () {
    it('pharmacist can upload a prescription image', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('prescription.jpg', 100, 'image/jpeg');

        // Bind a fake CloudinaryService so no real upload happens
        $this->app->bind(\App\Services\CloudinaryService::class, function () {
            return new class extends \App\Services\CloudinaryService {
                public function uploadPrescription($file): string
                {
                    return 'https://res.cloudinary.com/test/image/upload/prescription.jpg';
                }
            };
        });

        $response = $this->actingAs($user)->postJson('/api/prescriptions', [
            'image' => $file,
        ]);

        $response->assertCreated()
            ->assertJsonStructure(['id', 'prescription_number', 'image_url', 'status']);

        expect($response->json('status'))->toBe('UPLOADED');

        Queue::assertPushed(ProcessPrescriptionJob::class);
    });

    it('rejects upload without a file', function () {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/prescriptions', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('rejects invalid file types', function () {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->create('virus.exe', 100, 'application/octet-stream');

        $this->actingAs($user)
            ->postJson('/api/prescriptions', ['image' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image']);
    });

    it('rejects unauthenticated uploads', function () {
        $file = UploadedFile::fake()->create('rx.jpg', 100, 'image/jpeg');

        $this->postJson('/api/prescriptions', ['image' => $file])
            ->assertUnauthorized();
    });

    it('lists only own prescriptions for a pharmacist', function () {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Prescription::factory()->count(3)->create(['uploaded_by' => $userA->id, 'image_url' => 'http://example.com/a.jpg']);
        Prescription::factory()->count(2)->create(['uploaded_by' => $userB->id, 'image_url' => 'http://example.com/b.jpg']);

        $response = $this->actingAs($userA)->getJson('/api/prescriptions');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(3);
    });

    it('admin sees all prescriptions', function () {
        $admin = User::factory()->admin()->create();
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Prescription::factory()->count(2)->create(['uploaded_by' => $userA->id, 'image_url' => 'http://example.com/1.jpg']);
        Prescription::factory()->count(3)->create(['uploaded_by' => $userB->id, 'image_url' => 'http://example.com/2.jpg']);

        $response = $this->actingAs($admin)->getJson('/api/prescriptions');

        $response->assertOk();
        expect($response->json('data'))->toHaveCount(5);
    });

    it('non-owner cannot view another user prescription', function () {
        $owner  = User::factory()->create();
        $other  = User::factory()->create();
        $rx     = Prescription::factory()->create(['uploaded_by' => $owner->id, 'image_url' => 'http://example.com/rx.jpg']);

        $this->actingAs($other)
            ->getJson("/api/prescriptions/{$rx->id}")
            ->assertForbidden();
    });

    it('only admin can delete a prescription', function () {
        $admin       = User::factory()->admin()->create();
        $pharmacist  = User::factory()->create();
        $rx          = Prescription::factory()->create(['uploaded_by' => $pharmacist->id, 'image_url' => 'http://example.com/rx.jpg']);

        $this->actingAs($pharmacist)
            ->deleteJson("/api/prescriptions/{$rx->id}")
            ->assertForbidden();

        $this->actingAs($admin)
            ->deleteJson("/api/prescriptions/{$rx->id}")
            ->assertOk();

        $this->assertDatabaseMissing('prescriptions', ['id' => $rx->id]);
    });
});

describe('ProcessPrescriptionJob', function () {
    it('extracts prescription data and updates status to EXTRACTED', function () {
        $user = User::factory()->create();
        $rx   = Prescription::factory()->create([
            'uploaded_by' => $user->id,
            'status'      => 'UPLOADED',
            'image_url'   => 'https://res.cloudinary.com/test/image.jpg',
        ]);

        $fakeResult = new ExtractionResult(
            drugName: 'Paracetamol',
            dosage: '500mg',
            frequency: 'BD',
            duration: '5 days',
            route: 'Oral',
            confidenceScore: 0.92,
            rawResponse: ['choices' => [['message' => ['content' => '{}']]]],
            modelName: 'GPT4O_VISION',
            modelVersion: 'gpt-4o',
        );

        $this->app->bind(PrescriptionExtractionServiceInterface::class, function () use ($fakeResult) {
            return new class ($fakeResult) implements PrescriptionExtractionServiceInterface {
                public function __construct(private ExtractionResult $result) {}
                public function extract(string $imageUrl): ExtractionResult { return $this->result; }
                public function getModelName(): string { return 'GPT4O_VISION'; }
                public function getModelVersion(): string { return 'gpt-4o'; }
            };
        });

        (new ProcessPrescriptionJob($rx))->handle(
            app(PrescriptionExtractionServiceInterface::class),
            app(\App\Services\AuditService::class),
        );

        $rx->refresh();
        expect($rx->status)->toBe('EXTRACTED');

        $this->assertDatabaseHas('prescription_extractions', [
            'prescription_id' => $rx->id,
            'drug_name'       => 'Paracetamol',
            'dosage'          => '500mg',
            'model_name'      => 'GPT4O_VISION',
        ]);
    });
});
