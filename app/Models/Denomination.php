<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'cashier_id', 'sale_id', 'type', 'business_date',
    'amount', 'breakdown', 'notes',
])]
class Denomination extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'business_date' => 'date:Y-m-d',
            'amount' => 'decimal:2',
            'breakdown' => 'array',
        ];
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('business_date', today());
    }

    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
