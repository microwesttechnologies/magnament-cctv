<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectCamera extends Model
{
    protected $table = 'cameras_tb';

    protected $fillable = [
        'project_id',
        'floor_plan_id',
        'dvr_id',
        'channel',
        'name',
        'description',
        'brand',
        'reference',
        'serial',
        'photo_path',
        'pos_x',
        'pos_y',
    ];

    protected function casts(): array
    {
        return [
            'channel' => 'integer',
            'pos_x' => 'float',
            'pos_y' => 'float',
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
     * @return BelongsTo<FloorPlan, $this>
     */
    public function floorPlan(): BelongsTo
    {
        return $this->belongsTo(FloorPlan::class, 'floor_plan_id');
    }

    /**
     * @return BelongsTo<Dvr, $this>
     */
    public function dvr(): BelongsTo
    {
        return $this->belongsTo(Dvr::class, 'dvr_id');
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

    public function isPlaced(): bool
    {
        return $this->floor_plan_id !== null && $this->pos_x !== null && $this->pos_y !== null;
    }

    public function unplace(): void
    {
        $this->forceFill([
            'floor_plan_id' => null,
            'pos_x' => null,
            'pos_y' => null,
        ])->save();
    }
}
