<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

#[Fillable('user_id', 'avatar', 'firstname', 'middlename', 'lastname', 'suffix', 'contact', 'birthdate', 'address')]
class Profile extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'birthdate' => 'date',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
