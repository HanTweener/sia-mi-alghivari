<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Auth\PasswordLifecycleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class PasswordSetupController extends Controller
{
    public function __construct(
        protected PasswordLifecycleService $passwordService
    ) {}

    /**
     * Show first-login forced password change view.
     */
    public function create(Request $request): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user->must_change_password && ! $user->pending_session_choice_id) {
            return redirect('/');
        }

        if ($user->pending_session_choice_id) {
            abort_unless($request->session()->get('password_choice_id') === $user->pending_session_choice_id
                && $request->session()->get('password_choice_version') === $user->credential_version, 403);
        }

        return Inertia::render('Auth/SetupPassword', [
            'mustChangePassword' => $user->must_change_password,
            'pendingSessionChoice' => $user->pending_session_choice_id !== null,
        ]);
    }

    /**
     * Process initial forced password change.
     */
    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        abort_unless($request->user()->must_change_password, 403);

        try {
            $result = $this->passwordService->completeFirstLoginPasswordChange(
                $request->user(),
                $request->input('password')
            );
        } catch (LogicException) {
            abort(403);
        }

        $request->session()->regenerate();
        $request->session()->forget('temporary_credential_version');
        $request->session()->put([
            'password_choice_id' => $result['pending_choice_id'],
            'password_choice_flow' => 'first_setup',
            'password_choice_version' => $result['user']->credential_version,
        ]);

        return redirect()->route('password.setup')->with('success', 'Kata sandi berhasil diperbarui.');
    }

    /**
     * Process post-commit session choice modal (D-25).
     */
    public function storeSessionChoice(Request $request): RedirectResponse
    {
        $request->validate([
            'choice' => ['required', 'string', 'in:revoke_others,keep_others'],
        ]);

        $flow = $request->session()->get('password_choice_flow');
        abort_unless(is_string($request->session()->get('password_choice_id')) && is_string($flow)
            && is_int($request->session()->get('password_choice_version')), 403);

        try {
            $user = $this->passwordService->handleSessionChoice(
                $request->user(),
                $request->input('choice'),
                $request->session()->get('password_choice_id'),
                $flow,
                $request->session()->get('password_choice_version'),
                $flow === 'password_reset' ? null : $request->session()->getId()
            );
        } catch (LogicException) {
            abort(403);
        }

        $request->session()->forget(['password_choice_id', 'password_choice_flow', 'password_choice_version']);
        $request->session()->put('session_generation', $user->session_generation);

        if ($flow === 'password_reset') {
            return app(LoginController::class)->destroy($request);
        }

        return redirect('/')->with('success', 'Sesi berhasil dikonfirmasi. Selamat datang di sistem!');
    }
}
