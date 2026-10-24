<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Position;
use Illuminate\Database\Eloquent\Collection;

class PositionService
{
    public function getAllPositions(): Collection
    {
        return Position::query()->withCount('employees')
            ->orderBy('title')
            ->get();
    }

    public function getPositionById(int $id): Position
    {
        return Position::findOrFail($id);
    }

    public function savePosition(array $position): Position
    {
        return Position::create($position);
    }

    public function updatePosition(array $positionData, int $id): Position
    {
        $position = Position::findOrFail($id);

        $position->update($positionData);

        return $position->refresh(); // Return the updated model instance
    }
}
