<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Staff extends Model
{
    protected $table = 'staff_tb';

    protected $fillable = [
        'user_id',
        'name',
        'document_type',
        'document_number',
        'phone',
        'email',
        'city',
        'birth_date',
        'photo_path',
        'role',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<StaffTool, $this>
     */
    public function tools(): HasMany
    {
        return $this->hasMany(StaffTool::class, 'staff_id');
    }

    /**
     * @return HasMany<DvrSupport, $this>
     */
    public function supports(): HasMany
    {
        return $this->hasMany(DvrSupport::class, 'staff_id');
    }

    /**
     * @return HasMany<ServiceOrder, $this>
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'staff_id');
    }

    public function photoUrl(): ?string
    {
        if (! $this->photo_path) {
            return null;
        }

        return asset('storage/'.$this->photo_path);
    }

    public function deletePhoto(): void
    {
        if ($this->photo_path && Storage::disk('public')->exists($this->photo_path)) {
            Storage::disk('public')->delete($this->photo_path);
        }
    }

    public function isActiveTechnician(): bool
    {
        return $this->role === 'tecnico' && $this->status === 'activo';
    }

    public function roleLabel(): string
    {
        return $this->role === 'supervisor' ? 'Supervisor' : 'Técnico';
    }
}
