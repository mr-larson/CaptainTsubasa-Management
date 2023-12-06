<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $name
 * @property mixed $description
 * @property mixed $budget
 * @property mixed $wins
 * @property mixed $draws
 * @property mixed $losses
 */

class TeamResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'budget' => $this->budget,
            'wins' => $this->wins,
            'draws' => $this->draws,
            'losses' => $this->losses,
            'actions' => [
                'index' => route('teams.index'),
                'edit' => route('teams.edit', $this->id),
                'update' => route('teams.update', $this->id),
                'delete' => route('teams.destroy', $this->id),
            ],
        ];
    }
}
