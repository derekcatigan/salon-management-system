<?php

namespace App\Http\Controllers\Manage;

use App\Enum\RoleEnum;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\UniqueProfileName;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class AccountController extends Controller
{
    public function index()
    {
        return Inertia::render('Manage/ManageAccount', [
            'roles' => collect(RoleEnum::cases())->map(fn ($r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ]),
            'statuses' => ['active', 'inactive', 'suspended'],
        ]);
    }

    public function search(Request $request)
    {
        $q = trim($request->query('q', ''));

        $users = User::query()
            ->with('profile')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('email', 'like', "%{$q}%")
                        ->orWhere('role', 'like', "%{$q}%")
                        ->orWhereHas('profile', function ($p) use ($q) {
                            $p->where('firstname', 'like', "%{$q}%")
                                ->orWhere('middlename', 'like', "%{$q}%")
                                ->orWhere('lastname', 'like', "%{$q}%")
                                ->orWhere('suffix', 'like', "%{$q}%")
                                ->orWhere('contact', 'like', "%{$q}%");
                        });
                });
            })
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (User $u) => [
                'id' => $u->id,
                'email' => $u->email,
                'role' => $u->role?->value,
                'status' => $u->status,
                'profile' => $u->profile ? [
                    'avatar' => $u->profile->avatar,
                    'firstname' => $u->profile->firstname,
                    'middlename' => $u->profile->middlename,
                    'lastname' => $u->profile->lastname,
                    'suffix' => $u->profile->suffix,
                    'contact' => $u->profile->contact,
                    'birthdate' => $u->profile->birthdate
                        ? Carbon::parse($u->profile->birthdate)->format('Y-m-d')
                        : null,
                    'address' => $u->profile->address,
                ] : null,
            ]);

        return response()->json($users);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), [], $this->attributes());

        $profileData = $this->normalizeProfile($data['profile']);

        $user = User::create([
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        $user->profile()->create($profileData);

        return back()->with('success', 'Account created successfully.');
    }

    public function update(Request $request, User $user)
    {
        $rules = $this->rules(
            ignoreUserId: $user->id,
            ignoreProfileId: optional($user->profile)->id,
        );

        $data = $request->validate($rules, [], $this->attributes());

        $profileData = $this->normalizeProfile($data['profile']);

        $user->update([
            'email' => $data['email'],
            'role' => $data['role'],
            'status' => $data['status'],
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $profileData
        );

        return back()->with('success', 'Account updated successfully.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return back()->with('success', 'Account deleted successfully.');
    }

    /* ------------------------------------------------------------------ */
    /* Helpers */
    /* ------------------------------------------------------------------ */

    /**
     * Shared validation rules for store() and update().
     */
    private function rules(?string $ignoreUserId = null, ?string $ignoreProfileId = null): array
    {
        // For updates, ignore the current record on unique checks
        $emailUnique = $ignoreUserId
            ? Rule::unique('users', 'email')->ignore($ignoreUserId)
            : Rule::unique('users', 'email');

        $contactUnique = $ignoreProfileId
            ? Rule::unique('profiles', 'contact')->ignore($ignoreProfileId)
            : Rule::unique('profiles', 'contact');

        $rules = [
            'email' => ['required', 'string', 'email:rfc,dns', 'max:255', $emailUnique],
            'role' => ['required', Rule::in(['admin', 'staff', 'cashier'])],
            'status' => ['required', Rule::in(['active', 'inactive', 'suspended'])],

            'profile.firstname' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{L}\s\'\-\.]+$/u'],
            'profile.middlename' => ['nullable', 'string', 'min:2', 'max:100', 'regex:/^[\p{L}\s\'\-\.]+$/u'],
            'profile.lastname' => ['required', 'string', 'min:2', 'max:100', 'regex:/^[\p{L}\s\'\-\.]+$/u'],
            'profile.suffix' => ['nullable', 'string', 'max:20', 'regex:/^[\p{L}\s\'\-\.]+$/u'],
            'profile.contact' => ['required', 'string', 'min:7', 'max:20', 'regex:/^[0-9+\-\s()]+$/', $contactUnique],
            'profile.birthdate' => ['nullable', 'date', 'before:today'],
            'profile.address' => ['nullable', 'string', 'max:255'],
        ];

        // Only enforce the duplicate name rule on the four name fields together
        $rules['profile.firstname'][] = new UniqueProfileName($ignoreProfileId);

        // Password rules only apply on create
        if (! $ignoreUserId) {
            $rules['password'] = ['required', 'string', 'min:8', 'confirmed', 'max:255'];
        }

        return $rules;
    }

    /**
     * Friendly attribute names for error messages.
     */
    private function attributes(): array
    {
        return [
            'email' => 'email address',
            'password' => 'password',
            'role' => 'role',
            'status' => 'status',
            'profile.firstname' => 'first name',
            'profile.middlename' => 'middle name',
            'profile.lastname' => 'last name',
            'profile.suffix' => 'suffix',
            'profile.contact' => 'contact number',
            'profile.birthdate' => 'birthdate',
            'profile.address' => 'address',
        ];
    }

    /**
     * Trim + title-case the four name fields. Null-out empty birthdate.
     */
    private function normalizeProfile(array $profile): array
    {
        $profile['firstname'] = $this->titleCase($profile['firstname'] ?? null);
        $profile['middlename'] = $this->titleCase($profile['middlename'] ?? null);
        $profile['lastname'] = $this->titleCase($profile['lastname'] ?? null);
        $profile['suffix'] = $this->titleCase($profile['suffix'] ?? null);

        if (empty($profile['birthdate'])) {
            $profile['birthdate'] = null;
        }

        return $profile;
    }

    /**
     * "juan carlos" → "Juan Carlos"
     * "o'brien" → "O'Brien"
     * "dela-cruz" → "Dela-Cruz"
     */
    private function titleCase(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim($value);

        return $value === ''
            ? null
            : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }
}
