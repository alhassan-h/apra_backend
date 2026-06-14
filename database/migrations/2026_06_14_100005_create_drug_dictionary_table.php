<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('drug_dictionary', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('generic_name');
            $table->string('brand_name')->nullable();
            $table->json('aliases')->nullable();
            $table->string('nafdac_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();

            $table->index('generic_name');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('drug_dictionary');
    }
};
