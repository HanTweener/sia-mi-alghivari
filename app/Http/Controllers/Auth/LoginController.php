<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class LoginController extends Controller
{
    /**
     * Show the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'status' => session('status'),
            'demoAccounts' => [
                [
                    'role' => 'Master Admin',
                    'username' => 'admin',
                    'name' => 'Administrator Utama',
                    'desc' => 'Kelola seluruh pengguna, hak akses, audit log, dan pengaturan global.',
                ],
                [
                    'role' => 'Admin Akademik',
                    'username' => 'akademik',
                    'name' => 'Staf Tata Usaha',
                    'desc' => 'Draf kenaikan/kelulusan, mutasi enrollment, dan rombel.',
                ],
                [
                    'role' => 'Kepala Madrasah',
                    'username' => 'kepala.madrasah',
                    'name' => 'Drs. H. Miftah Faridl, M.Ag.',
                    'desc' => 'Persetujuan & penolakan eksklusif kenaikan/kelulusan siswa.',
                ],
                [
                    'role' => 'Guru & Wali Kelas',
                    'username' => 'guru.ahmad',
                    'name' => 'Ustadz Ahmad Fauzi, S.Pd.I.',
                    'desc' => 'Absensi, entri penilaian mapel, dan publikasi rapor resmi binaan.',
                ],
                [
                    'role' => 'Guru Mengajar (Mapel)',
                    'username' => 'guru.ridwan',
                    'name' => 'Ustadz Ridwan Nugraha, S.Pd.',
                    'desc' => 'Entri asesmen formatif/sumatif dan presensi mapel tanpa tugas wali kelas.',
                ],
                [
                    'role' => 'Wali Murid',
                    'username' => 'wali.hasan',
                    'name' => 'Hasan Basri, S.T.',
                    'desc' => 'Portal pemantauan belajar anak & unduh rapor semester resmi.',
                ],
                [
                    'role' => 'Data Entry',
                    'username' => 'dataentry',
                    'name' => 'Operator Data Entri',
                    'desc' => 'Input data siswa & penautan wali murid.',
                ],
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $key = 'login-account:'.hash('sha256', strtolower($credentials['username']));
        $sourceKey = 'login-source:'.hash('sha256', $request->ip());
        abort_if(RateLimiter::tooManyAttempts($key, 5) || RateLimiter::tooManyAttempts($sourceKey, 20), 429);
        RateLimiter::hit($key, 60);
        RateLimiter::hit($sourceKey, 60);
        $field = filter_var($credentials['username'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        if (! Auth::attemptWhen(
            [$field => $credentials['username'], 'password' => $credentials['password']],
            fn (User $user) => $user->is_active
                && ! ($user->must_change_password && $user->temporary_password_expires_at?->isPast()),
        )) {
            throw ValidationException::withMessages([
                'username' => __('Kredensial tidak valid atau akun tidak dapat digunakan.'),
            ]);
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $user = $request->user();
        $request->session()->put('session_generation', $user->session_generation);
        $request->session()->forget(['temporary_credential_version', 'password_choice_id', 'password_choice_flow', 'password_choice_version']);

        if ($user->must_change_password) {
            $request->session()->put('temporary_credential_version', $user->credential_version);
        }

        if ($user->pending_session_choice_id) {
            $request->session()->put([
                'password_choice_id' => $user->pending_session_choice_id,
                'password_choice_flow' => $user->pending_session_choice_flow,
                'password_choice_version' => $user->credential_version,
            ]);
        }

        return redirect()->intended('/');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
