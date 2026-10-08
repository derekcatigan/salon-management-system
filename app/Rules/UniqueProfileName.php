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
        $data = request()->input('profile', []);

        $firstname = $this->normalize($data['firstname'] ?? '');
        $middlename = $this->normalize($data['middlename'] ?? '');
        $lastname = $this->normalize($data['lastname'] ?? '');
        $suffix = $this->normalize($data['suffix'] ?? '');

        // If any of the required name pieces are missing, skip — the normal
        // validation rules will handle that.
        if ($firstname === '' || $lastname === '') {
            return;
        }

        $query = Profile::query()
            ->whereRaw('LOWER(TRIM(firstname))  = ?', [mb_strtolower($firstname)])
            ->whereRaw('LOWER(TRIM(lastname))   = ?', [mb_strtolower($lastname)])
            ->whereRaw("LOWER(TRIM(COALESCE(middlename, ''))) = ?", [mb_strtolower($middlename)])
            ->whereRaw("LOWER(TRIM(COALESCE(suffix, '')))     = ?", [mb_strtolower($suffix)]);

        if ($this->ignoreProfileId) {
            $query->where('id', '!=', $this->ignoreProfileId);
        }

        if ($query->exists()) {
            $fail('A user with this full name already exists.');
        }
    }

    private function normalize(?string $value): string
    {
        return trim((string) $value);
    }
}
