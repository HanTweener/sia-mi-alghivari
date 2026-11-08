<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationController extends Controller
{
    /**
     * Display full inbox view (Inertia) or paginated list.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $query = $user->notifications()->visibleTo($user)->latest();

        if ($request->boolean('unread_only')) {
            $query->whereNull('read_at');
        }

        if ($category = $request->input('category')) {
            $query->where('event_category', $category);
        }

        $notifications = $query->paginate(15)->through(fn (Notification $n) => [
            'id' => $n->id,
            'event_category' => $n->event_category,
            'scope_type' => $n->scope_type,
            'scope_id' => $n->scope_id,
            'sanitized_summary' => $n->sanitized_summary,
            'visible_item_count' => $n->visible_item_count,
            'read_at' => $n->read_at?->toIso8601String(),
            'created_at' => $n->created_at?->toIso8601String(),
        ]);

        $unreadCount = $user->notifications()->visibleTo($user)->whereNull('read_at')->count();

        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'filters' => $request->only(['unread_only', 'category']),
        ]);
    }

    /**
     * API endpoint for recent notifications (used by NotificationBell dropdown).
     */
    public function apiIndex(Request $request): JsonResponse
    {
        $user = $request->user();
        $notifications = $user->notifications()
            ->visibleTo($user)
            ->latest()
            ->take(10)
            ->select(['id', 'recipient_id', 'event_category', 'scope_type', 'sanitized_summary', 'read_at', 'created_at'])
            ->get()
            ->map(fn (Notification $n) => [
                'id' => $n->id,
                'event_category' => $n->event_category,
                'scope_type' => $n->scope_type,
                'sanitized_summary' => $n->sanitized_summary,
                'read_at' => $n->read_at?->toIso8601String(),
                'created_at' => $n->created_at?->toIso8601String(),
            ]);

        $unreadCount = $user->notifications()->visibleTo($user)->whereNull('read_at')->count();

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    /**
     * API endpoint for unread notifications count badge.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->visibleTo($request->user())->whereNull('read_at')->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Mark a specific notification as read.
     */
    public function updateReadState(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        if (! Notification::visibleTo($request->user())->whereKey($notification->id)->exists()) {
            abort(403, 'Unauthorized access to notification.');
        }

        $notification->markAsRead();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'read_at' => $notification->read_at?->toIso8601String(),
            ]);
        }

        return back()->with('success', 'Notifikasi telah ditandai dibaca.');
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->notifications()->visibleTo($request->user())->whereNull('read_at')->update([
            'read_at' => now(),
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Semua notifikasi telah ditandai dibaca.');
    }
}
