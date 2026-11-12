<?php

namespace App\Http\Controllers\Manage;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Rules\UniqueServiceIdentity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class ServiceController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $category = $request->query('category', 'All');
        $status = $request->query('status', 'all');
        $sort = $request->query('sort', 'name');
        $dir = $request->query('dir', 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = 10;

        $query = Service::query()->search($search);

        if ($category && $category !== 'All') {
            $query->where('category', $category);
        }

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $allowedSorts = ['name', 'category', 'price', 'duration_minutes', 'created_at'];
        if (! \in_array($sort, $allowedSorts, true)) {
            $sort = 'name';
        }

        $services = $query
            ->orderBy($sort, $dir)
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Service $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'category' => $s->category,
                'price' => (float) $s->price,
                'duration_minutes' => $s->duration_minutes,
                'description' => $s->description,
                'status' => $s->status,
                'updated_at' => $s->updated_at?->format('Y-m-d'),
            ]);

        return Inertia::render('Manage/ManageServices', [
            'services' => $services,
            'stats' => [
                'total' => Service::count(),
                'active' => Service::active()->count(),
                'inactive' => Service::where('status', 'inactive')->count(),
                'avg_price' => (float) Service::query()->avg('price') ?: 0,
            ],
            'categories' => ['All', 'Basic', 'Premium', 'Spa', 'Nail', 'Hair', 'Facial'],
            'filters' => [
                'q' => $search,
                'category' => $category,
                'status' => $status,
                'sort' => $sort,
                'dir' => $dir,
            ],
        ]);
    }

    public function store(Request $request)
    {
        if (Auth::user()->role->value !== 'admin') {
            abort(403, 'Only admins can create services.');
        }

        $data = $request->validate($this->rules(), [], $this->attributes());

        $data = $this->normalize($data);

        Service::create($data);

        return back()->with('success', 'Service added successfully.');
    }

    public function update(Request $request, Service $service)
    {
        $data = $request->validate(
            $this->rules($service->id),
            [],
            $this->attributes(),
        );

        $data = $this->normalize($data);

        $service->update($data);

        return back()->with('success', 'Service updated successfully.');
    }

    public function destroy(Service $service)
    {
        $service->delete();

        return back()->with('success', 'Service deleted successfully.');
    }

    private function rules(?string $ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150', new UniqueServiceIdentity($ignoreId)],
            'category' => ['required', 'string', 'max:50'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999'],
            'duration_minutes' => ['required', 'integer', 'min:5', 'max:1440'],
            'description' => ['nullable', 'string', 'max:500'],
            'status' => ['required', 'in:active,inactive'],
        ];
    }

    private function attributes(): array
    {
        return [
            'name' => 'service name',
            'category' => 'category',
            'price' => 'price',
            'duration_minutes' => 'duration',
            'description' => 'description',
            'status' => 'status',
        ];
    }

    private function normalize(array $data): array
    {
        foreach (['name', 'category', 'description'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $value = trim($data[$key]);

                if ($value === '') {
                    $data[$key] = null;
                } else {
                    // Title Case: "hair cut" → "Hair Cut", "o'brien" → "O'Brien"
                    $data[$key] = mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
                }
            }
        }

        return $data;
    }
}
