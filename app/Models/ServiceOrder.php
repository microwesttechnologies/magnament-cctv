<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\ServiceOrder\Enums\ServiceOrderPriority;
use App\Domain\ServiceOrder\Enums\ServiceOrderStatus;
use App\Domain\ServiceOrder\Exceptions\InvalidServiceOrderTransition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ServiceOrder extends Model
{
    protected $table = 'service_orders_tb';

    protected $fillable = [
        'code',
        'project_id',
        'dvr_id',
        'source_dvr_support_id',
        'staff_id',
        'created_by',
        'description',
        'observations',
        'requester_name',
        'requester_phone',
        'resolution_notes',
        'unresolved_notes',
        'cancellation_reason',
        'priority',
        'status',
        'scheduled_at',
        'assigned_at',
        'started_at',
        'resolved_at',
        'cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'resolved_at' => 'datetime',
            'unresolved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function statusEnum(): ServiceOrderStatus
    {
        return ServiceOrderStatus::fromString($this->status);
    }

    public function priorityEnum(): ServiceOrderPriority
    {
        return ServiceOrderPriority::fromString($this->priority);
    }

    public function photoEvidenceCount(): int
    {
        return $this->evidences()->count();
    }

    public function hasRequiredPhotoEvidence(): bool
    {
        $count = $this->photoEvidenceCount();

        return $count >= 1 && $count <= 3;
    }

    public function canAddPhotoEvidence(): bool
    {
        return $this->photoEvidenceCount() < 3;
    }

    /** @deprecated Use hasRequiredPhotoEvidence() */
    public function hasPngEvidence(): bool
    {
        return $this->hasRequiredPhotoEvidence();
    }

    public function assignTo(int $staffId): void
    {
        $status = $this->statusEnum();
        if (! $status->canAssign()) {
            throw InvalidServiceOrderTransition::because('Solo una orden pendiente puede asignarse.');
        }

        $this->staff_id = $staffId;
        $this->status = ServiceOrderStatus::Asignada->value;
        $this->assigned_at = now();
        $this->save();
    }

    public function reassignTo(int $staffId): int
    {
        $status = $this->statusEnum();
        if (! $status->canReassign()) {
            throw InvalidServiceOrderTransition::because('Esta orden no puede reasignarse.');
        }

        $previous = (int) $this->staff_id;
        if ($previous === $staffId) {
            throw InvalidServiceOrderTransition::because('Selecciona un técnico distinto al actual.');
        }

        $this->staff_id = $staffId;
        $this->status = ServiceOrderStatus::Asignada->value;
        $this->assigned_at = now();
        $this->started_at = null;
        $this->save();

        return $previous;
    }

    public function start(): void
    {
        if (! $this->statusEnum()->canStart()) {
            throw InvalidServiceOrderTransition::because('Solo una orden asignada puede iniciarse.');
        }

        $this->status = ServiceOrderStatus::EnProceso->value;
        $this->started_at = now();
        $this->save();
    }

    public function resolve(string $notes): void
    {
        if (! $this->statusEnum()->canResolve()) {
            throw InvalidServiceOrderTransition::because('Solo una orden en proceso puede resolverse.');
        }

        if ($this->photoEvidenceCount() < 1) {
            throw InvalidServiceOrderTransition::because('La orden no puede resolverse sin al menos una evidencia fotográfica.');
        }

        $this->resolution_notes = $notes;
        $this->status = ServiceOrderStatus::Resuelta->value;
        $this->resolved_at = now();
        $this->save();
    }

    public function markUnresolved(string $notes): void
    {
        if (! $this->statusEnum()->canMarkUnresolved()) {
            throw InvalidServiceOrderTransition::because('Solo una orden en proceso puede marcarse como no resuelta.');
        }

        if ($this->photoEvidenceCount() < 1) {
            throw InvalidServiceOrderTransition::because('La orden no puede cerrarse sin al menos una evidencia fotográfica.');
        }

        $this->unresolved_notes = $notes;
        $this->status = ServiceOrderStatus::NoResuelta->value;
        $this->unresolved_at = now();
        $this->save();
    }

    public function cancel(string $reason): void
    {
        $status = $this->statusEnum();
        if (! $status->canCancel()) {
            throw InvalidServiceOrderTransition::because('Esta orden no puede cancelarse.');
        }

        if ($status->requiresEvidenceToClose() && $this->photoEvidenceCount() < 1) {
            throw InvalidServiceOrderTransition::because('La orden en proceso no puede cancelarse sin al menos una evidencia fotográfica.');
        }

        $this->cancellation_reason = $reason;
        $this->status = ServiceOrderStatus::Cancelada->value;
        $this->cancelled_at = now();
        $this->save();
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /** @return BelongsTo<Dvr, $this> */
    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class, 'dvr_id');
    }

    /** @return BelongsTo<Staff, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<ServiceOrderEvidence, $this> */
    public function evidences(): HasMany
    {
        return $this->hasMany(ServiceOrderEvidence::class, 'service_order_id')->latest();
    }

    /** @return BelongsTo<DvrSupport, $this> */
    public function sourceDvrSupport(): BelongsTo
    {
        return $this->belongsTo(DvrSupport::class, 'source_dvr_support_id');
    }

    public static function nextCode(): string
    {
        $next = (int) static::query()->max('id') + 1;

        return 'OS-'.now()->format('Y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
