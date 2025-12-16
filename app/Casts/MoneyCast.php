<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class MoneyCast implements CastsAttributes
{
    /**
     * Cast the given value from storage (cents) to application format (dollars).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): ?float
    {
        if ($value === null) {
            return null;
        }

        // Convert cents (integer) to dollars (float)
        return (float) $value / 100;
    }

    /**
     * Prepare the given value for storage (convert dollars to cents).
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): ?int
    {
        if ($value === null) {
            return null;
        }

        // Convert dollars (float) to cents (integer)
        return (int) round((float) $value * 100);
    }
}
