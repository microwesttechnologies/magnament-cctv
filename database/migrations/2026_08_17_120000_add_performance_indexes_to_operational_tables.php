<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índices alineados a WHERE/ORDER BY reales (dashboard, listados, filtros).
 * No modifica migraciones históricas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_tb', function (Blueprint $table) {
            $table->index('status', 'projects_tb_status_index');
            $table->index('created_at', 'projects_tb_created_at_index');
        });

        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->index('status', 'quotations_tb_status_index');
            $table->index('created_at', 'quotations_tb_created_at_index');
            $table->index('updated_at', 'quotations_tb_updated_at_index');
        });

        Schema::table('installation_orders_tb', function (Blueprint $table) {
            $table->index('status', 'installation_orders_tb_status_index');
        });

        Schema::table('staff_tb', function (Blueprint $table) {
            $table->index(['role', 'status'], 'staff_tb_role_status_index');
            $table->index('email', 'staff_tb_email_index');
        });

        Schema::table('traceability_events_tb', function (Blueprint $table) {
            $table->index('created_at', 'traceability_events_tb_created_at_index');
        });
    }

    public function down(): void
    {
        Schema::table('projects_tb', function (Blueprint $table) {
            $table->dropIndex('projects_tb_status_index');
            $table->dropIndex('projects_tb_created_at_index');
        });

        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->dropIndex('quotations_tb_status_index');
            $table->dropIndex('quotations_tb_created_at_index');
            $table->dropIndex('quotations_tb_updated_at_index');
        });

        Schema::table('installation_orders_tb', function (Blueprint $table) {
            $table->dropIndex('installation_orders_tb_status_index');
        });

        Schema::table('staff_tb', function (Blueprint $table) {
            $table->dropIndex('staff_tb_role_status_index');
            $table->dropIndex('staff_tb_email_index');
        });

        Schema::table('traceability_events_tb', function (Blueprint $table) {
            $table->dropIndex('traceability_events_tb_created_at_index');
        });
    }
};
