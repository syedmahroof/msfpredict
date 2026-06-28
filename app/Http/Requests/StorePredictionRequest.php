<?php

namespace App\Http\Requests;

use App\FixtureRound;
use Illuminate\Foundation\Http\FormRequest;

class StorePredictionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check();
    }

    public function rules(): array
    {
        $fixture = $this->route('fixture');
        $isKnockout = $fixture && FixtureRound::from($fixture->round)->isKnockout();

        $scoresAreEqual = $this->input('predicted_home_score') !== null
            && $this->input('predicted_away_score') !== null
            && (int) $this->input('predicted_home_score') === (int) $this->input('predicted_away_score');

        return [
            'predicted_home_score' => ['required', 'integer', 'min:0', 'max:20'],
            'predicted_away_score' => ['required', 'integer', 'min:0', 'max:20'],
            'predicted_winner' => [
                $isKnockout && $scoresAreEqual ? 'required' : 'nullable',
                'string',
                $isKnockout && $scoresAreEqual ? 'in:home,away' : 'in:home,away,draw',
            ],
        ];
    }
}
