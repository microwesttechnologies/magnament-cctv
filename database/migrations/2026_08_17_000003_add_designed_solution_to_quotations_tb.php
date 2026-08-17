<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->text('designed_solution')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->dropColumn('designed_solution');
        });
    }
};
