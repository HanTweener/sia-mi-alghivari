<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PasswordLifecycleService;
use Fouladgar\OTP\Exceptions\OTPException;
use Fouladgar\OTP\Facades\OTP;
use Fouladgar\OTP\Notifications\OTPNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use LogicException;

class PasswordResetController extends Controller
{
    public function __construct(
        protected PasswordLifecycleService $passwordService
    ) {}

    /**
     * Display the email request view.
     */
    public function createRequest(): Response
    {
        return Inertia::render('Auth/ForgotPassword', [
            'status' => session('status'),
        ]);
    }

    /**
     * Send 5-minute OTP code to user's registered email (D-25).
     */
    public function sendOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $email = (string) $request->input('email');
        $key = 'password-otp-account:'.hash('sha256', strtolower($email));
        $sourceKey = 'password-otp-source:'.hash('sha256', $request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, 3) || RateLimiter::tooManyAttempts($sourceKey, 10), 429);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($sourceKey, 60);
        $request->session()->forget(['password_reset_proof', 'password_reset_proof_email']);

        try {
            $challenge = DB::transaction(function () use ($email) {
                $user = User::where('email', $email)->where('is_active', true)->lockForUpdate()->first();
                if (! $user) {
                    return null;
                }

                $user->update([
                    'password_reset_proof_hash' => null,
                    'password_reset_proof_expires_at' => null,
                    'password_reset_proof_version' => null,
                ]);
                $broker = OTP::channel('mail')->purpose('password_reset')->withNotify(false);
                $broker->revoke($user->email);
                $broker->send($user->email);

                return ['email' => $user->email, 'token' => $broker->getToken()];
            });

            if ($challenge) {
                Notification::route('mail', $challenge['email'])->route('otp', $challenge['email'])
                    ->notify(new OTPNotification($challenge['email'], $challenge['token'], ['mail']));
            }
        } catch (\Throwable) {
            Log::warning('OTP dispatch failed.');
        }

        $request->session()->put('password_reset_email', $email);

        return redirect()->route('password.verify.show')->with(
            'status',
            'Jika email Anda terdaftar di sistem, kode verifikasi OTP 6 digit telah dikirimkan ke kotak masuk email Anda.'
        );
    }

    /**
     * Display OTP verification view.
     */
    public function createVerify(Request $request): Response|RedirectResponse
    {
        $email = $request->session()->get('password_reset_email') ?? $request->query('email');

        if (! $email) {
            return redirect()->route('password.request');
        }

        return Inertia::render('Auth/VerifyOtp', [
            'email' => (string) $email,
            'status' => session('status'),
        ]);
    }

    /**
     * Validate OTP and issue ephemeral proof token (D-25).
     */
    public function verifyOtp(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'string', 'email'],
            'otp' => ['required', 'string', 'min:4', 'max:10'],
        ]);

        $email = (string) $request->input('email');
        $otp = (string) $request->input('otp');
        $key = 'password-otp-verify-account:'.hash('sha256', strtolower($email));
        $sourceKey = 'password-otp-verify-source:'.hash('sha256', $request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($sourceKey, 20), 429);
        RateLimiter::hit($key, 300);
        RateLimiter::hit($sourceKey, 300);
        try {
            $proofToken = DB::transaction(function () use ($email, $otp) {
                $user = User::where('email', $email)->where('is_active', true)->lockForUpdate()->first();
                if (! $user || ! OTP::purpose('password_reset')->validate($user->email, $otp)) {
                    throw new LogicException('Invalid OTP context.');
                }

                return $this->passwordService->generateResetProof($user);
            });
        } catch (OTPException|LogicException) {
            throw ValidationException::withMessages([
                'otp' => __('Kode verifikasi OTP tidak valid atau telah kedaluwarsa. Silakan periksa kembali atau minta kode baru.'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $request->session()->put([
            'password_reset_proof' => $proofToken,
            'password_reset_proof_email' => $email,
        ]);
        $request->session()->forget('password_reset_email');

        return redirect()->route('password.reset.show');
    }

    /**
     * Display the reset password view with proof token.
     */
    public function createReset(Request $request): Response|RedirectResponse
    {
        $token = (string) $request->session()->get('password_reset_proof');
        $email = (string) $request->session()->get('password_reset_proof_email');

        if (! $token || ! $email) {
            return redirect()->route('password.request');
        }

        $user = User::where('email', $email)->where('is_active', true)->first();

        if (
            ! $user ||
            empty($user->password_reset_proof_hash) ||
            empty($user->password_reset_proof_expires_at) ||
            now()->gt($user->password_reset_proof_expires_at)
        ) {
            return redirect()->route('password.request')->withErrors([
                'email' => 'Sesi perubahan kata sandi telah kedaluwarsa atau tidak valid. Silakan ajukan ulang.',
            ]);
        }

        return Inertia::render('Auth/ResetPassword', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    /**
     * Process new password update using proof token.
     */
    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        abort_unless($request->session()->get('password_reset_proof_email') === $request->input('email')
            && is_string($request->session()->get('password_reset_proof'))
            && hash_equals($request->session()->get('password_reset_proof'), $request->input('token')), 403);

        $user = User::where('email', $request->input('email'))->where('is_active', true)->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => __('Pengguna tidak ditemukan atau telah dinonaktifkan.'),
            ]);
        }

        try {
            $result = $this->passwordService->completePasswordReset(
                $user,
                (string) $request->input('token'),
                (string) $request->input('password')
            );
        } catch (LogicException) {
            throw ValidationException::withMessages([
                'token' => __('Token otorisasi pemulihan tidak valid atau telah kedaluwarsa.'),
            ]);
        }

        $request->session()->regenerate();
        $request->session()->forget(['password_reset_proof', 'password_reset_proof_email']);
        $request->session()->put('pending_reset_user_id', $user->id);
        $request->session()->put('pending_choice_id', $result['pending_choice_id']);
        $request->session()->put('pending_choice_version', $result['user']->credential_version);

        return redirect()->route('password.reset.session-choice.show');
    }

    /**
     * Show post-reset session termination choice view (D-25).
     */
    public function createSessionChoice(Request $request): Response|RedirectResponse
    {
        $userId = $request->session()->get('pending_reset_user_id');
        $choiceId = $request->session()->get('pending_choice_id');

        if (! $userId || ! $choiceId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->is_active || $user->pending_session_choice_id !== $choiceId
            || $user->pending_session_choice_flow !== 'password_reset'
            || $user->credential_version !== $request->session()->get('pending_choice_version')) {
            return redirect()->route('login');
        }

        return Inertia::render('Auth/ResetSessionChoice', [
            'email' => $user->email,
        ]);
    }

    /**
     * Store session choice and redirect to normal login (D-25).
     */
    public function storeSessionChoice(Request $request): RedirectResponse
    {
        $request->validate([
            'choice' => ['required', 'string', 'in:revoke_others,keep_others'],
        ]);

        $userId = $request->session()->get('pending_reset_user_id');
        $choiceId = $request->session()->get('pending_choice_id');

        if (! $userId || ! $choiceId) {
            return redirect()->route('login');
        }

        $user = User::find($userId);

        if (! $user || ! $user->is_active || $user->pending_session_choice_id !== $choiceId
            || $user->pending_session_choice_flow !== 'password_reset'
            || $user->credential_version !== $request->session()->get('pending_choice_version')) {
            return redirect()->route('login');
        }

        try {
            $this->passwordService->handleSessionChoice(
                $user, (string) $request->input('choice'), $choiceId, 'password_reset',
                $request->session()->get('pending_choice_version')
            );
        } catch (LogicException) {
            abort(403);
        }

        $request->session()->forget(['pending_reset_user_id', 'pending_choice_id', 'pending_choice_version']);

        return redirect()->route('login')->with(
            'status',
            'Kata sandi Anda berhasil diperbarui. Silakan masuk menggunakan kata sandi baru Anda.'
        );
    }
}
