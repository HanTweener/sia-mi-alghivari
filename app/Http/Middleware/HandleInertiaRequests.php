<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\Term;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $roles = $user ? $user->userRoles->pluck('role')->all() : [];
        $isTeacher = in_array(Role::TEACHER->value, $roles, true);

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'roles' => $roles,
                    'is_homeroom' => $isTeacher ? $user->homeroomAssignments()->where('status', 'active')->exists() : false,
                ] : null,
            ],
            'academicContext' => [
                'activeTerm' => fn () => rescue(
                    fn () => Cache::remember('active_academic_term', 3600, fn () => Term::where('status', 'active')
                        ->with('academicYear')
                        ->first()),
                    fn () => Term::where('status', 'active')->with('academicYear')->first(),
                    report: false
                ),
            ],
            'notifications' => [
                'unreadCount' => $user ? fn () => $user->notifications()->visibleTo($user)->whereNull('read_at')->count() : 0,
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }
}
