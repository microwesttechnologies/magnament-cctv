<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Application\Camera\DTOs\CreateCameraInput;
use App\Application\Camera\UseCases\CreateCameraUseCase;
use App\Application\Camera\UseCases\GetCameraUseCase;
use App\Application\Camera\UseCases\ListCamerasUseCase;
use App\Domain\Camera\Entities\Camera;
use App\Domain\Camera\Exceptions\CameraNotFoundException;
use App\Http\Requests\StoreCameraRequest;
use Illuminate\Http\JsonResponse;

/**
 * Adaptador de interfaz (HTTP). No contiene lógica de negocio:
 * solo traduce la petición a un caso de uso y devuelve la respuesta.
 */
final class CameraController extends Controller
{
    public function index(ListCamerasUseCase $listCameras): JsonResponse
    {
        $cameras = array_map(
            fn (Camera $camera): array => $this->present($camera),
            $listCameras->execute(),
        );

        return response()->json(['data' => $cameras]);
    }

    public function store(StoreCameraRequest $request, CreateCameraUseCase $createCamera): JsonResponse
    {
        $camera = $createCamera->execute(new CreateCameraInput(
            name: $request->string('name')->toString(),
            location: $request->string('location')->toString(),
            ipAddress: $request->string('ip_address')->toString(),
        ));

        return response()->json(['data' => $this->present($camera)], 201);
    }

    public function show(string $id, GetCameraUseCase $getCamera): JsonResponse
    {
        try {
            $camera = $getCamera->execute($id);
        } catch (CameraNotFoundException $e) {
            return response()->json(['message' => $e->getMessage()], 404);
        }

        return response()->json(['data' => $this->present($camera)]);
    }

    /**
     * @return array<string, string>
     */
    private function present(Camera $camera): array
    {
        return [
            'id' => $camera->id()->value(),
            'name' => $camera->name(),
            'location' => $camera->location(),
            'ip_address' => $camera->ipAddress()->value(),
            'status' => $camera->status()->value,
            'created_at' => $camera->createdAt()->format(DATE_ATOM),
        ];
    }
}
