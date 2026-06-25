<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Stores total good/ng qty per PPF
        Schema::create('total_of_process_summary', function (Blueprint $table) {
            $table->id();
            $table->string('ppfno')->index();
            $table->unsignedInteger('total_good')->default(0);
            $table->unsignedInteger('total_ng')->default(0);
            $table->decimal('ng_percent', 5, 2)->default(0.00);
            $table->timestamps();

            $table->unique('ppfno');
        });

        // Stores total qty per large defect category per PPF
        Schema::create('total_of_process_large_defects', function (Blueprint $table) {
            $table->id();
            $table->string('ppfno')->index();
            $table->string('defect');
            $table->unsignedInteger('total_qty')->default(0);
            $table->timestamps();

            $table->unique(['ppfno', 'defect']);
        });

        // Stores total qty per small defect (under its large category) per PPF
        Schema::create('total_of_process_small_defects', function (Blueprint $table) {
            $table->id();
            $table->string('ppfno')->index();
            $table->string('large_defect');
            $table->string('small_defect');
            $table->unsignedInteger('total_qty')->default(0);
            $table->timestamps();

            $table->unique(['ppfno', 'large_defect', 'small_defect']);
        });

        // Stores total qty per rework type per PPF
        Schema::create('total_of_process_reworks', function (Blueprint $table) {
            $table->id();
            $table->string('ppfno')->index();
            $table->string('rework_type');
            $table->unsignedInteger('total_qty')->default(0);
            $table->timestamps();

            $table->unique(['ppfno', 'rework_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('total_of_process_reworks');
        Schema::dropIfExists('total_of_process_small_defects');
        Schema::dropIfExists('total_of_process_large_defects');
        Schema::dropIfExists('total_of_process_summary');
    }
};