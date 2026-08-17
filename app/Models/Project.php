<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $table = 'projects_tb';

    protected $fillable = [
        'code',
        'name',
        'type',
        'address',
        'neighborhood',
        'city',
        'floor_plan_path',
        'status',
    ];

    /**
     * DVRs de este proyecto (un proyecto tiene muchos DVRs).
     *
     * @return HasMany<Dvr, $this>
     */
    public function dvrs(): HasMany
    {
        return $this->hasMany(Dvr::class, 'project_id');
    }

    /**
     * Hojas de plano de este proyecto.
     *
     * @return HasMany<FloorPlan, $this>
     */
    public function floorPlans(): HasMany
    {
        return $this->hasMany(FloorPlan::class, 'project_id')->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Cámaras del proyecto colocadas en planos.
     *
     * @return HasMany<ProjectCamera, $this>
     */
    public function projectCameras(): HasMany
    {
        return $this->hasMany(ProjectCamera::class, 'project_id');
    }
}
