<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Http\Controllers\Controller;
use App\Models\GradingPolicy;
use App\Models\ScoringComponent;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradingPolicyController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display global grading policies and scoring components catalog.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', GradingPolicy::class);

        $activePolicy = GradingPolicy::with(['components.scoringComponent', 'createdBy', 'activatedBy'])
            ->where('status', 'active')
            ->first();

        $draftPolicies = GradingPolicy::with(['components.scoringComponent', 'createdBy'])
            ->where('status', 'draft')
            ->latest('revision')
            ->get();

        $retiredPolicies = GradingPolicy::with(['components.scoringComponent', 'createdBy', 'activatedBy'])
            ->where('status', 'retired')
            ->orderByDesc('activated_at')
            ->get();

        $scoringComponents = ScoringComponent::orderBy('sort_order')->get();

        return Inertia::render('Admin/Academic/GradingPolicies/Index', [
            'activePolicy' => $activePolicy,
            'draftPolicies' => $draftPolicies,
            'retiredPolicies' => $retiredPolicies,
            'scoringComponents' => $scoringComponents,
        ]);
    }

    /**
     * Create a new draft grading policy.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', GradingPolicy::class);

        $validated = $request->validate([
            'kkm' => ['required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.scoring_component_id' => ['required', 'exists:scoring_components,id', 'distinct'],
            'components.*.weight_percentage' => ['required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ], [
            'kkm.regex' => 'KKM maksimal memiliki 2 angka desimal (Q7.5).',
            'components.*.weight_percentage.regex' => 'Bobot komponen maksimal memiliki 2 angka desimal (Q7.5).',
            'components.*.scoring_component_id.distinct' => 'Komponen penilaian tidak boleh diduplikasi dalam satu kebijakan.',
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated, $actor) {
                $nextRevision = ((int) GradingPolicy::max('revision')) + 1;

                $policy = GradingPolicy::create([
                    'revision' => $nextRevision,
                    'kkm' => $validated['kkm'],
                    'status' => 'draft',
                    'created_by' => $actor?->id,
                ]);

                foreach ($validated['components'] as $comp) {
                    $policy->components()->create([
                        'scoring_component_id' => $comp['scoring_component_id'],
                        'weight_percentage' => $comp['weight_percentage'],
                    ]);
                }

                return $policy;
            },
            auditFactory: fn (GradingPolicy $policy) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_GRADING_POLICY_DRAFT',
                'entity_type' => GradingPolicy::class,
                'entity_id' => $policy->id,
                'scope' => AuditScope::SYSTEM,
                'changes' => [
                    'revision' => $policy->revision,
                    'kkm' => $policy->kkm,
                    'status' => 'draft',
                ],
                'reason' => "Pembuatan draf kebijakan penilaian revisi ke-{$policy->revision} (KKM: {$policy->kkm})",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Draf kebijakan penilaian berhasil dibuat.');
    }

    /**
     * Update an existing draft grading policy.
     */
    public function update(Request $request, GradingPolicy $policy): RedirectResponse
    {
        $this->authorize('update', $policy);

        if (! $policy->isDraft()) {
            return back()->withErrors([
                'policy' => 'Kebijakan yang sudah aktif atau diarsipkan bersifat permanen dan tidak dapat diubah.',
            ]);
        }

        $validated = $request->validate([
            'kkm' => ['required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
            'components' => ['required', 'array', 'min:1'],
            'components.*.scoring_component_id' => ['required', 'exists:scoring_components,id', 'distinct'],
            'components.*.weight_percentage' => ['required', 'numeric', 'between:0,100', 'regex:/^\d+(\.\d{1,2})?$/'],
        ], [
            'kkm.regex' => 'KKM maksimal memiliki 2 angka desimal (Q7.5).',
            'components.*.weight_percentage.regex' => 'Bobot komponen maksimal memiliki 2 angka desimal (Q7.5).',
            'components.*.scoring_component_id.distinct' => 'Komponen penilaian tidak boleh diduplikasi dalam satu kebijakan.',
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($policy, $validated) {
                $policy->update([
                    'kkm' => $validated['kkm'],
                ]);

                // Delete old components and recreate
                $policy->components()->delete();
                foreach ($validated['components'] as $comp) {
                    $policy->components()->create([
                        'scoring_component_id' => $comp['scoring_component_id'],
                        'weight_percentage' => $comp['weight_percentage'],
                    ]);
                }

                return $policy;
            },
            auditFactory: fn (GradingPolicy $p) => [
                'actor_id' => $actor?->id,
                'action' => 'UPDATE_GRADING_POLICY_DRAFT',
                'entity_type' => GradingPolicy::class,
                'entity_id' => $p->id,
                'scope' => AuditScope::SYSTEM,
                'changes' => [
                    'revision' => $p->revision,
                    'kkm' => $p->kkm,
                ],
                'reason' => "Pembaruan draf kebijakan penilaian revisi ke-{$p->revision}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Draf kebijakan penilaian berhasil diperbarui.');
    }

    /**
     * Activate a draft grading policy.
     * Enforces total weight must be exactly 100.00% (F-04).
     * Retires the previously active policy atomically.
     */
    public function activate(Request $request, GradingPolicy $policy): RedirectResponse
    {
        $this->authorize('activate', $policy);

        if (! $policy->isDraft()) {
            return back()->withErrors(['activation' => 'Hanya kebijakan berstatus draf yang dapat diaktifkan.']);
        }

        // Validate total weight must equal exactly 100.00%
        $policy->load('components.scoringComponent');
        $totalWeight = $policy->components->sum(fn ($c) => (float) $c->weight_percentage);

        // Precise comparison without rounding epsilon (Q7.5, D-22)
        if (bccomp(number_format($totalWeight, 2, '.', ''), '100.00', 2) !== 0) {
            return back()->withErrors([
                'activation' => "Total bobot komponen harus tepat 100.00%. Saat ini total adalah {$totalWeight}%.",
            ]);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($policy, $actor) {
                // 1. Lock existing active policy and retire it (ensures max 1 active globally)
                $currentActive = GradingPolicy::where('status', 'active')
                    ->lockForUpdate()
                    ->first();

                if ($currentActive) {
                    $currentActive->update([
                        'status' => 'retired',
                    ]);
                }

                // 2. Activate new policy
                $policy->update([
                    'status' => 'active',
                    'activated_by' => $actor?->id,
                    'activated_at' => now(),
                ]);

                return $policy;
            },
            auditFactory: fn (GradingPolicy $p) => [
                'actor_id' => $actor?->id,
                'action' => 'ACTIVATE_GRADING_POLICY',
                'entity_type' => GradingPolicy::class,
                'entity_id' => $p->id,
                'scope' => AuditScope::SYSTEM,
                'changes' => [
                    'revision' => $p->revision,
                    'kkm' => $p->kkm,
                    'status' => 'active',
                ],
                'reason' => "Aktivasi kebijakan penilaian global revisi ke-{$p->revision} (KKM: {$p->kkm}, Total Bobot: 100.00%)",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Kebijakan penilaian revisi ke-{$policy->revision} berhasil diaktifkan untuk seluruh sekolah.");
    }

    /**
     * Add a scoring component to the school catalog.
     */
    public function storeComponent(Request $request): RedirectResponse
    {
        $this->authorize('manageComponents', GradingPolicy::class);

        $validated = $request->validate([
            'code' => ['required', 'alpha_dash', 'unique:scoring_components,code'],
            'name' => ['required', 'string', 'max:255'],
            'source_type' => ['required', 'in:score,attendance'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($validated['source_type'] === 'attendance') {
            $alreadyHasAttendance = ScoringComponent::where('source_type', 'attendance')->exists();
            if ($alreadyHasAttendance) {
                return back()->withErrors([
                    'source_type' => 'Hanya diperbolehkan 1 komponen sistem dengan tipe kehadiran.',
                ]);
            }
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated) {
                return ScoringComponent::create([
                    'code' => strtolower($validated['code']),
                    'name' => $validated['name'],
                    'source_type' => $validated['source_type'],
                    'sort_order' => $validated['sort_order'] ?? ((int) ScoringComponent::max('sort_order') + 1),
                    'is_active' => true,
                ]);
            },
            auditFactory: fn (ScoringComponent $c) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_SCORING_COMPONENT',
                'entity_type' => ScoringComponent::class,
                'entity_id' => $c->id,
                'scope' => AuditScope::SYSTEM,
                'changes' => [
                    'code' => $c->code,
                    'name' => $c->name,
                    'source_type' => $c->source_type,
                ],
                'reason' => "Penambahan komponen penilaian: {$c->name} ({$c->code})",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Komponen penilaian {$validated['name']} berhasil ditambahkan ke katalog.");
    }

    /**
     * Toggle active state of a scoring component.
     */
    public function toggleComponent(Request $request, ScoringComponent $component): RedirectResponse
    {
        $this->authorize('manageComponents', GradingPolicy::class);

        $actor = $request->user();
        $newStatus = ! $component->is_active;

        $this->auditService->executeAtomic(
            operation: function () use ($component, $newStatus) {
                $component->update(['is_active' => $newStatus]);

                return $component;
            },
            auditFactory: fn (ScoringComponent $c) => [
                'actor_id' => $actor?->id,
                'action' => 'TOGGLE_SCORING_COMPONENT',
                'entity_type' => ScoringComponent::class,
                'entity_id' => $c->id,
                'scope' => AuditScope::SYSTEM,
                'changes' => ['is_active' => $newStatus],
                'reason' => "Status komponen {$c->name} diubah menjadi ".($newStatus ? 'aktif' : 'nonaktif'),
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Status komponen {$component->name} berhasil diperbarui.");
    }
}
