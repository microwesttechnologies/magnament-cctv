<?php

declare(strict_types=1);

namespace App\Support\FloorPlans;

use App\Models\Project;
use App\Models\ProjectCamera;
use Illuminate\Support\Facades\Cache;
use App\Support\Cache\CacheKeys;
use App\Support\Cache\CacheTtl;

final class ProjectFloorPlanPayload
{
    /**
     * @return array{sheets: list<array<string, mixed>>, dvrs: list<array<string, mixed>>}
     */
    public static function for(Project $project): array
    {
        return Cache::remember(
            CacheKeys::projectPlans((int) $project->id),
            CacheTtl::LIST_STATS,
            fn (): array => [
                'sheets' => self::sheets($project),
                'dvrs' => self::dvrs($project),
            ],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function sheets(Project $project): array
    {
        return $project->floorPlans->map(fn ($fp) => [
            'id' => $fp->id,
            'name' => $fp->name ?: 'Hoja '.$fp->id,
            'description' => $fp->description,
            'status' => $fp->status ?: 'activo',
            'url' => $fp->url(),
            'isImage' => $fp->isImage(),
            'sort_order' => (int) $fp->sort_order,
            'updateUrl' => route('projects.floor-plans.update', [$project, $fp]),
            'deleteUrl' => route('projects.floor-plans.destroy', [$project, $fp]),
            'cameras' => $fp->cameras->map(fn (ProjectCamera $cam) => self::camera($project, $cam))->values()->all(),
        ])->values()->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function dvrs(Project $project): array
    {
        $cameras = $project->projectCameras;

        return $project->dvrs->map(function ($dvr) use ($cameras, $project) {
            $ports = max(1, (int) $dvr->ports);
            $channels = [];

            for ($channel = 1; $channel <= $ports; $channel++) {
                /** @var ProjectCamera|null $cam */
                $cam = $cameras->first(
                    fn (ProjectCamera $item) => (int) $item->dvr_id === (int) $dvr->id && (int) $item->channel === $channel
                );

                $channels[] = [
                    'channel' => $channel,
                    'label' => 'Canal '.str_pad((string) $channel, 2, '0', STR_PAD_LEFT),
                    'camera_id' => $cam?->id,
                    'floor_plan_id' => $cam?->floor_plan_id,
                    'floor_plan_name' => $cam?->floorPlan?->name,
                    'placed' => $cam?->isPlaced() ?? false,
                    'inventory' => $cam !== null && ! $cam->isPlaced(),
                    'camera' => $cam ? self::camera($project, $cam) : null,
                ];
            }

            return [
                'id' => $dvr->id,
                'label' => trim(($dvr->brand ?? '').' '.($dvr->serial_model ?? '')) ?: 'DVR #'.$dvr->id,
                'ports' => $ports,
                'channels' => $channels,
            ];
        })->values()->all();
    }

    /**
     * @return array<string, mixed>
     */
    private static function camera(Project $project, ProjectCamera $cam): array
    {
        return [
            'id' => $cam->id,
            'name' => $cam->name,
            'description' => $cam->description,
            'brand' => $cam->brand,
            'reference' => $cam->reference,
            'serial' => $cam->serial,
            'channel' => $cam->channel,
            'dvr_id' => $cam->dvr_id,
            'dvr_label' => trim(($cam->dvr?->brand ?? '').' '.($cam->dvr?->serial_model ?? '')) ?: 'DVR #'.$cam->dvr_id,
            'photo_url' => $cam->photoUrl(),
            'pos_x' => $cam->pos_x !== null ? (float) $cam->pos_x : null,
            'pos_y' => $cam->pos_y !== null ? (float) $cam->pos_y : null,
            'floor_plan_id' => $cam->floor_plan_id,
            'update_url' => route('projects.cameras.update', [$project, $cam]),
            'position_url' => route('projects.cameras.position', [$project, $cam]),
            'unplace_url' => route('projects.cameras.unplace', [$project, $cam]),
            'delete_url' => route('projects.cameras.destroy', [$project, $cam]),
        ];
    }
}
