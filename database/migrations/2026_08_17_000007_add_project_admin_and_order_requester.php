<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects_tb', function (Blueprint $table) {
            $table->string('admin_name')->nullable()->after('city');
            $table->string('admin_phone', 64)->nullable()->after('admin_name');
            $table->string('admin_email')->nullable()->after('admin_phone');
        });

        Schema::table('service_orders_tb', function (Blueprint $table) {
            $table->string('requester_name')->nullable()->after('observations');
            $table->string('requester_phone', 64)->nullable()->after('requester_name');
        });
    }

    public function down(): void
    {
        Schema::table('service_orders_tb', function (Blueprint $table) {
            $table->dropColumn(['requester_name', 'requester_phone']);
        });

        Schema::table('projects_tb', function (Blueprint $table) {
            $table->dropColumn(['admin_name', 'admin_phone', 'admin_email']);
        });
    }
};
