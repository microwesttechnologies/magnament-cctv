<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('floor_plans_tb', function (Blueprint $table) {
            $table->text('description')->nullable();
            $table->string('status', 32)->default('activo');
        });

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->dropForeign(['floor_plan_id']);
        });

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_plan_id')->nullable()->change();
            $table->decimal('pos_x', 8, 4)->nullable()->change();
            $table->decimal('pos_y', 8, 4)->nullable()->change();
        });

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->foreign('floor_plan_id')
                ->references('id')
                ->on('floor_plans_tb')
                ->nullOnDelete();
        });

        $cameras = DB::table('cameras_tb')->whereNotNull('pos_x')->get(['id', 'pos_x', 'pos_y']);

        foreach ($cameras as $camera) {
            $x = (float) $camera->pos_x;
            $y = (float) $camera->pos_y;

            if ($x > 1 || $y > 1) {
                DB::table('cameras_tb')->where('id', $camera->id)->update([
                    'pos_x' => round($x / 100, 4),
                    'pos_y' => round($y / 100, 4),
                ]);
            }
        }
    }

    public function down(): void
    {
        $cameras = DB::table('cameras_tb')->whereNotNull('pos_x')->get(['id', 'pos_x', 'pos_y']);

        foreach ($cameras as $camera) {
            $x = (float) $camera->pos_x;
            $y = (float) $camera->pos_y;

            if ($x <= 1 && $y <= 1) {
                DB::table('cameras_tb')->where('id', $camera->id)->update([
                    'pos_x' => round($x * 100, 4),
                    'pos_y' => round($y * 100, 4),
                ]);
            }
        }

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->dropForeign(['floor_plan_id']);
        });

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->unsignedBigInteger('floor_plan_id')->nullable(false)->change();
            $table->decimal('pos_x', 8, 4)->nullable(false)->change();
            $table->decimal('pos_y', 8, 4)->nullable(false)->change();
        });

        Schema::table('cameras_tb', function (Blueprint $table) {
            $table->foreign('floor_plan_id')
                ->references('id')
                ->on('floor_plans_tb')
                ->cascadeOnDelete();
        });

        Schema::table('floor_plans_tb', function (Blueprint $table) {
            $table->dropColumn(['description', 'status']);
        });
    }
};
