<?php

declare(strict_types=1);

use App\Models\DvrSupport;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_tb', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('id')->constrained('users_tb')->nullOnDelete();
            $table->unique('user_id');
        });

        Schema::create('service_orders_tb', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->foreignId('project_id')->constrained('projects_tb')->restrictOnDelete();
            $table->foreignId('dvr_id')->nullable()->constrained('dvrs_tb')->nullOnDelete();
            $table->foreignId('source_dvr_support_id')->nullable()->unique()->constrained('dvr_supports_tb')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_tb')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->text('description');
            $table->text('observations')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->string('priority')->default('media');
            $table->string('status')->default('pendiente');
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['project_id', 'status']);
            $table->index(['staff_id', 'status']);
        });

        Schema::create('service_order_evidences_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('service_order_id')->constrained('service_orders_tb')->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users_tb')->nullOnDelete();
            $table->foreignId('staff_id')->nullable()->constrained('staff_tb')->nullOnDelete();
            $table->string('path');
            $table->string('original_name')->nullable();
            $table->string('mime')->default('image/png');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_tb')->cascadeOnDelete();
            $table->text('endpoint');
            $table->string('public_key');
            $table->string('auth_token');
            $table->string('user_agent')->nullable();
            $table->timestamps();
        });

        Schema::create('technician_notifications_tb', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_tb')->cascadeOnDelete();
            $table->foreignId('service_order_id')->nullable()->constrained('service_orders_tb')->nullOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->string('url')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('traceability_events_tb', function (Blueprint $table) {
            $table->foreignId('service_order_id')->nullable()->after('order_id')->constrained('service_orders_tb')->nullOnDelete();
        });

        $this->migrateHistoricalSupports();
    }

    public function down(): void
    {
        Schema::table('traceability_events_tb', function (Blueprint $table) {
            $table->dropConstrainedForeignId('service_order_id');
        });
        Schema::dropIfExists('technician_notifications_tb');
        Schema::dropIfExists('push_subscriptions_tb');
        Schema::dropIfExists('service_order_evidences_tb');
        Schema::dropIfExists('service_orders_tb');
        Schema::table('staff_tb', function (Blueprint $table) {
            $table->dropConstrainedForeignId('user_id');
        });
    }

    private function migrateHistoricalSupports(): void
    {
        $supports = DvrSupport::query()->with(['evidences', 'dvr'])->orderBy('id')->get();
        $year = now()->format('Y');
        $seq = 0;

        foreach ($supports as $support) {
            $seq++;
            $dvr = $support->dvr;
            if ($dvr === null) {
                continue;
            }

            $assignedAt = $support->created_at;
            $orderId = DB::table('service_orders_tb')->insertGetId([
                'code' => 'OS-'.$year.'-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
                'project_id' => $dvr->project_id,
                'dvr_id' => $dvr->id,
                'source_dvr_support_id' => $support->id,
                'staff_id' => $support->staff_id,
                'created_by' => null,
                'description' => $support->title,
                'observations' => $support->description,
                'resolution_notes' => 'Migrado desde soporte histórico del DVR.',
                'cancellation_reason' => null,
                'priority' => 'media',
                'status' => 'resuelta',
                'scheduled_at' => null,
                'assigned_at' => $assignedAt,
                'started_at' => $assignedAt,
                'resolved_at' => $support->updated_at ?? $assignedAt,
                'cancelled_at' => null,
                'created_at' => $support->created_at,
                'updated_at' => $support->updated_at,
            ]);

            foreach ($support->evidences as $evidence) {
                $mime = 'image/png';
                if (is_string($evidence->path) && Storage::disk('public')->exists($evidence->path)) {
                    $detected = (new \finfo(FILEINFO_MIME_TYPE))->buffer((string) Storage::disk('public')->get($evidence->path));
                    $mime = is_string($detected) ? $detected : $mime;
                }

                DB::table('service_order_evidences_tb')->insert([
                    'service_order_id' => $orderId,
                    'uploaded_by' => null,
                    'staff_id' => $support->staff_id,
                    'path' => $evidence->path,
                    'original_name' => $evidence->original_name,
                    'mime' => $mime,
                    'description' => 'Evidencia histórica de soporte DVR',
                    'created_at' => $evidence->created_at,
                    'updated_at' => $evidence->updated_at,
                ]);
            }
        }
    }
};
