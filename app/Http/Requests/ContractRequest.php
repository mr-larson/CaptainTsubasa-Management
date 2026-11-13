<?php

namespace App\Http\Requests;

use App\Models\Contract;
use Illuminate\Foundation\Http\FormRequest;

/** Requête de création ou d'édition d'un contrat du roster de référence (hors sauvegarde). */
class ContractRequest extends FormRequest
{
    /** Création ou modification autorisée par ContractPolicy (réservée aux administrateurs). */
    public function authorize(): bool
    {
        $contract = $this->route('contract');

        return $contract
            ? (bool) $this->user()?->can('update', $contract)
            : (bool) $this->user()?->can('create', Contract::class);
    }

    /** Équipe, joueur, salaire par match, nombre de matchs du contrat et dates facultatives. */
    public function rules(): array
    {
        return [
            'team_id'   => ['required', 'exists:teams,id'],
            'player_id' => ['required', 'exists:players,id'],

            // coût par match
            'salary'        => ['required', 'integer', 'min:0'],

            // nombre total de matchs du contrat
            'matches_total'  => ['required', 'integer', 'min:1'],

            // matches_played sera en général géré côté backend (0 à la création)
            'matches_played' => ['nullable', 'integer', 'min:0'],

            // Dates → optionnelles / pas utilisées pour le gameplay
            'start_date' => ['nullable', 'date'],
            'end_date'   => ['nullable', 'date'],
        ];
    }
}
