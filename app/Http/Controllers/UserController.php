<?php

namespace App\Http\Controllers;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Models\User;
use App\Services\Audit\AuditService;
use App\Services\Auth\PasswordLifecycleService;
use Fouladgar\OTP\Facades\OTP;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UserController extends Controller
{
    public function __construct(
        protected AuditService $auditService,
        protected PasswordLifecycleService $passwordService
    ) {}

    /**
     * Display a listing of users.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', User::class);

        $query = User::with('userRoles')->latest();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->whereHas('userRoles', fn ($q) => $q->where('role', $role));
        }

        $users = $query->paginate(15)->through(fn (User $user) => [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'is_active' => $user->is_active,
            'must_change_password' => $user->must_change_password,
            'roles' => $user->userRoles->pluck('role')->all(),
            'created_at' => $user->created_at?->toIso8601String(),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => $request->only(['search', 'role']),
            'availableRoles' => array_map(fn (Role $r) => [
                'value' => $r->value,
                'label' => $r->label(),
            ], Role::cases()),
        ]);
    }

    /**
     * Store a newly created user (Master Admin only).
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', User::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:64', 'alpha_dash', 'unique:users,username'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::enum(Role::class)],
        ]);

        $temporaryPassword = $this->passwordService->generateTemporaryPassword();
        $actor = $request->user();

        $result = $this->auditService->executeAtomic(
            operation: function () use ($validated, $temporaryPassword, $actor) {
                $user = User::create([
                    'name' => $validated['name'],
                    'username' => $validated['username'],
                    'email' => $validated['email'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'password' => Hash::make($temporaryPassword),
                    'must_change_password' => true,
                    'temporary_password_expires_at' => now()->addDays(7),
                    'email_verified_at' => now(),
                    'email_verified_by' => $actor?->id,
                    'email_verification_source' => 'master_attested',
                    'is_active' => true,
                ]);

                foreach ($validated['roles'] as $roleValue) {
                    $user->assignRole($roleValue);
                }

                return $user;
            },
            auditFactory: fn (User $newUser) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_USER',
                'entity_type' => User::class,
                'entity_id' => $newUser->id,
                'scope' => AuditScope::USER,
                'changes' => [
                    'username' => $newUser->username,
                    'email' => $newUser->email,
                    'roles' => $validated['roles'],
                    'must_change_password' => true,
                ],
                'reason' => 'Administrative user onboarding by Master Admin',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Akun {$result['result']->name} berhasil dibuat.")
            ->with('temporary_credentials', [
                'username' => $result['result']->username,
                'temporary_password' => $temporaryPassword,
            ]);
    }

    /**
     * Update user details and roles (Master Admin only).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorize('manageRoles', $user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['required', 'boolean'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', Rule::enum(Role::class)],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($user, $validated) {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                if ($user->is_active && ! $validated['is_active']) {
                    $this->revokeAccess($user);
                }

                $user->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'is_active' => $validated['is_active'],
                ]);

                // Synchronize roles
                $currentRoles = $user->userRoles->pluck('role')->all();
                $targetRoles = $validated['roles'];

                // Remove unselected
                foreach ($currentRoles as $role) {
                    if (! in_array($role, $targetRoles, true)) {
                        $user->removeRole($role);
                    }
                }

                // Add newly selected
                foreach ($targetRoles as $role) {
                    if (! in_array($role, $currentRoles, true)) {
                        $user->assignRole($role);
                    }
                }

                return $user;
            },
            auditFactory: fn (User $updatedUser) => [
                'actor_id' => $actor?->id,
                'action' => 'UPDATE_USER',
                'entity_type' => User::class,
                'entity_id' => $updatedUser->id,
                'scope' => AuditScope::USER,
                'changes' => [
                    'name' => $validated['name'],
                    'is_active' => $validated['is_active'],
                    'roles' => $validated['roles'],
                ],
                'reason' => 'Administrative user details and roles update',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Akun {$user->name} berhasil diperbarui.");
    }

    /**
     * Delete/deactivate user (Master Admin only).
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorize('delete', $user);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($user) {
                $user = User::query()->lockForUpdate()->findOrFail($user->id);
                $this->revokeAccess($user);
                $user->is_active = false;
                $user->save();

                return $user;
            },
            auditFactory: fn (User $deactivatedUser) => [
                'actor_id' => $actor?->id,
                'action' => 'DEACTIVATE_USER',
                'entity_type' => User::class,
                'entity_id' => $deactivatedUser->id,
                'scope' => AuditScope::USER,
                'changes' => ['is_active' => false],
                'reason' => 'Administrative deactivation by Master Admin',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Akun {$user->name} berhasil dinonaktifkan.");
    }

    private function revokeAccess(User $user): void
    {
        $user->update([
            'session_generation' => $user->session_generation + 1,
            'credential_version' => $user->credential_version + 1,
            'password_reset_proof_hash' => null,
            'password_reset_proof_expires_at' => null,
            'password_reset_proof_version' => null,
            'pending_session_choice_id' => null,
            'pending_session_choice_flow' => null,
        ]);
        OTP::purpose('password_reset')->revoke($user->email);
        DB::table('sessions')->where('user_id', $user->id)->delete();
    }
}
