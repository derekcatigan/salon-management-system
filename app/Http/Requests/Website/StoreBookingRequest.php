<?php

namespace App\Http\Requests\Website;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null; // must be logged in
    }

    public function rules(): array
    {
        return [
            'service_id' => ['required', 'uuid', Rule::exists('services', 'id')->where('status', 'active')],
            'full_name' => ['required', 'string', 'min:2', 'max:150'],
            'phone' => ['required', 'string', 'min:7', 'max:30'],
            'email' => ['nullable', 'email', 'max:150'],
            'booking_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'time_slot' => ['required', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'service_id.exists' => 'The selected service is no longer available.',
            'booking_date.after_or_equal' => 'Please choose today or a future date.',
        ];
    }
}
