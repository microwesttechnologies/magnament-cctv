<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Módulo comercial: cotizaciones, órdenes, IVA configurable, trazabilidad y auditoría.
 * Diseñado para MySQL (DECIMAL, JSON nativo). No usar tipos específicos de SQLite/Postgres.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings_tb', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('quotations_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_tb')->restrictOnDelete();
            $table->string('code')->unique();
            $table->text('work_description')->nullable();
            $table->string('status')->default('borrador');
            // Snapshot del % de IVA aplicado (histórico); no depende del setting vigente.
            $table->decimal('vat_rate_percent', 8, 4);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('vat_amount', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('quotation_lines_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations_tb')->cascadeOnDelete();
            $table->string('product_name');
            $table->decimal('quantity', 12, 2);
            $table->string('brand')->nullable();
            $table->string('serial')->nullable();
            $table->decimal('unit_price', 14, 2);
            $table->decimal('line_subtotal', 14, 2);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('installation_orders_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_tb')->restrictOnDelete();
            $table->foreignId('quotation_id')->unique()->constrained('quotations_tb')->restrictOnDelete();
            $table->string('code')->unique();
            $table->string('status')->default('pendiente');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
        });

        Schema::create('traceability_events_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained('projects_tb')->cascadeOnDelete();
            $table->foreignId('quotation_id')->nullable()->constrained('quotations_tb')->nullOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('installation_orders_tb')->nullOnDelete();
            $table->string('event_type');
            $table->string('title');
            $table->json('payload')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->timestamps();

            $table->index(['project_id', 'created_at']);
        });

        Schema::create('audit_logs_tb', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type');
            $table->unsignedBigInteger('auditable_id');
            $table->string('action');
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs_tb');
        Schema::dropIfExists('traceability_events_tb');
        Schema::dropIfExists('installation_orders_tb');
        Schema::dropIfExists('quotation_lines_tb');
        Schema::dropIfExists('quotations_tb');
        Schema::dropIfExists('app_settings_tb');
    }
};
