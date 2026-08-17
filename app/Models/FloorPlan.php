<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FloorPlan extends Model
{
    protected $table = 'floor_plans_tb';

    protected $fillable = [
        'project_id',
        'path',
        'name',
        'description',
        'sort_order',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Cámaras colocadas en esta hoja de plano.
     *
     * @return HasMany<ProjectCamera, $this>
     */
    public function cameras(): HasMany
    {
        return $this->hasMany(ProjectCamera::class, 'floor_plan_id');
    }

    public function url(): string
    {
        return asset('storage/'.$this->path);
    }

    public function isPdf(): bool
    {
        return Str::endsWith(strtolower($this->path), '.pdf');
    }

    public function isImage(): bool
    {
        return ! $this->isPdf();
    }

    public function deleteFile(): void
    {
        if ($this->path && Storage::disk('public')->exists($this->path)) {
            Storage::disk('public')->delete($this->path);
        }
    }
}
