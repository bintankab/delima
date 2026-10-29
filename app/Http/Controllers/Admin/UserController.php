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
        $actor = $request->user();
        $search = mb_substr($request->string('search')->trim()->toString(), 0, 100);
        $perPage = $this->perPage($request);
        $sort = $this->resolveSort($request, ['name', 'email', 'phone', 'is_active']);

        $users = UserModel::query()
            ->with(['roles:id,name', 'organisasis:id,name'])
            ->when(! $actor->hasRole('super-admin'), function ($query) use ($actor): void {
                $ownOrganisasiIds = $actor->organisasis()->pluck('organisasis.id');

                $query->whereHas('roles', fn ($roleQuery) => $roleQuery->where('name', 'asn'))
                    ->whereHas('organisasis', fn ($orgQuery) => $orgQuery->whereIn('organisasis.id', $ownOrganisasiIds));
            })
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

    public function create(Request $request): Response
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super-admin');

        return Inertia::render('Admin/Users/Form', [
            'meta' => ['title' => $isSuperAdmin ? 'Tambah Pengguna' : 'Tambah Staf ASN'],
            'user' => null,
            'roles' => $this->roleOptions($actor),
            'organisasis' => $isSuperAdmin ? $this->organisasiOptions() : [],
            'lockedRole' => $isSuperAdmin ? null : 'asn',
        ]);
    }

    public function store(UserRequest $request): RedirectResponse
    {
        $actor = $request->user();
        $validated = $request->safe()->only(['name', 'email', 'phone', 'password', 'is_active']);

        $user = UserModel::create([...$validated, 'email_verified_at' => now()]);

        if ($actor->hasRole('super-admin')) {
            $user->syncRoles([$request->validated('role')]);
            $this->syncOrganisasi($user, $request->validated('role'), $request->validated('organisasi_id'));
        } else {
            $user->syncRoles(['asn']);
            $this->syncOrganisasi($user, 'asn', $actor->organisasis()->first()?->id);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, UserModel $user): Response
    {
        $actor = $request->user();
        $isSuperAdmin = $actor->hasRole('super-admin');

        if (! $isSuperAdmin) {
            abort_unless($this->isManageableAsn($actor, $user), 403);
        }

        $user->load('roles:id,name', 'organisasis:id,name');

        return Inertia::render('Admin/Users/Form', [
            'meta' => ['title' => $isSuperAdmin ? 'Ubah Pengguna' : 'Ubah Staf ASN'],
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
                'organisasi_id' => $user->organisasis->first()?->id,
            ],
            'roles' => $this->roleOptions($actor),
            'organisasis' => $isSuperAdmin ? $this->organisasiOptions() : [],
            'lockedRole' => $isSuperAdmin ? null : 'asn',
        ]);
    }

    public function update(UserRequest $request, UserModel $user): RedirectResponse
    {
        $actor = $request->user();

        if (! $actor->hasRole('super-admin')) {
            abort_unless($this->isManageableAsn($actor, $user), 403);
        }

        $validated = $request->safe()->only(['name', 'email', 'phone', 'is_active']);

        if ($request->filled('password')) {
            $validated['password'] = $request->validated('password');
        }

        $user->update($validated);

        if ($actor->hasRole('super-admin')) {
            $user->syncRoles([$request->validated('role')]);
            $this->syncOrganisasi($user, $request->validated('role'), $request->validated('organisasi_id'));
        } else {
            $user->syncRoles(['asn']);
            $this->syncOrganisasi($user, 'asn', $actor->organisasis()->first()?->id);
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, UserModel $user): RedirectResponse
    {
        $actor = $request->user();

        if ($user->id === $actor->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        if (! $actor->hasRole('super-admin')) {
            abort_unless($this->isManageableAsn($actor, $user), 403);
        }

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    /**
     * Whether an admin-opd actor is allowed to manage the given user:
     * must be an ASN belonging to the actor's own organisasi.
     */
    private function isManageableAsn(UserModel $actor, UserModel $user): bool
    {
        $ownOrganisasiIds = $actor->organisasis()->pluck('organisasis.id');

        return $user->hasRole('asn')
            && $user->organisasis()->whereIn('organisasis.id', $ownOrganisasiIds)->exists();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    private function roleOptions(UserModel $actor): array
    {
        $labels = [
            'super-admin' => 'Super Admin',
            'admin-opd' => 'Admin OPD',
            'reviewer' => 'Reviewer',
            'bupati' => 'Bupati',
            'dprd' => 'DPRD',
            'asn' => 'ASN',
            'publik' => 'Masyarakat',
        ];

        return array_map(
            fn (string $role): array => ['id' => $role, 'name' => $labels[$role]],
            UserRequest::assignableRolesFor($actor),
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

    private function syncOrganisasi(UserModel $user, ?string $role, ?int $organisasiId): void
    {
        if (! in_array($role, ['admin-opd', 'asn'], true) || $organisasiId === null) {
            $user->organisasis()->sync([]);

            return;
        }

        $user->organisasis()->sync([$organisasiId => ['is_primary' => true]]);
    }
}
