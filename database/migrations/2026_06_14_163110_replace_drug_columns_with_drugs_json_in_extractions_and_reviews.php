<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescription_extractions', function (Blueprint $table) {
            $table->dropColumn(['drug_name', 'dosage', 'frequency', 'duration', 'route']);
            $table->json('drugs')->nullable()->after('raw_response');
        });

        Schema::table('prescription_reviews', function (Blueprint $table) {
            $table->dropColumn(['drug_name', 'dosage', 'frequency', 'duration', 'route']);
            $table->json('drugs')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('prescription_extractions', function (Blueprint $table) {
            $table->dropColumn('drugs');
            $table->string('drug_name')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('route')->nullable();
        });

        Schema::table('prescription_reviews', function (Blueprint $table) {
            $table->dropColumn('drugs');
            $table->string('drug_name')->nullable();
            $table->string('dosage')->nullable();
            $table->string('frequency')->nullable();
            $table->string('duration')->nullable();
            $table->string('route')->nullable();
        });
    }
};
