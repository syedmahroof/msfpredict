<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFixtureRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->is_admin;
    }

    public function rules(): array
    {
        return [
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'home_team_id' => ['required', 'exists:teams,id'],
            'away_team_id' => ['required', 'exists:teams,id', 'different:home_team_id'],
            'stadium_id' => ['nullable', 'exists:stadiums,id'],
            'round' => ['required', 'string'],
            'group' => ['nullable', 'string', 'max:1'],
            'match_day' => ['nullable', 'integer', 'min:1'],
            'scheduled_at' => ['required', 'date'],
        ];
    }
}
