<?php

namespace App\Http\Controllers;

use App\Enums\AuditScope;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuditLogController extends Controller
{
    /**
     * Display a listing of the audit logs with filters.
     */
    public function index(Request $request): InertiaResponse|JsonResponse
    {
        $this->authorize('viewAny', AuditLog::class);

        $query = AuditLog::visibleTo($request->user())->with('actor:id,name,username');

        // Filter by scope
        if ($request->filled('scope')) {
            $query->where('scope', $request->string('scope')->value());
        }

        // Filter by action
        if ($request->filled('action')) {
            $query->where('action', 'like', '%'.$request->string('action')->value().'%');
        }

        // Filter by actor
        if ($request->filled('actor_user_id')) {
            $query->where('actor_user_id', $request->integer('actor_user_id'));
        }

        // Filter by entity type
        if ($request->filled('entity_type')) {
            $query->where('entity_type', 'like', '%'.$request->string('entity_type')->value().'%');
        }

        // Filter by date range (starts_on / ends_on)
        if ($request->filled('starts_on')) {
            $startsOn = Carbon::parse($request->string('starts_on')->value())->startOfDay();
            $query->where('created_at', '>=', $startsOn);
        }

        if ($request->filled('ends_on')) {
            $endsOn = Carbon::parse($request->string('ends_on')->value())->endOfDay();
            $query->where('created_at', '<=', $endsOn);
        }

        $logs = $query->latest('id')->paginate(25)->withQueryString();

        // Redact any sensitive information in allowed_changes
        $logs->getCollection()->transform(function (AuditLog $log) {
            $log->allowed_changes = $this->redactSensitiveData($log->allowed_changes);

            return $log;
        });

        if ($request->wantsJson()) {
            return response()->json($logs);
        }

        $actors = User::whereHas('auditLogs', fn ($logs) => $logs->visibleTo($request->user()))
            ->orderBy('name')->get(['id', 'name', 'username']);

        $scopes = array_map(fn (AuditScope $s) => [
            'value' => $s->value,
            'label' => ucfirst($s->value),
        ], array_values(array_filter(AuditScope::cases(), fn (AuditScope $scope) => $request->user()->isMasterAdmin() || ! in_array($scope, [AuditScope::USER, AuditScope::SYSTEM], true))));

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['scope', 'action', 'actor_user_id', 'entity_type', 'starts_on', 'ends_on']),
            'actors' => $actors,
            'scopes' => $scopes,
        ]);
    }

    /**
     * Display details of a specific audit log.
     */
    public function show(Request $request, AuditLog $auditLog): InertiaResponse|JsonResponse
    {
        $this->authorize('view', $auditLog);

        $auditLog->load('actor:id,name,username,email');
        $auditLog->allowed_changes = $this->redactSensitiveData($auditLog->allowed_changes);

        // Fetch related batch logs if batch_id exists
        $batchLogs = [];
        if ($auditLog->batch_id) {
            $batchLogs = AuditLog::visibleTo($request->user())->with('actor:id,name')
                ->where('batch_id', $auditLog->batch_id)
                ->where('id', '!=', $auditLog->id)
                ->oldest('id')
                ->get()
                ->map(function ($log) {
                    $log->allowed_changes = $this->redactSensitiveData($log->allowed_changes);

                    return $log;
                });
        }

        if ($request->wantsJson()) {
            return response()->json([
                'data' => $auditLog,
                'batch_logs' => $batchLogs,
            ]);
        }

        return Inertia::render('Admin/AuditLogs/Show', [
            'auditLog' => $auditLog,
            'batchLogs' => $batchLogs,
        ]);
    }

    /**
     * Redact sensitive information (passwords, tokens, secrets) recursively.
     */
    protected function redactSensitiveData(mixed $data): mixed
    {
        if (! is_array($data)) {
            return $data;
        }

        $sensitivePatterns = ['password', 'token', 'secret', 'hash', 'otp', 'remember_token'];

        $redacted = [];
        foreach ($data as $key => $value) {
            $isSensitive = false;
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                foreach ($sensitivePatterns as $pattern) {
                    if (str_contains($lowerKey, $pattern)) {
                        $isSensitive = true;
                        break;
                    }
                }
            }

            if ($isSensitive) {
                $redacted[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $redacted[$key] = $this->redactSensitiveData($value);
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }
}
