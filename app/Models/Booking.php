<?php

namespace App\Models;

use App\Enum\BookingStatusEnum;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'reference_no', 'customer_id', 'service_id',
    'service_name', 'service_category', 'service_price', 'service_duration',
    'full_name', 'phone', 'email',
    'booking_date', 'time_slot', 'notes', 'status',
])]
class Booking extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'booking_date' => 'date:Y-m-d',
            'service_price' => 'decimal:2',
            'service_duration' => 'integer',
            'status' => BookingStatusEnum::class,
        ];
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /* ------------------------------------------------------------------ */
    /* Scopes */
    /* ------------------------------------------------------------------ */
    public function scopeUpcoming($query)
    {
        return $query->whereDate('booking_date', '>=', today())
            ->whereIn('status', [
                BookingStatusEnum::Pending->value,
                BookingStatusEnum::Confirmed->value,
            ])
            ->orderBy('booking_date')
            ->orderBy('time_slot');
    }

    public function scopeForCustomer($query, string $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */
    public static function generateReference(?string $date = null): string
    {
        $date = $date ?: now()->toDateString();
        $prefix = 'BKG-'.date('Ymd', strtotime($date)).'-';

        $last = static::query()
            ->where('reference_no', 'like', $prefix.'%')
            ->orderByDesc('reference_no')
            ->value('reference_no');

        $next = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
