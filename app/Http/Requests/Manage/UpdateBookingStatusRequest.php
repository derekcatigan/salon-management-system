<?php

namespace App\Http\Requests\Manage;

use App\Enum\BookingStatusEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && in_array($user->role->value, ['admin', 'staff'], true);
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(BookingStatusEnum::class)],
        ];
    }
}
