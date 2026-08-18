<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_evidences_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained('quotations_tb')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['quotation_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_evidences_tb');
    }
};
