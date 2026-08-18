<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users_tb', function (Blueprint $table) {
            $table->string('phone', 64)->nullable()->after('email');
        });

        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->string('signatory_phone', 64)->nullable()->after('signatory_name');
        });
    }

    public function down(): void
    {
        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->dropColumn('signatory_phone');
        });

        Schema::table('users_tb', function (Blueprint $table) {
            $table->dropColumn('phone');
        });
    }
};
