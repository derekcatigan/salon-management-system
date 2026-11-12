<?php

namespace App\Rules;

use App\Models\Service;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueServiceIdentity implements ValidationRule
{
    public function __construct(protected ?string $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();

        $name = trim((string) ($data['name'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));

        if ($name === '') {
            return;
        }

        $query = Service::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->whereRaw('LOWER(TRIM(category)) = ?', [mb_strtolower($category)]);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('A service with this name already exists in this category.');
        }
    }
}
