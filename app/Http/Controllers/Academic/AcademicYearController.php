<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Term;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display academic years and terms.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', AcademicYear::class);

        $academicYears = AcademicYear::with(['terms' => fn ($q) => $q->orderBy('semester')])
            ->latest('starts_on')
            ->get();

        $activeTerm = Term::with('academicYear')->where('status', 'active')->first();

        return Inertia::render('Admin/Academic/Years/Index', [
            'academicYears' => $academicYears,
            'activeTerm' => $activeTerm,
        ]);
    }

    /**
     * Store a new academic year.
     */
    public function storeYear(Request $request): RedirectResponse
    {
        $this->authorize('create', AcademicYear::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:32', 'unique:academic_years,name'],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated) {
                return AcademicYear::create($validated);
            },
            auditFactory: fn (AcademicYear $year) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_ACADEMIC_YEAR',
                'entity_type' => AcademicYear::class,
                'entity_id' => $year->id,
                'scope' => AuditScope::TERM,
                'changes' => $validated,
                'reason' => "Tahun ajaran baru {$year->name} dibuat",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Tahun ajaran berhasil dibuat.');
    }

    /**
     * Store a new term/semester for an academic year.
     */
    public function storeTerm(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->authorize('update', $academicYear);

        $validated = $request->validate([
            'semester' => [
                'required',
                'integer',
                'in:1,2',
                Rule::unique('terms')->where('academic_year_id', $academicYear->id),
            ],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after:starts_on'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($academicYear, $validated) {
                return $academicYear->terms()->create([
                    'semester' => $validated['semester'],
                    'starts_on' => $validated['starts_on'],
                    'ends_on' => $validated['ends_on'],
                    'status' => 'draft',
                ]);
            },
            auditFactory: fn (Term $term) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_TERM',
                'entity_type' => Term::class,
                'entity_id' => $term->id,
                'scope' => AuditScope::TERM,
                'changes' => $validated,
                'reason' => "Semester {$term->semester} untuk {$academicYear->name} dibuat",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Semester berhasil dibuat.');
    }

    /**
     * Activate a term (ensuring only one term is active school-wide).
     */
    public function activateTerm(Request $request, Term $term): RedirectResponse
    {
        $this->authorize('update', $term->academicYear);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($term) {
                // Deactivate any currently active terms
                Term::where('status', 'active')
                    ->where('id', '!=', $term->id)
                    ->update(['status' => 'closed']);

                $term->update(['status' => 'active']);

                return $term;
            },
            auditFactory: fn (Term $activatedTerm) => [
                'actor_id' => $actor?->id,
                'action' => 'ACTIVATE_TERM',
                'entity_type' => Term::class,
                'entity_id' => $activatedTerm->id,
                'scope' => AuditScope::TERM,
                'changes' => ['status' => 'active'],
                'reason' => "Aktivasi semester aktif sekolah: {$activatedTerm->full_name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        rescue(fn () => Cache::forget('active_academic_term'), report: false);

        return back()->with('success', "Semester {$term->full_name} berhasil diaktifkan sebagai periode aktif sekolah.");
    }
}
