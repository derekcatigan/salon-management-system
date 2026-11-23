<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['date', 'time_slot', 'is_available', 'note', 'updated_by'])]
class BookingSchedule extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'is_available' => 'boolean',
        ];
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeDate($query, string $date)
    {
        return $query->whereDate('date', $date);
    }

    public function scopeAvailable($query)
    {
        return $query->where('is_available', true);
    }
}
