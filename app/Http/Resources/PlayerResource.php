<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $firstname
 * @property mixed $lastname
 * @property mixed $age
 * @property mixed $position
 * @property mixed $cost
 * @property mixed $stats
 * @property mixed $description
 */

class PlayerResource extends JsonResource
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
            'firstname' => $this->firstname,
            'lastname' => $this->lastname,
            'age' => $this->age,
            'position' => $this->position,
            'cost' => $this->cost,
            'stats' => $this->stats,
            'description' => $this->description,
            'actions' => [
                'index' => route('players.index'),
                'edit' => route('players.edit', $this->id),
                'update' => route('players.update', $this->id),
                'delete' => route('players.destroy', $this->id),
            ],
        ];
    }
}
