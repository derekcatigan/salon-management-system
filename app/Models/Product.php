<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'sku', 'name', 'category', 'brand',
    'price', 'cost', 'stock', 'reorder_level',
    'unit', 'description',
])]
class Product extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost' => 'decimal:2',
            'stock' => 'integer',
            'reorder_level' => 'integer',
        ];
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */
    public function scopeLowStock($query)
    {
        return $query->whereColumn('stock', '<=', 'reorder_level')
            ->where('stock', '>', 0);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('stock', 0);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        $term = '%'.trim($term).'%';

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', $term)
                ->orWhere('sku', 'like', $term)
                ->orWhere('brand', 'like', $term)
                ->orWhere('category', 'like', $term);
        });
    }
}
