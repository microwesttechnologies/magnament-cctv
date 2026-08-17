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
            $table->string('signature_path')->nullable()->after('password');
        });

        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->foreignId('signatory_user_id')->nullable()->after('created_by')->constrained('users_tb')->nullOnDelete();
            $table->string('signatory_name')->nullable()->after('signatory_user_id');
            $table->string('signature_snapshot_path')->nullable()->after('signatory_name');
        });
    }

    public function down(): void
    {
        Schema::table('quotations_tb', function (Blueprint $table) {
            $table->dropConstrainedForeignId('signatory_user_id');
            $table->dropColumn(['signatory_name', 'signature_snapshot_path']);
        });

        Schema::table('users_tb', function (Blueprint $table) {
            $table->dropColumn('signature_path');
        });
    }
};
