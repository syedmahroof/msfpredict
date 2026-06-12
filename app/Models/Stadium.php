<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'city', 'country', 'country_code', 'capacity', 'image'])]
class Stadium extends Model
{
    public function fixtures(): HasMany
    {
        return $this->hasMany(Fixture::class);
    }
}
