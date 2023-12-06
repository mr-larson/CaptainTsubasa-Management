<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property mixed $id
 * @property mixed $team_id_home
 * @property mixed $team_id_away
 * @property mixed $score_team_home
 * @property mixed $score_team_away
 * @property mixed $end_date
 */

class SoccerMatchResource extends JsonResource
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
            'team_id_home' => $this->team_id_home,
            'team_id_away' => $this->team_id_away,
            'score_team_home' => $this->score_team_home,
            'score_team_away' => $this->score_team_away,
            'end_date' => $this->end_date,
            'actions' => [
                'index' => route('soccerMatches.index'),
                'edit' => route('soccerMatches.edit', $this->id),
                'update' => route('soccerMatches.update', $this->id),
                'delete' => route('soccerMatches.destroy', $this->id),
            ],
        ];
    }
}
