<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_tb', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('document_type')->default('CC');
            $table->string('document_number')->unique();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('city')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('photo_path')->nullable();
            $table->string('role')->default('tecnico'); // supervisor | tecnico
            $table->string('status')->default('activo'); // activo | inactivo
            $table->timestamps();
        });

        Schema::create('staff_tools_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff_tb')->cascadeOnDelete();
            $table->string('name');
            $table->string('brand')->nullable();
            $table->string('reference')->nullable();
            $table->string('serial')->nullable();
            $table->timestamps();
        });

        Schema::create('dvr_supports_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvr_id')->constrained('dvrs_tb')->cascadeOnDelete();
            $table->foreignId('staff_id')->constrained('staff_tb')->restrictOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('dvr_support_evidences_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dvr_support_id')->constrained('dvr_supports_tb')->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvr_support_evidences_tb');
        Schema::dropIfExists('dvr_supports_tb');
        Schema::dropIfExists('staff_tools_tb');
        Schema::dropIfExists('staff_tb');
    }
};
