<?php

namespace App\Http\Controllers\Academic;

use App\Enums\ProgressionStatus;
use App\Enums\ProgressionType;
use App\Enums\Role;
use App\Exceptions\SourceDataChangedException;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Classroom;
use App\Models\StudentProgression;
use App\Services\Academic\ProgressionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ProgressionController extends Controller
{
    public function __construct(
        protected ProgressionService $progressionService
    ) {}

    /**
     * List progression proposals with filters.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $this->authorize('viewAny', StudentProgression::class);

        $query = StudentProgression::with([
            'student:id,nis,name,gender',
            'sourceAcademicYear:id,name',
            'sourceReportCard.enrollment.classroom:id,grade_level,section,term_id',
            'targetAcademicYear:id,name',
            'targetClassroom:id,grade_level,section,term_id',
            'proposedBy:id,name',
            'approvedBy:id,name',
        ]);

        if ($request->filled('source_academic_year_id')) {
            $query->where('source_academic_year_id', $request->integer('source_academic_year_id'));
        }

        if ($request->filled('classroom_id')) {
            $classroomId = $request->integer('classroom_id');
            $query->whereHas('sourceReportCard.enrollment', fn ($q) => $q->where('classroom_id', $classroomId));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->string('type')->value());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->value());
        }

        $user = $request->user();
        if ($user && $user->hasRole(Role::TEACHER) && ! $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])) {
            $homeroomClassroomIds = $user->homeroomAssignments()->where('status', 'active')->pluck('classroom_id');
            $query->whereHas('sourceReportCard.enrollment', fn ($q) => $q->whereIn('classroom_id', $homeroomClassroomIds));
        }

        $progressions = $query->latest('id')->paginate(20)->withQueryString();

        if ($request->wantsJson()) {
            return response()->json($progressions);
        }

        $classroomsQuery = Classroom::with('term:id,academic_year_id,semester,status');
        if ($user && $user->hasRole(Role::TEACHER) && ! $user->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::PRINCIPAL])) {
            $homeroomClassroomIds = $user->homeroomAssignments()->where('status', 'active')->pluck('classroom_id');
            $classroomsQuery->whereIn('id', $homeroomClassroomIds);
        }
        $classrooms = $classroomsQuery->get(['id', 'grade_level', 'section', 'term_id']);

        return Inertia::render('Admin/Academic/Progressions/Index', [
            'progressions' => $progressions,
            'filters' => $request->only(['source_academic_year_id', 'classroom_id', 'type', 'status']),
            'academicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name']),
            'classrooms' => $classrooms,
            'types' => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], ProgressionType::cases()),
            'statuses' => array_map(fn ($case) => ['value' => $case->value, 'label' => $case->label()], ProgressionStatus::cases()),
        ]);
    }

    /**
     * Create an individual or batch progression proposal.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', StudentProgression::class);

        // Check if it's a batch creation for a classroom
        if ($request->filled('classroom_id') && ! $request->filled('student_id')) {
            $validated = $request->validate([
                'classroom_id' => ['required', 'exists:classrooms,id'],
                'type' => ['required', 'string', 'in:promotion,graduation,retention'],
                'source_academic_year_id' => ['nullable', 'exists:academic_years,id'],
                'target_academic_year_id' => ['nullable', 'exists:academic_years,id'],
                'target_classroom_id' => ['nullable', 'exists:classrooms,id'],
                'reason' => ['nullable', 'string', 'max:1000'],
            ]);

            $result = $this->progressionService->batchCreateProposals(
                classroomId: (int) $validated['classroom_id'],
                commonData: $validated,
                actor: $request->user()
            );

            $msg = "Berhasil membuat {$result['created_count']} draf usulan ({$result['skipped_count']} siswa dilewati).";

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $msg,
                    'created_count' => $result['created_count'],
                    'skipped_count' => $result['skipped_count'],
                ], 201);
            }

            return back()->with('success', $msg);
        }

        // Individual proposal creation
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
            'source_academic_year_id' => ['required', 'exists:academic_years,id'],
            'source_report_card_id' => ['nullable', 'exists:report_cards,id'],
            'type' => ['required', 'string', 'in:promotion,graduation,retention'],
            'target_academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'target_classroom_id' => ['nullable', 'exists:classrooms,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $progression = $this->progressionService->createProposal(
            data: $validated,
            actor: $request->user()
        );

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Draf usulan kenaikan/kelulusan berhasil dibuat.',
                'data' => $progression,
            ], 201);
        }

        return redirect()->route('academic.progressions.show', $progression)
            ->with('success', 'Draf usulan kenaikan/kelulusan berhasil dibuat.');
    }

    /**
     * Show detail of a progression proposal.
     */
    public function show(Request $request, StudentProgression $progression): InertiaResponse|JsonResponse
    {
        $this->authorize('view', $progression);

        $progression->load([
            'student',
            'sourceAcademicYear',
            'sourceReportCard.enrollment.classroom',
            'sourceReportCard.items.classSubject.subject',
            'targetAcademicYear',
            'targetClassroom',
            'proposedBy:id,name',
            'approvedBy:id,name',
            'appliedBy:id,name',
        ]);

        if ($request->wantsJson()) {
            return response()->json(['data' => $progression]);
        }

        return Inertia::render('Admin/Academic/Progressions/Show', [
            'progression' => $progression,
            'targetClassrooms' => Classroom::with('term')->get(['id', 'grade_level', 'section', 'term_id']),
            'targetAcademicYears' => AcademicYear::orderByDesc('starts_on')->get(['id', 'name']),
        ]);
    }

    /**
     * Update an existing draft/needs_review progression proposal.
     */
    public function update(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $progression);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'type' => ['required', 'string', 'in:promotion,graduation,retention'],
            'target_academic_year_id' => ['nullable', 'exists:academic_years,id'],
            'target_classroom_id' => ['nullable', 'exists:classrooms,id'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $progression = $this->progressionService->updateProposal(
            progression: $progression,
            data: $validated,
            expectedVersion: (int) $validated['expected_version'],
            actor: $request->user()
        );

        $msg = 'Draf usulan berhasil diperbarui.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $progression,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Delete/cancel a draft progression proposal.
     */
    public function destroy(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $progression);

        $expectedVersion = $request->input('expected_version');

        $this->progressionService->deleteProposal(
            progression: $progression,
            expectedVersion: $expectedVersion !== null ? (int) $expectedVersion : null,
            actor: $request->user()
        );

        $msg = 'Draf usulan berhasil dibatalkan.';

        if ($request->wantsJson()) {
            return response()->json(['message' => $msg]);
        }

        return redirect()->route('academic.progressions.index')->with('success', $msg);
    }

    /**
     * Submit a draft progression proposal for Principal approval.
     */
    public function submit(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('submit', $progression);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
        ]);

        try {
            $progression = $this->progressionService->submitProposal(
                progression: $progression,
                expectedVersion: (int) $validated['expected_version'],
                actor: $request->user()
            );
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['source' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['source' => $e->getMessage()]);
        }

        $msg = 'Usulan berhasil diajukan untuk ditinjau oleh Kepala Madrasah.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $progression,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Approve a progression proposal (Principal only, AC-09.1).
     */
    public function approve(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('approve', $progression);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'expected_source_report_id' => ['nullable', 'integer'],
            'expected_source_report_revision' => ['nullable', 'integer'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $progression = $this->progressionService->approveProposal(
                progression: $progression,
                data: $validated,
                actor: $request->user()
            );
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['source' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['source' => $e->getMessage()]);
        }

        $msg = 'Usulan kenaikan/kelulusan berhasil disetujui.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $progression,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Reject a progression proposal (Principal only, AC-09.1).
     */
    public function reject(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('reject', $progression);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $progression = $this->progressionService->rejectProposal(
            progression: $progression,
            data: $validated,
            actor: $request->user()
        );

        $msg = 'Usulan kenaikan/kelulusan telah ditolak.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $progression,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Batch approve progression proposals (Principal only, AC-09.3).
     */
    public function batchApprove(Request $request): RedirectResponse|JsonResponse
    {
        if (! $request->user()->hasRole(Role::PRINCIPAL)) {
            abort(403, 'Hanya Kepala Madrasah yang berwenang menyetujui usulan kenaikan/kelulusan.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.progression_id' => ['required', 'integer', 'exists:student_progressions,id'],
            'items.*.expected_version' => ['required', 'integer'],
            'items.*.expected_source_report_id' => ['nullable', 'integer'],
            'items.*.expected_source_report_revision' => ['nullable', 'integer'],
        ]);

        try {
            $result = $this->progressionService->batchApproveProposals(
                items: $validated['items'],
                actor: $request->user()
            );
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['items' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['items' => $e->getMessage()]);
        }

        $msg = "Berhasil menyetujui {$result['approved_count']} usulan secara massal.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'approved_count' => $result['approved_count'],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Batch reject progression proposals (Principal only).
     */
    public function batchReject(Request $request): RedirectResponse|JsonResponse
    {
        if (! $request->user()->hasRole(Role::PRINCIPAL)) {
            abort(403, 'Hanya Kepala Madrasah yang berwenang menolak usulan kenaikan/kelulusan.');
        }

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.progression_id' => ['required', 'integer', 'exists:student_progressions,id'],
            'items.*.expected_version' => ['required', 'integer'],
        ]);

        $result = $this->progressionService->batchRejectProposals(
            items: $validated['items'],
            reason: $validated['reason'],
            actor: $request->user()
        );

        $msg = "Berhasil menolak {$result['rejected_count']} usulan secara massal.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'rejected_count' => $result['rejected_count'],
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Apply an approved progression proposal (Master Admin or Academic Admin only, AC-09.2).
     */
    public function apply(Request $request, StudentProgression $progression): RedirectResponse|JsonResponse
    {
        $this->authorize('apply', $progression);

        $validated = $request->validate([
            'expected_version' => ['required', 'integer'],
        ]);

        try {
            $progression = $this->progressionService->applyProposal(
                progression: $progression,
                expectedVersion: (int) $validated['expected_version'],
                actor: $request->user()
            );
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['source' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['source' => $e->getMessage()]);
        }

        $msg = 'Keputusan mutasi akademik berhasil diterapkan.';

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'data' => $progression,
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Batch apply approved progression proposals (Master Admin or Academic Admin only).
     */
    public function batchApply(Request $request): RedirectResponse|JsonResponse
    {
        if (! $request->user()->hasAnyRole([Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN])) {
            abort(403, 'Hanya Staf Administratif yang berwenang menerapkan keputusan kenaikan/kelulusan.');
        }

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.progression_id' => ['required', 'integer', 'exists:student_progressions,id'],
            'items.*.expected_version' => ['required', 'integer'],
        ]);

        try {
            $result = $this->progressionService->batchApplyProposals(
                items: $validated['items'],
                actor: $request->user()
            );
        } catch (SourceDataChangedException $e) {
            if ($request->wantsJson()) {
                return response()->json(['message' => $e->getMessage(), 'errors' => ['items' => [$e->getMessage()]]], 422);
            }

            return back()->withErrors(['items' => $e->getMessage()]);
        }

        $msg = "Berhasil menerapkan {$result['applied_count']} keputusan kenaikan/kelulusan.";

        if ($request->wantsJson()) {
            return response()->json([
                'message' => $msg,
                'applied_count' => $result['applied_count'],
            ]);
        }

        return back()->with('success', $msg);
    }
}
