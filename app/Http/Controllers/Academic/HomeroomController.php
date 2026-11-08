<?php

namespace App\Http\Controllers\Academic;

use App\Enums\AuditScope;
use App\Enums\AuditVisibility;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Classroom;
use App\Models\HomeroomAssignment;
use App\Models\User;
use App\Services\Audit\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeroomController extends Controller
{
    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Assign teacher as homeroom teacher to a classroom (Max 1 active per room).
     */
    public function assign(Request $request, Classroom $classroom): RedirectResponse
    {
        $this->authorize('manageHomeroom', $classroom);

        $validated = $request->validate([
            'teacher_id' => ['required', 'exists:users,id'],
        ]);

        $teacher = User::findOrFail($validated['teacher_id']);
        if (! $teacher->hasRole(Role::TEACHER)) {
            return back()->withErrors(['teacher_id' => 'Pengguna yang dipilih tidak memiliki peran Guru.']);
        }

        $actor = $request->user();

        $this->auditService->executeAtomic(
            operation: function () use ($classroom, $teacher, $actor) {
                // Inactivate any currently active homeroom assignment for this room
                $classroom->homeroomAssignments()
                    ->where('status', 'active')
                    ->update(['status' => 'inactive']);

                // Create new active homeroom assignment
                return $classroom->homeroomAssignments()->create([
                    'teacher_id' => $teacher->id,
                    'status' => 'active',
                    'assigned_by' => $actor?->id,
                ]);
            },
            auditFactory: fn (HomeroomAssignment $assignment) => [
                'actor_id' => $actor?->id,
                'action' => 'ASSIGN_HOMEROOM',
                'entity_type' => HomeroomAssignment::class,
                'entity_id' => $assignment->id,
                'scope' => AuditScope::ASSIGNMENT,
                'changes' => [
                    'classroom_id' => $classroom->id,
                    'teacher_id' => $teacher->id,
                    'status' => 'active',
                ],
                'reason' => "Penugasan wali kelas {$classroom->full_name} kepada {$teacher->name}",
                'visibility' => AuditVisibility::INTERNAL,
            ]
        );

        return back()->with('success', "Wali kelas {$classroom->full_name} berhasil ditugaskan.");
    }
}
