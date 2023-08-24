<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',  // Nom obligatoire et au maximum 255 caractères
            'logo_path' => 'nullable|string|max:255',  // Chemin du logo optionnel, au maximum 255 caractères
            'budget' => 'required|integer|min:0',  // Budget obligatoire, entier et positif
            'points' => 'required|integer|min:0',  // Points obligatoires, entiers et positifs
            'wins' => 'required|integer|min:0',  // Victoires obligatoires, entiers et positifs
            'draws' => 'required|integer|min:0',  // Matchs nuls obligatoires, entiers et positifs
            'losses' => 'required|integer|min:0',  // Défaites obligatoires, entiers et positifs
            'team_stats_bonus' => 'nullable|array',  // Bonus d'équipe optionnels, sous forme de tableau
            'active_cards' => 'nullable|array',  // Cartes actives optionnelles, sous forme de tableau
            'description' => 'nullable|string',  // Description optionnelle
        ];
    }
}
