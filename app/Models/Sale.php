<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reference_no', 'cashier_id', 'business_date', 'subtotal', 'discount', 'total',
    'amount_paid', 'change_due', 'payment_method', 'status', 'notes',
])]
class Sale extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'business_date' => 'date:Y-m-d',
            'subtotal' => 'decimal:2',
            'discount' => 'decimal:2',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'change_due' => 'decimal:2',
        ];
    }

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function cashier()
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function denominations()
    {
        return $this->hasMany(Denomination::class);
    }

    public static function generateReference(?string $businessDate = null): string
    {
        $date = $businessDate ?: now()->toDateString();
        $prefix = 'INV-'.date('Ymd', strtotime($date)).'-';

        $last = static::query()
            ->where('reference_no', 'like', $prefix.'%')
            ->orderByDesc('reference_no')
            ->value('reference_no');

        $next = $last
            ? ((int) substr($last, -4)) + 1
            : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('business_date', today());
    }

    public function scopeThisMonth($query)
    {
        return $query->whereYear('business_date', now()->year)
            ->whereMonth('business_date', now()->month);
    }
}
