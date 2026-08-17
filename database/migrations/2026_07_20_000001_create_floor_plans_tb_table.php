<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('floor_plans_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_tb')->cascadeOnDelete();
            $table->string('path');
            $table->string('name')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Migrar planos existentes desde projects_tb.floor_plan_path
        $projects = DB::table('projects_tb')
            ->whereNotNull('floor_plan_path')
            ->where('floor_plan_path', '!=', '')
            ->get(['id', 'floor_plan_path']);

        foreach ($projects as $project) {
            DB::table('floor_plans_tb')->insert([
                'project_id' => $project->id,
                'path' => $project->floor_plan_path,
                'name' => 'Hoja 1',
                'sort_order' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('floor_plans_tb');
    }
};
