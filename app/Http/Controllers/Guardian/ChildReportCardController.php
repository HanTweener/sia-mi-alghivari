<?php

namespace App\Http\Controllers\Guardian;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\ReportCard;
use App\Models\Student;
use App\Services\Reports\ReportPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ChildReportCardController extends Controller
{
    public function __construct(
        protected ReportPdfService $reportPdfService
    ) {}

    /**
     * Display published report cards for a child (SCR-09).
     */
    public function index(Request $request, Student $student): Response
    {
        $this->authorize('viewReportCards', $student);

        $user = $request->user();

        // Load active classroom for context
        $activeEnrollment = $student->enrollments()
            ->where('status', 'active')
            ->with(['classroom.term.academicYear', 'classroom.activeHomeroom.teacher'])
            ->first();

        if ($activeEnrollment && ! $user->can('viewProgress', [$student, $activeEnrollment])) {
            $activeEnrollment = null;
        }

        // Query only published and current report cards (Zero Leak)
        $enrollmentIds = $student->enrollments()->pluck('id');
        $reportCards = ReportCard::whereIn('enrollment_id', $enrollmentIds)
            ->where('status', ReportCardStatus::PUBLISHED)
            ->where('availability', ReportCardAvailability::CURRENT)
            ->with([
                'term.academicYear',
                'enrollment.classroom',
                'homeroomAssignment.teacher',
                'items.classSubject.subject',
            ])
            ->orderBy('id', 'desc')
            ->get()
            ->filter(fn (ReportCard $report) => $user->can('view', $report))
            ->values();

        $reportsData = $reportCards->map(function (ReportCard $rc) {
            $totalItems = $rc->items->count();
            $passedItems = $rc->items->where('kkm_status', 'tuntas')->count();
            $avgScore = $totalItems > 0 ? (float) $rc->items->avg('final_score') : null;

            return [
                'id' => $rc->id,
                'revision' => $rc->revision,
                'published_at' => $rc->published_at?->format('d M Y'),
                'published_at_raw' => $rc->published_at?->toIso8601String(),
                'classroom_name' => $rc->enrollment?->classroom?->name ?? '-',
                'term' => [
                    'id' => $rc->term->id,
                    'name' => $rc->term->name,
                    'semester' => $rc->term->semester,
                    'academic_year' => $rc->term->academicYear?->name ?? '-',
                ],
                'homeroom_teacher' => $rc->homeroomAssignment?->teacher?->name ?? $rc->identity_snapshot['homeroom_teacher']['name'] ?? '-',
                'summary' => [
                    'total_subjects' => $totalItems,
                    'passed_subjects' => $passedItems,
                    'average_score' => $avgScore ? number_format($avgScore, 1) : null,
                ],
                'has_file' => ! empty($rc->private_file_path),
            ];
        });

        $siblingChildren = $user->hasRole(Role::GUARDIAN)
            ? $user->children()->where('students.status', 'active')->get(['students.id', 'students.nis', 'students.name'])
            : [];

        return Inertia::render('Guardian/Reports/Index', [
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
            ],
            'classroom' => $activeEnrollment?->classroom ? [
                'id' => $activeEnrollment->classroom->id,
                'name' => $activeEnrollment->classroom->name,
                'grade_level' => $activeEnrollment->classroom->grade_level,
                'section' => $activeEnrollment->classroom->section,
            ] : null,
            'reports' => $reportsData,
            'siblingChildren' => $siblingChildren,
        ]);
    }

    /**
     * Display single published report card online detail.
     */
    public function show(Request $request, Student $student, ReportCard $report): Response
    {
        // Strict boundary check: report card must belong to this student
        if ($report->enrollment?->student_id !== $student->id) {
            abort(404, 'Rapor tidak ditemukan untuk siswa ini.');
        }

        // Zero Leak: Report card must be published and current
        if (! $report->isPublished() || ! $report->isCurrent()) {
            abort(404, 'Rapor resmi belum tersedia.');
        }

        $this->authorize('view', $report);

        $report->loadMissing([
            'enrollment.classroom',
            'term.academicYear',
            'items.classSubject.subject',
            'extracurricularAssessments',
            'p5Assessments',
            'homeroomAssignment.teacher',
        ]);

        $user = $request->user();
        $siblingChildren = $user->hasRole(Role::GUARDIAN)
            ? $user->children()->where('students.status', 'active')->get(['students.id', 'students.nis', 'students.name'])
            : [];

        return Inertia::render('Guardian/Reports/Show', [
            'student' => [
                'id' => $student->id,
                'nis' => $student->nis,
                'name' => $student->name,
                'gender' => $student->gender,
            ],
            'report' => [
                'id' => $report->id,
                'revision' => $report->revision,
                'status' => $report->status->value,
                'published_at' => $report->published_at?->format('d M Y'),
                'published_at_raw' => $report->published_at?->toIso8601String(),
                'homeroom_note' => $report->homeroom_note,
                'attendance_summary' => $report->attendance_summary,
                'identity_snapshot' => $report->identity_snapshot,
                'items' => $report->items->map(fn ($item) => [
                    'id' => $item->id,
                    'subject' => [
                        'id' => $item->classSubject?->subject?->id,
                        'name' => $item->subject_snapshot['name'] ?? $item->classSubject?->subject?->name ?? '-',
                        'code' => $item->subject_snapshot['code'] ?? $item->classSubject?->subject?->code ?? '-',
                    ],
                    'kkm' => $item->kkm_snapshot['kkm'] ?? '70.00',
                    'final_score' => $item->final_score,
                    'kkm_status' => $item->kkm_status,
                    'component_breakdown' => $item->component_breakdown_snapshot,
                ]),
                'extracurriculars' => $report->extracurricularAssessments->map(fn ($e) => [
                    'id' => $e->id,
                    'activity_name' => $e->activity_name,
                    'predicate' => $e->predicate,
                    'predicate_label' => $e->predicate_label,
                    'description' => $e->description,
                ]),
                'p5_assessments' => $report->p5Assessments->map(fn ($p) => [
                    'id' => $p->id,
                    'project_title' => $p->project_title,
                    'dimension' => $p->dimension,
                    'predicate' => $p->predicate,
                    'predicate_label' => $p->predicate_label,
                    'description' => $p->description,
                ]),
            ],
            'classroom' => [
                'id' => $report->enrollment?->classroom?->id,
                'name' => $report->identity_snapshot['classroom']['name'] ?? $report->enrollment?->classroom?->name ?? '-',
                'grade_level' => $report->identity_snapshot['classroom']['grade_level'] ?? $report->enrollment?->classroom?->grade_level,
                'section' => $report->identity_snapshot['classroom']['section'] ?? $report->enrollment?->classroom?->section,
            ],
            'term' => [
                'id' => $report->term->id,
                'name' => $report->term->name,
                'semester' => $report->term->semester,
                'academic_year' => $report->term->academicYear?->name ?? '-',
            ],
            'siblingChildren' => $siblingChildren,
        ]);
    }

    /**
     * Download or stream the official published report card PDF for guardian.
     */
    public function pdf(Request $request, Student $student, ReportCard $report)
    {
        // Strict boundary check: report card must belong to this student
        if ($report->enrollment?->student_id !== $student->id) {
            abort(404, 'Rapor tidak ditemukan untuk siswa ini.');
        }

        // Zero Leak: Report card must be published and current
        if (! $report->isPublished() || ! $report->isCurrent()) {
            abort(404, 'Berkas rapor resmi belum tersedia.');
        }

        $this->authorize('downloadPdf', $report);

        // Ensure file exists; auto prepare if missing
        if (! $report->private_file_path || ! Storage::disk('local')->exists($report->private_file_path)) {
            $this->reportPdfService->renderAndStore($report);
        }

        $nis = $student->nis ?? $report->id;
        $fileName = "rapor_{$nis}_rev{$report->revision}.pdf";

        return Storage::disk('local')->response(
            $report->private_file_path,
            $fileName,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="'.$fileName.'"',
            ]
        );
    }
}
