<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UserRequest;
use App\Models\OrganisasiModel;
use App\Models\UserModel;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['name', 'email', 'phone', 'is_active']);

        $users = UserModel::query()
            ->with(['roles:id,name', 'organisasis:id,name'])
            ->when($search !== '', function ($query) use ($search): void {
                $escaped = '%'.addcslashes($search, '%_\\').'%';

                $query->where(function ($query) use ($escaped): void {
                    $query->where('name', 'like', $escaped)
                        ->orWhere('email', 'like', $escaped);
                });
            })
            ->when(
                $sort,
                fn ($query) => $query->orderBy($sort[0], $sort[1]),
                fn ($query) => $query->orderBy('name'),
            )
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (UserModel $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
                'organisasi' => $user->organisasis->first()?->name,
                'is_self' => $user->id === $request->user()->id,
            ]);

        return Inertia::render('Admin/Users/Index', [
            'meta' => ['title' => 'Pengguna'],
            'users' => $users,
            'filters' => [
                'search' => $search,
                'per_page' => $perPage,
                'sort' => $sort[0] ?? null,
                'direction' => $sort[1] ?? null,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Admin/Users/Form', [
            'meta' => ['title' => 'Tambah Pengguna'],
            'user' => null,
            'roles' => $this->roleOptions(),
            'organisasis' => $this->organisasiOptions(),
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $validated = $request->safe()->only(['name', 'email', 'phone', 'password', 'is_active']);

        $user = UserModel::create($validated);
        $user->syncRoles([$request->validated('role')]);
        $this->syncOrganisasi($user, $request);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(UserModel $user): Response
    {
        $user->load('roles:id,name', 'organisasis:id,name');

        return Inertia::render('Admin/Users/Form', [
            'meta' => ['title' => 'Ubah Pengguna'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
                'organisasi_id' => $user->organisasis->first()?->id,
            ],
            'roles' => $this->roleOptions(),
            'organisasis' => $this->organisasiOptions(),
        ]);
    }

    public function update(UserRequest $request, UserModel $user): RedirectResponse
    {
        $validated = $request->safe()->only(['name', 'email', 'phone', 'is_active']);

        if ($request->filled('password')) {
            $validated['password'] = $request->validated('password');
        }

        $user->update($validated);
        $user->syncRoles([$request->validated('role')]);
        $this->syncOrganisasi($user, $request);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, UserModel $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function roleOptions(): array
    {
        $labels = [
            'super-admin' => 'Super Admin',
            'admin-opd' => 'Admin OPD',
            'reviewer' => 'Reviewer',
        ];

        return array_map(
            fn (string $role): array => ['id' => $role, 'name' => $labels[$role]],
            UserRequest::ASSIGNABLE_ROLES,
        );
    }

    /**
     * @return list<array{id: int, name: string}>
     */
    private function organisasiOptions(): array
    {
        return OrganisasiModel::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (OrganisasiModel $organisasi): array => [
                'id' => $organisasi->id,
                'name' => $organisasi->name,
            ])
            ->all();
    }

    private function syncOrganisasi(UserModel $user, UserRequest $request): void
    {
        $organisasiId = $request->validated('organisasi_id');

        if ($request->validated('role') !== 'admin-opd' || $organisasiId === null) {
            $user->organisasis()->sync([]);

            return;
        }

        $user->organisasis()->sync([$organisasiId => ['is_primary' => true]]);
    }
}
