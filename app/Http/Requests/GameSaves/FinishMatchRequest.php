<?php

namespace App\Http\Requests\GameSaves;

use Illuminate\Validation\Validator;

/** Requête de fin de match : reçoit le résultat calculé par le moteur de match côté navigateur. */
class FinishMatchRequest extends GameSaveActionRequest
{
    /** Taille maximale (JSON, en octets) des statistiques et des fautes envoyées par le moteur. */
    private const MAX_PAYLOAD_BYTES = 2 * 1024 * 1024;

    /**
     * Scores des deux équipes du match (bornés), plus statistiques et événements
     * de fautes facultatifs. Le contenu des statistiques est libre (il n'est que
     * réaffiché), mais sa taille est plafonnée dans after().
     */
    public function rules(): array
    {
        return [
            'scoresByTeamId'   => ['required', 'array', 'size:2'],
            'scoresByTeamId.*' => ['integer', 'min:0', 'max:99'],
            'match_stats'      => ['nullable', 'array'],
            'foulEvents'       => ['nullable', 'array', 'max:300'],
            'foulEvents.*'     => ['array'],
        ];
    }

    /** Refuse des statistiques ou des fautes trop volumineuses : elles sont stockées telles quelles avec le match. */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                foreach (['match_stats', 'foulEvents'] as $key) {
                    $value = $this->input($key);
                    if (is_array($value) && strlen((string) json_encode($value)) > self::MAX_PAYLOAD_BYTES) {
                        $validator->errors()->add($key, 'Les données du match sont trop volumineuses.');
                    }
                }
            },
        ];
    }
}
