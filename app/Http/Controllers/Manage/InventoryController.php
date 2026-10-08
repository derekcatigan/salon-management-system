<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Rules\UniqueProductIdentity;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class InventoryController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category', 'All');
        $stockFilter = $request->query('stock', 'all');
        $sort = $request->query('sort', 'name');
        $dir = $request->query('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = 8;

        $query = Product::query()->search($search);

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        match ($stockFilter) {
            'low' => $query->lowStock(),
            'out' => $query->outOfStock(),
            'in' => $query->whereColumn('stock', '>', 'reorder_level'),
            default => null,
        };

        $allowedSorts = ['name', 'sku', 'price', 'stock', 'created_at'];
        if (! in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $products = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'category' => $p->category,
                'brand' => $p->brand,
                'price' => (float) $p->price,
                'cost' => (float) $p->cost,
                'stock' => $p->stock,
                'reorder_level' => $p->reorder_level,
                'unit' => $p->unit,
                'description' => $p->description,
                'updated_at' => $p->updated_at?->format('Y-m-d'),
            ]);

        return Inertia::render('Manage/ManageInventory', [
            'products' => $products,
            'stats' => [
                'total' => Product::count(),
                'low' => Product::lowStock()->count(),
                'out' => Product::outOfStock()->count(),
                'value' => (float) Product::query()
                    ->selectRaw('COALESCE(SUM(cost * stock), 0) as v')
                    ->value('v'),
            ],
            'categories' => ['All', 'Hair Care', 'Skin Care', 'Nail Care', 'Spa', 'Tools'],
            'filters' => [
                'q' => $search,
                'category' => $category,
                'stock' => $stockFilter,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), [], $this->attributes());

        $data = $this->normalize($data);

        Product::create($data);

        return back()->with('success', 'Product added successfully.');
    }

    public function update(Request $request, Product $product)
    {
        $data = $request->validate(
            $this->rules($product->id),
            [],
            $this->attributes(),
        );

        $data = $this->normalize($data);

        $product->update($data);

        return back()->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return back()->with('success', 'Product deleted successfully.');
    }

    private function rules(?string $ignoreId = null): array
    {
        $skuUnique = $ignoreId
            ? Rule::unique('products', 'sku')->ignore($ignoreId)
            : Rule::unique('products', 'sku');

        return [
            'sku' => ['required', 'string', 'max:50', $skuUnique],
            'name' => ['required', 'string', 'min:2', 'max:150', new UniqueProductIdentity($ignoreId)],
            'category' => ['required', 'string', 'max:50'],
            'brand' => ['nullable', 'string', 'max:100'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'cost' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'stock' => ['required', 'integer', 'min:0', 'max:999999'],
            'reorder_level' => ['required', 'integer', 'min:0', 'max:999999'],
            'unit' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }

    private function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'product name',
            'category' => 'category',
            'brand' => 'brand',
            'price' => 'price',
            'cost' => 'cost',
            'stock' => 'stock',
            'reorder_level' => 'reorder level',
            'unit' => 'unit',
            'description' => 'description',
        ];
    }

    /**
     * Trim strings before saving.
     */
    private function normalize(array $data): array
    {
        foreach (['sku', 'name', 'category', 'brand', 'unit', 'description'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $trimmed = trim($data[$key]);
                $data[$key] = $trimmed === '' ? null : $trimmed;
            }
        }

        return $data;
    }
}
