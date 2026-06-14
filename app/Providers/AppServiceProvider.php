<?php

namespace App\Providers;

use App\Contracts\PrescriptionExtractionServiceInterface;
use App\Models\DrugDictionary;
use App\Services\OpenAIPrescriptionExtractionService;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(
            PrescriptionExtractionServiceInterface::class,
            OpenAIPrescriptionExtractionService::class,
        );
    }

    public function boot(): void
    {
        JsonResource::withoutWrapping();
        Route::model('drug', DrugDictionary::class);
    }
}
