<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_orders_tb', function (Blueprint $table) {
            $table->text('unresolved_notes')->nullable()->after('resolution_notes');
            $table->timestamp('unresolved_at')->nullable()->after('resolved_at');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders_tb', function (Blueprint $table) {
            $table->dropColumn(['unresolved_notes', 'unresolved_at']);
        });
    }
};
