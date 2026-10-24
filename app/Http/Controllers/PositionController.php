<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StorePositionRequest;
use App\Http\Requests\UpdatePositionRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Services\PositionService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\JsonResponse;

class PositionController extends Controller
{
    /**
     * Display a paginated list of positions.
     */
    public function index(PositionService $positionService): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Position::class);
        // Senior Tip: Always paginate. Never return Position::all()
        // because eventually, you will have 1,000 rows and crash the browser.
        $positions = $positionService->getAllPositions();

        return PositionResource::collection($positions);
    }

    /**
     * Store a newly created position in storage.
     */
    public function store(StorePositionRequest $request, PositionService $positionService): PositionResource
    {
        $position = $positionService->savePosition($request->validatedSnake());

        return new PositionResource($position);
    }

    /**
     * Display the specified position.
     */
    public function show(PositionService $positionService, Position $position): PositionResource
    {
        $position = $positionService->getPositionById($position->id);
        $this->authorize('view', $position);

        return new PositionResource($position);
    }

    /**
     * Update the specified position in storage.
     */
    public function update(UpdatePositionRequest $request, PositionService $positionService, Position $position): PositionResource
    {
        $position = $positionService->updatePosition($request->validatedSnake(), $position->id);

        return new PositionResource($position);
    }

    /**
     * Remove the specified position from storage.
     */
    public function destroy(PositionService $positionService, int $id): JsonResponse
    {
        $position = $positionService->getPositionById($id);
        $this->authorize('delete', $position);
        // Data Integrity Check
        // You cannot delete a Position if employees are currently assigned to it.
        if ($position->employees()->exists()) {
            return response()->json([
                'message' => 'Cannot delete position because employees are assigned to it.',
            ], 409); // 409 Conflict
        }

        $position->delete();

        // 204 No Content is the standard HTTP status for a successful delete
        return response()->json(null, 204);
    }
}
