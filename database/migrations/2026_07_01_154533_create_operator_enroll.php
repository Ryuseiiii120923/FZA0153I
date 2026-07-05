<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection('PRecord')->create('operator_enroll', function (Blueprint $table) {
            $table->id();
            $table->string('OperatorID', 50);
            $table->string('OperatorName', 100);
            $table->string('GLID', 50);
            $table->string('ProgramID', 50);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('PRecord')->dropIfExists('operator_enroll');
    }
};
