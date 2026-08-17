<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dvrs_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->nullable()->constrained('projects_tb')->cascadeOnDelete();
            $table->string('brand')->nullable();
            $table->string('serial_model')->nullable();
            $table->unsignedInteger('ports')->default(4);
            $table->unsignedInteger('disks')->default(1);
            $table->string('ip_address')->nullable();
            $table->string('physical_location')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dvrs_tb');
    }
};
