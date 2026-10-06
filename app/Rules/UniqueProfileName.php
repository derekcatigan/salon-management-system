<?php

namespace App\Rules;

use App\Models\Profile;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueProfileName implements ValidationRule
{
    public function __construct(protected ?string $ignoreProfileId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // The whole profile data is on the request, so pull it directly
        $data = request()->input('profile', []);

        $firstname = trim((string) ($data['firstname'] ?? ''));
        $middlename = trim((string) ($data['middlename'] ?? ''));
        $lastname = trim((string) ($data['lastname'] ?? ''));
        $suffix = trim((string) ($data['suffix'] ?? ''));

        $query = Profile::query()
            ->whereRaw('LOWER(firstname)  = ?', [mb_strtolower($firstname)])
            ->whereRaw('LOWER(lastname)   = ?', [mb_strtolower($lastname)])
            ->whereRaw("LOWER(COALESCE(middlename, '')) = ?", [mb_strtolower($middlename)])
            ->whereRaw("LOWER(COALESCE(suffix, ''))     = ?", [mb_strtolower($suffix)]);

        if ($this->ignoreProfileId) {
            $query->where('id', '!=', $this->ignoreProfileId);
        }

        if ($query->exists()) {
            $fail('A user with the same full name already exists.');
        }
    }
}
