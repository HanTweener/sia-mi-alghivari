<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Display subjects catalog.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Subject::class);

        $query = Subject::query()->orderBy('code');

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%");
            });
        }

        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        $subjects = $query->paginate(20)->through(fn (Subject $s) => [
            'id' => $s->id,
            'code' => $s->code,
            'name' => $s->name,
            'category' => $s->category,
            'is_active' => $s->is_active,
        ]);

        return Inertia::render('Admin/Academic/Subjects/Index', [
            'subjects' => $subjects,
            'filters' => $request->only(['search', 'category']),
        ]);
    }

    /**
     * Store a new subject.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Subject::class);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:subjects,code'],
            'name' => ['required', 'string', 'max:128'],
            'category' => ['required', 'string', 'in:umum,agama,muatan_lokal'],
            'is_active' => ['boolean'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($validated) {
                return Subject::create($validated);
            },
            auditFactory: fn (Subject $subject) => [
                'actor_id' => $actor?->id,
                'action' => 'CREATE_SUBJECT',
                'entity_type' => Subject::class,
                'entity_id' => $subject->id,
                'scope' => AuditScope::SUBJECT,
                'changes' => $validated,
                'reason' => "Mata pelajaran {$subject->name} ({$subject->code}) ditambahkan",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Mata pelajaran berhasil ditambahkan.');
    }

    /**
     * Update an existing subject.
     */
    public function update(Request $request, Subject $subject): RedirectResponse
    {
        $this->authorize('update', $subject);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:128'],
            'category' => ['required', 'string', 'in:umum,agama,muatan_lokal'],
            'is_active' => ['required', 'boolean'],
        ]);

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($subject, $validated) {
                $subject->update($validated);

                return $subject;
            },
            auditFactory: fn (Subject $updated) => [
                'actor_id' => $actor?->id,
                'action' => 'UPDATE_SUBJECT',
                'entity_type' => Subject::class,
                'entity_id' => $updated->id,
                'scope' => AuditScope::SUBJECT,
                'changes' => $validated,
                'reason' => "Mata pelajaran {$updated->code} diperbarui",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', 'Mata pelajaran berhasil diperbarui.');
    }
}
