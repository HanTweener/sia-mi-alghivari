<?php

namespace App\Models;

use App\Enums\ReportCardAvailability;
use App\Enums\ReportCardStatus;
use App\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'recipient_id',
        'source_audit_id',
        'batch_id',
        'event_category',
        'scope_type',
        'scope_id',
        'sanitized_summary',
        'visible_item_count',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'visible_item_count' => 'integer',
            'read_at' => 'datetime',
        ];
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function sourceAudit(): BelongsTo
    {
        return $this->belongsTo(AuditLog::class, 'source_audit_id');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        $roles = $user->userRoles()->pluck('role');
        $oversight = $roles->intersect([Role::MASTER_ADMIN->value, Role::ACADEMIC_ADMIN->value, Role::PRINCIPAL->value])->isNotEmpty();
        $teacher = $roles->contains(Role::TEACHER->value);
        $guardian = $roles->contains(Role::GUARDIAN->value);
        $homeroom = fn (Builder $assignment) => $assignment->where('teacher_id', $user->id)->where('status', 'active');

        $reports = ReportCard::select('id')->where(function (Builder $report) use ($user, $oversight, $teacher, $guardian, $homeroom) {
            $report->whereRaw('1 = 0');
            if ($oversight) {
                $report->orWhereRaw('1 = 1');
            }
            if ($teacher) {
                $report->orWhereHas('enrollment.classroom.homeroomAssignments', $homeroom);
            }
            if ($guardian) {
                $report->orWhere(fn (Builder $ownReport) => $ownReport
                    ->where('status', ReportCardStatus::PUBLISHED)
                    ->where('availability', ReportCardAvailability::CURRENT)
                    ->whereHas('enrollment.student.guardians', fn (Builder $parent) => $parent->where('users.id', $user->id)));
            }
        });

        $progressions = StudentProgression::select('id')->where(function (Builder $progression) use ($oversight, $teacher, $homeroom) {
            $progression->whereRaw('1 = 0');
            if ($oversight) {
                $progression->orWhereRaw('1 = 1');
            }
            if ($teacher) {
                $progression->orWhereHas('sourceReportCard.enrollment.classroom.homeroomAssignments', $homeroom);
            }
        });

        return $query->where('recipient_id', $user->id)
            ->whereHas('recipient', fn (Builder $recipient) => $recipient->where('is_active', true))
            ->where(function (Builder $visible) use ($user, $reports, $progressions) {
                $visible->where(fn (Builder $general) => $general->whereNull('scope_type')->whereNull('scope_id'))
                    ->orWhere(fn (Builder $account) => $account->whereIn('scope_type', ['USER', 'user', 'account', 'general'])
                        ->where(fn (Builder $own) => $own->whereNull('scope_id')->orWhere('scope_id', (string) $user->id)))
                    ->orWhere(fn (Builder $report) => $report->whereIn('scope_type', ['report', 'report_card'])->whereIn('scope_id', $reports))
                    ->orWhere(fn (Builder $progression) => $progression->where('scope_type', 'progression')->whereIn('scope_id', $progressions));
            });
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(): bool
    {
        if ($this->read_at === null) {
            $this->read_at = now();

            return $this->save();
        }

        return true;
    }
}
