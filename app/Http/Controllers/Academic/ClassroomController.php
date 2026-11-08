<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\Term;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display classrooms listing.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Classroom::class);

        $user = $request->user();
        $activeTerm = rescue(
            fn () => Cache::remember('active_academic_term', 3600, fn () => Term::with('academicYear')->where('status', 'active')->first()),
            fn () => Term::with('academicYear')->where('status', 'active')->first(),
            report: false
        );
        $selectedTermId = $request->input('term_id', $activeTerm?->id);

        $terms = Term::with('academicYear:id,name')->latest('starts_on')->get(['id', 'academic_year_id', 'semester', 'status', 'starts_on', 'ends_on']);

        $query = Classroom::with(['term.academicYear:id,name', 'activeHomeroom.teacher:id,name'])
            ->when($selectedTermId, fn ($q) => $q->where('term_id', $selectedTermId))
            ->orderBy('grade_level')
            ->orderBy('section');

        // Scoping for teachers: Homeroom sees only their binaan class, pure subject teacher sees classes they teach
        if ($user && $user->hasRole(Role::TEACHER) && ! $user->hasAnyRole([
            Role::MASTER_ADMIN, Role::ACADEMIC_ADMIN, Role::DATA_ENTRY_ADMIN, Role::PRINCIPAL,
        ])) {
            $isHomeroom = $user->homeroomAssignments()->where('status', 'active')->exists();
            if ($isHomeroom) {
                $query->whereHas('activeHomeroom', fn ($q) => $q->where('teacher_id', $user->id));
            } else {
                $query->whereHas('classSubjects.teachingAssignments', fn ($q) => $q->where('teacher_id', $user->id)
                    ->active()
                );
            }
        }

        $classrooms = $query->get();

        return Inertia::render('Admin/Academic/Classrooms/Index', [
            'classrooms' => $classrooms,
            'terms' => $terms,
            'selectedTermId' => $selectedTermId ? (int) $selectedTermId : null,
            'activeTerm' => $activeTerm,
            'canCreate' => $user ? $user->can('create', Classroom::class) : false,
        ]);
    }

    /**
     * Store a new classroom.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Classroom::class);

        $validated = $request->validate([
            'term_id' => ['required', 'exists:terms,id'],
            'grade_level' => ['required', 'integer', 'between:1,6'],
            'section' => [
                'required',
                'string',
                'max:32',
                Rule::unique('classrooms')->where(function ($query) use ($request) {
                    return $query->where('term_id', $request->input('term_id'))
                        ->where('grade_level', $request->input('grade_level'));
                }),
            ],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated) {
                return Classroom::create([
                    'term_id' => $validated['term_id'],
                    'grade_level' => $validated['grade_level'],
                    'section' => $validated['section'],
                    'roster_version' => 1,
                ]);
            },
            auditFactory: fn (Classroom $classroom) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_CLASSROOM',
                'entity_type' => Classroom::class,
                'entity_id' => $classroom->id,
                'scope' => AuditScope::CLASSROOM,
                'changes' => $validated,
                'reason' => "Rombel {$classroom->full_name} dibuat",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Rombongan belajar (kelas) berhasil ditambahkan.');
    }
}
