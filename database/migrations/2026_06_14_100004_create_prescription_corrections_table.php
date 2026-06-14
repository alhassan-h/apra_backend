<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescription_corrections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('prescription_id');
            $table->string('field_name');
            $table->text('predicted_value')->nullable();
            $table->text('corrected_value')->nullable();
            $table->uuid('corrected_by');
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('prescription_id')->references('id')->on('prescriptions')->onDelete('cascade');
            $table->foreign('corrected_by')->references('id')->on('users')->onDelete('restrict');
            $table->index('prescription_id');
            $table->index('field_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescription_corrections');
    }
};
