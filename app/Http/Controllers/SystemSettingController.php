<?php

namespace App\Http\Controllers;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Models\SystemSetting;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SystemSettingController extends Controller
{
    /**
     * Allowed settings allowlist.
     */
    protected const ALLOWED_TIMEZONES = [
        'Asia/Jakarta',    // WIB
        'Asia/Makassar',   // WITA
        'Asia/Jayapura',   // WIT
    ];

    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display settings management page.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', SystemSetting::class);

        return Inertia::render('Admin/Settings/Index', [
            'settings' => [
                'school_timezone' => SystemSetting::get('school_timezone', 'Asia/Jakarta'),
                'school_name' => SystemSetting::get('school_name', 'MI Alghivari'),
                'school_address' => SystemSetting::get('school_address', ''),
                'school_phone' => SystemSetting::get('school_phone', ''),
            ],
            'allowedTimezones' => [
                ['value' => 'Asia/Jakarta', 'label' => 'WIB - Waktu Indonesia Barat (Asia/Jakarta)'],
                ['value' => 'Asia/Makassar', 'label' => 'WITA - Waktu Indonesia Tengah (Asia/Makassar)'],
                ['value' => 'Asia/Jayapura', 'label' => 'WIT - Waktu Indonesia Timur (Asia/Jayapura)'],
            ],
        ]);
    }

    /**
     * Update system settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $this->authorize('update', SystemSetting::class);

        $validated = $request->validate([
            'school_timezone' => ['required', 'string', 'in:'.implode(',', self::ALLOWED_TIMEZONES)],
            'school_name' => ['required', 'string', 'max:255'],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_phone' => ['nullable', 'string', 'max:32'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated, $actor) {
                foreach ($validated as $key => $value) {
                    SystemSetting::set(
                        key: $key,
                        value: $value,
                        updatedBy: $actor?->id,
                        description: "System setting {$key} updated by Master Admin"
                    );
                }

                return $validated;
            },
            auditFactory: fn (array $saved) => [
                'actor_id' => $actor?->id,
                'action' => 'UPDATE_SETTINGS',
                'entity_type' => SystemSetting::class,
                'entity_id' => 'global',
                'scope' => AuditScope::SYSTEM,
                'changes' => $saved,
                'reason' => 'Master Admin system settings update',
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Pengaturan sistem berhasil diperbarui.');
    }
}
