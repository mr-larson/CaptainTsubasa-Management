<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $team_id
 * @property mixed $player_id
 * @property mixed $salary
 * @property mixed $start_date
 * @property mixed $end_date
 */

class ContractResource extends JsonResource
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
            'team_id' => $this->team_id,
            'player_id' => $this->player_id,
            'salary' => $this->salary,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'actions' => [
                'index' => route('contracts.index'),
                'edit' => route('contracts.edit', $this->id),
                'update' => route('contracts.update', $this->id),
                'delete' => route('contracts.destroy', $this->id),
            ],
        ];
    }
}
