<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Dvr extends Model
{
    protected $table = 'dvrs_tb';

    protected $fillable = [
        'project_id',
        'brand',
        'serial_model',
        'ports',
        'disks',
        'ip_address',
        'physical_location',
    ];

    protected function casts(): array
    {
        return [
            'ports' => 'integer',
            'disks' => 'integer',
        ];
    }

    /**
     * Proyecto al que pertenece este DVR (un DVR pertenece a un solo proyecto).
     *
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * Cámaras asignadas a este DVR.
     *
     * @return HasMany<ProjectCamera, $this>
     */
    public function cameras(): HasMany
    {
        return $this->hasMany(ProjectCamera::class, 'dvr_id');
    }

    /**
     * Soportes / hoja de vida del DVR.
     *
     * @return HasMany<DvrSupport, $this>
     */
    public function supports(): HasMany
    {
        return $this->hasMany(DvrSupport::class, 'dvr_id')->latest();
    }
}
