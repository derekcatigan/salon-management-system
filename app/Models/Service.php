<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'name', 'category', 'price',
    'duration_minutes', 'description', 'status',
])]
class Service extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'duration_minutes' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */
    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('category', 'like', $term)
                ->orWhere('description', 'like', $term);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
