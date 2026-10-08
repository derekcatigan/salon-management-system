<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueProductIdentity implements ValidationRule
{
    public function __construct(protected ?string $ignoreId = null) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $data = request()->all();

        $name = trim((string) ($data['name'] ?? ''));
        $brand = trim((string) ($data['brand'] ?? ''));
        $unit = trim((string) ($data['unit'] ?? ''));

        if ($name === '') {
            return;
        }

        $query = Product::query()
            ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)])
            ->whereRaw("LOWER(TRIM(COALESCE(brand, ''))) = ?", [mb_strtolower($brand)])
            ->whereRaw('LOWER(TRIM(unit)) = ?', [mb_strtolower($unit)]);

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        if ($query->exists()) {
            $fail('A product with this name, brand, and unit already exists.');
        }
    }
}
