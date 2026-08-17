<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cameras_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_tb')->cascadeOnDelete();
            $table->foreignId('floor_plan_id')->constrained('floor_plans_tb')->cascadeOnDelete();
            $table->foreignId('dvr_id')->constrained('dvrs_tb')->cascadeOnDelete();
            $table->unsignedInteger('channel');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('brand')->nullable();
            $table->string('reference')->nullable();
            $table->string('serial')->nullable();
            $table->string('photo_path')->nullable();
            $table->decimal('pos_x', 8, 4);
            $table->decimal('pos_y', 8, 4);
            $table->timestamps();

            $table->unique(['dvr_id', 'channel']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cameras_tb');
    }
};
