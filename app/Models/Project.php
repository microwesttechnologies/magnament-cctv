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

    public static function nextCode(): string
    {
        return 'PRJ-'.now()->format('Y').'-'.str_pad((string) (static::query()->max('id') + 1), 3, '0', STR_PAD_LEFT);
    }

    /**
     * Alta de proyecto reutilizada por Proyectos y Cotizaciones.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function createRecord(array $attributes): self
    {
        return static::query()->create(array_merge([
            'code' => static::nextCode(),
            'type' => 'Residencial',
            'status' => 'activo',
            'floor_plan_path' => null,
        ], $attributes));
    }

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

    /**
     * Cotizaciones comerciales asociadas al proyecto.
     *
     * @return HasMany<Quotation, $this>
     */
    public function quotations(): HasMany
    {
        return $this->hasMany(Quotation::class, 'project_id')->latest();
    }

    /**
     * Órdenes de instalación/implementación del proyecto.
     *
     * @return HasMany<InstallationOrder, $this>
     */
    public function installationOrders(): HasMany
    {
        return $this->hasMany(InstallationOrder::class, 'project_id')->latest();
    }

    /**
     * Órdenes de servicio técnico del proyecto.
     *
     * @return HasMany<ServiceOrder, $this>
     */
    public function serviceOrders(): HasMany
    {
        return $this->hasMany(ServiceOrder::class, 'project_id')->latest();
    }

    /**
     * Eventos de trazabilidad del proyecto.
     *
     * @return HasMany<TraceabilityEvent, $this>
     */
    public function traceabilityEvents(): HasMany
    {
        return $this->hasMany(TraceabilityEvent::class, 'project_id')->latest();
    }
}
