<?php

namespace App\Http\Controllers;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Show the user profile form.
     */
    public function edit(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('Profile/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'phone' => $user->phone,
                'address' => $user->address,
                'avatar_path' => $user->avatar_path,
                'roles' => $user->userRoles->pluck('role')->all(),
            ],
        ]);
    }

    /**
     * Update the user profile (strict allowlist: name, phone, address).
     */
    public function update(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:500'],
        ]);

        $changes = [];
        foreach ($validated as $key => $value) {
            if ($user->{$key} !== $value) {
                $changes[$key] = [
                    'old' => $user->{$key},
                    'new' => $value,
                ];
            }
        }

        $user->update($validated);

        if (! empty($changes)) {
            $this->auditService->record(
                actorUserId: $user->id,
                action: 'UPDATE_PROFILE',
                entityType: User::class,
                entityId: $user->id,
                scope: AuditScope::USER,
                allowedChanges: $changes,
                reason: 'Self-service profile update',
                visibility: AuditVisibility::INTERNAL
            );
        }

        return back()->with('success', 'Profil Anda berhasil diperbarui.');
    }
}
