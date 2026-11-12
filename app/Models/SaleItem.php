<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'sale_id', 'item_type', 'item_id', 'item_name', 'item_sku',
    'unit_price', 'quantity', 'line_total',
])]
class SaleItem extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}
