<?php

use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\AssessmentController;
use App\Http\Controllers\Academic\AttendanceSessionController;
use App\Http\Controllers\Academic\ClassroomController;
use App\Http\Controllers\Academic\ClassSubjectController;
use App\Http\Controllers\Academic\EnrollmentController;
use App\Http\Controllers\Academic\GradingPolicyController;
use App\Http\Controllers\Academic\HomeroomController;
use App\Http\Controllers\Academic\ProgressionController;
use App\Http\Controllers\Academic\ReportCardController;
use App\Http\Controllers\Academic\StudentController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\TeachingAssignmentController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\PasswordSetupController;
use App\Http\Controllers\Guardian\ChildProgressController;
use App\Http\Controllers\Guardian\ChildReportCardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\EnsurePasswordNotExpired;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'appName' => config('app.name'),
        'environment' => app()->environment(),
    ]);
});

// Authentication Routes (Guest Only)
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // D-25 Self-Service Password Recovery & Email OTP
    Route::get('/forgot-password', [PasswordResetController::class, 'createRequest'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendOtp'])->name('password.email');
    Route::get('/reset-password/verify', [PasswordResetController::class, 'createVerify'])->name('password.verify.show');
    Route::post('/reset-password/verify', [PasswordResetController::class, 'verifyOtp'])->name('password.verify.submit');
    Route::get('/reset-password', [PasswordResetController::class, 'createReset'])->name('password.reset.show');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.reset.update');
    Route::get('/reset-password/session-choice', [PasswordResetController::class, 'createSessionChoice'])->name('password.reset.session-choice.show');
    Route::post('/reset-password/session-choice', [PasswordResetController::class, 'storeSessionChoice'])->name('password.reset.session-choice.store');
});

// Logout Route (Auth Only)
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout')->middleware('auth');

Route::middleware(['auth'])->group(function () {
    // D-25 First-Login Password Change and Session Choice Routes
    Route::get('/auth/setup-password', [PasswordSetupController::class, 'create'])->name('password.setup');
    Route::post('/auth/setup-password', [PasswordSetupController::class, 'update'])->name('password.setup.update');
    Route::post('/auth/setup-password/session-choice', [PasswordSetupController::class, 'storeSessionChoice'])->name('password.setup.session-choice');

    // Protected App Area (requires password setup completed)
    Route::middleware([EnsurePasswordNotExpired::class])->group(function () {
        // Persistent Inbox & Notifications (F-11, DP-13)
        Route::get('/inbox', [NotificationController::class, 'index'])->name('notifications.inbox');
        Route::get('/notifications', [NotificationController::class, 'apiIndex'])->name('notifications.api.index');
        Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount'])->name('notifications.unread-count');
        Route::patch('/notifications/{notification}/read-state', [NotificationController::class, 'updateReadState'])->name('notifications.read-state');
        Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.mark-all-read');

        // Self-Profile Management (FR-012/F-01)
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');

        // Guardian Learning Progress Portal (F-07, DP-26, HTTP-19)
        Route::get('/children', [ChildProgressController::class, 'index'])->name('children.index');
        Route::get('/children/{student}/progress', [ChildProgressController::class, 'progress'])->name('children.progress');
        Route::get('/children/{student}/homeroom-contact', [ChildProgressController::class, 'homeroomContact'])->name('children.homeroom-contact');

        // Guardian Official Semester Report Cards Portal (DP-30.5, SCR-09, Q7)
        Route::get('/children/{student}/report-cards', [ChildReportCardController::class, 'index'])->name('children.report-cards.index');
        Route::get('/children/{student}/report-cards/{report}', [ChildReportCardController::class, 'show'])->name('children.report-cards.show');
        Route::get('/children/{student}/report-cards/{report}/pdf', [ChildReportCardController::class, 'pdf'])->name('children.report-cards.pdf');

        // Master / Academic Administration: Users & Roles (FR-001/F-01)
        Route::prefix('admin')->name('admin.')->group(function () {
            Route::get('/users', [UserController::class, 'index'])->name('users.index');
            Route::post('/users', [UserController::class, 'store'])->name('users.store');
            Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

            // Master Administration: System Settings (FR-002/F-02, D-29)
            Route::get('/settings', [SystemSettingController::class, 'index'])->name('settings.index');
            Route::put('/settings', [SystemSettingController::class, 'update'])->name('settings.update');
        });

        // System Audit Logs (F-10, HTTP-28, SCR-11)
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
        Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

        // Academic Master Data (F-03, DP-20)
        Route::prefix('academic')->name('academic.')->group(function () {
            // Academic Years & Terms
            Route::get('/years', [AcademicYearController::class, 'index'])->name('years.index');
            Route::post('/years', [AcademicYearController::class, 'storeYear'])->name('years.store');
            Route::post('/years/{academicYear}/terms', [AcademicYearController::class, 'storeTerm'])->name('terms.store');
            Route::patch('/terms/{term}/activate', [AcademicYearController::class, 'activateTerm'])->name('terms.activate');

            // Classrooms (Rombel)
            Route::get('/classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
            Route::post('/classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
            Route::get('/classrooms/{classroom}/report-preview', [ReportCardController::class, 'preview'])->name('classrooms.report-preview');

            // Subjects (Mata Pelajaran)
            Route::get('/subjects', [SubjectController::class, 'index'])->name('subjects.index');
            Route::post('/subjects', [SubjectController::class, 'store'])->name('subjects.store');
            Route::put('/subjects/{subject}', [SubjectController::class, 'update'])->name('subjects.update');

            // Students (Siswa & Relasi Wali Q6.1)
            Route::get('/students', [StudentController::class, 'index'])->name('students.index');
            Route::get('/students/export', [StudentController::class, 'export'])->name('students.export');
            Route::get('/students/template', [StudentController::class, 'template'])->name('students.template');
            Route::post('/students/import', [StudentController::class, 'import'])->name('students.import');
            Route::post('/students', [StudentController::class, 'store'])->name('students.store');
            Route::put('/students/{student}', [StudentController::class, 'update'])->name('students.update');
            Route::post('/students/{student}/guardians', [StudentController::class, 'linkGuardian'])->name('students.guardians.link');

            // Homeroom, Roster & Enrollments (DP-21)
            Route::post('/classrooms/{classroom}/homeroom', [HomeroomController::class, 'assign'])->name('classrooms.homeroom.assign');
            Route::get('/classrooms/{classroom}/roster', [EnrollmentController::class, 'index'])->name('classrooms.roster.index');
            Route::post('/classrooms/{classroom}/enrollments', [EnrollmentController::class, 'store'])->name('classrooms.enrollments.store');
            Route::delete('/enrollments/{enrollment}', [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');
            Route::post('/enrollments/{enrollment}/transfer', [EnrollmentController::class, 'transfer'])->name('enrollments.transfer');

            // Class Subjects, Teaching Assignments & Schedules (DP-21, D-26/D-27)
            Route::get('/teaching-assignments', [TeachingAssignmentController::class, 'index'])->name('teaching-assignments.index');
            Route::post('/teaching-assignments', [TeachingAssignmentController::class, 'store'])->name('teaching-assignments.store');
            Route::delete('/teaching-assignments/{classSubject}', [TeachingAssignmentController::class, 'destroy'])->name('teaching-assignments.destroy');
            Route::get('/classrooms/{classroom}/subjects', [ClassSubjectController::class, 'index'])->name('classrooms.subjects.index');
            Route::post('/classrooms/{classroom}/subjects', [ClassSubjectController::class, 'store'])->name('classrooms.subjects.store');
            Route::post('/class-subjects/{classSubject}/teachers', [ClassSubjectController::class, 'assignTeacher'])->name('class-subjects.teachers.assign');
            Route::post('/class-subjects/{classSubject}/schedules', [ClassSubjectController::class, 'storeScheduleSlot'])->name('class-subjects.schedules.store');
            Route::delete('/schedules/{slot}', [ClassSubjectController::class, 'destroyScheduleSlot'])->name('schedules.destroy');

            // Global Grading Policies & Scoring Components (DP-22, F-04, Q7.5)
            Route::get('/grading-policies', [GradingPolicyController::class, 'index'])->name('grading-policies.index');
            Route::post('/grading-policies', [GradingPolicyController::class, 'store'])->name('grading-policies.store');
            Route::put('/grading-policies/{policy}', [GradingPolicyController::class, 'update'])->name('grading-policies.update');
            Route::patch('/grading-policies/{policy}/activate', [GradingPolicyController::class, 'activate'])->name('grading-policies.activate');
            Route::post('/scoring-components', [GradingPolicyController::class, 'storeComponent'])->name('scoring-components.store');
            Route::patch('/scoring-components/{component}/toggle', [GradingPolicyController::class, 'toggleComponent'])->name('scoring-components.toggle');

            // Attendance Sessions & Records (DP-24, F-05, Q6.3)
            Route::get('/class-subjects/{classSubject}/attendance-sessions', [AttendanceSessionController::class, 'index'])->name('class-subjects.attendance.index');
            Route::post('/class-subjects/{classSubject}/attendance-sessions', [AttendanceSessionController::class, 'store'])->name('class-subjects.attendance.store');
            Route::get('/attendance-sessions/{session}', [AttendanceSessionController::class, 'show'])->name('attendance-sessions.show');
            Route::patch('/attendance-sessions/{session}', [AttendanceSessionController::class, 'update'])->name('attendance-sessions.update');
            Route::delete('/attendance-sessions/{session}', [AttendanceSessionController::class, 'destroy'])->name('attendance-sessions.destroy');
            Route::patch('/attendance-sessions/{session}/records', [AttendanceSessionController::class, 'updateRecords'])->name('attendance-sessions.records.update');
            Route::post('/attendance-sessions/{session}/finalize', [AttendanceSessionController::class, 'finalize'])->name('attendance-sessions.finalize');
            Route::post('/class-subjects/{classSubject}/delegations', [AttendanceSessionController::class, 'storeDelegation'])->name('class-subjects.delegations.store');
            Route::post('/delegations/{delegation}/revoke', [AttendanceSessionController::class, 'revokeDelegation'])->name('delegations.revoke');

            // Subject Assessments & Grading (DP-25, F-06, Q6.3)
            Route::get('/class-subjects/{classSubject}/assessments', [AssessmentController::class, 'index'])->name('class-subjects.assessments.index');
            Route::post('/class-subjects/{classSubject}/assessments', [AssessmentController::class, 'store'])->name('class-subjects.assessments.store');
            Route::get('/assessments/{assessment}', [AssessmentController::class, 'show'])->name('assessments.show');
            Route::patch('/assessments/{assessment}', [AssessmentController::class, 'update'])->name('assessments.update');
            Route::delete('/assessments/{assessment}', [AssessmentController::class, 'destroy'])->name('assessments.destroy');
            Route::get('/assessments/{assessment}/template', [AssessmentController::class, 'exportTemplate'])->name('assessments.template');
            Route::post('/assessments/{assessment}/import', [AssessmentController::class, 'importScores'])->name('assessments.import');
            Route::patch('/assessments/{assessment}/scores', [AssessmentController::class, 'updateScores'])->name('assessments.scores.update');
            Route::post('/assessments/{assessment}/publish', [AssessmentController::class, 'publish'])->name('assessments.publish');

            // Semester Report Cards (DP-30, F-08, Q7, SCR-08)
            Route::post('/classrooms/{classroom}/report-cards', [ReportCardController::class, 'storeDrafts'])->name('classrooms.report-cards.store');
            Route::get('/report-cards/{report}', [ReportCardController::class, 'show'])->name('report-cards.show');
            Route::patch('/report-cards/{report}', [ReportCardController::class, 'update'])->name('report-cards.update');
            Route::post('/report-cards/{report}/finalize', [ReportCardController::class, 'finalize'])->name('report-cards.finalize');
            Route::post('/report-cards/{report}/publish', [ReportCardController::class, 'publish'])->name('report-cards.publish');
            Route::post('/report-cards/{report}/revisions', [ReportCardController::class, 'createRevision'])->name('report-cards.revisions.store');
            Route::post('/report-cards/{report}/revoke', [ReportCardController::class, 'revoke'])->name('report-cards.revoke');
            Route::post('/report-cards/{report}/prepare-file', [ReportCardController::class, 'prepareFile'])->name('report-cards.prepare-file');
            Route::get('/report-cards/{report}/pdf', [ReportCardController::class, 'pdf'])->name('report-cards.pdf');
            Route::post('/report-cards/{report}/extracurricular', [ReportCardController::class, 'storeExtracurricular'])->name('report-cards.extracurricular.store');
            Route::delete('/report-cards/extracurricular/{extracurricular}', [ReportCardController::class, 'destroyExtracurricular'])->name('report-cards.extracurricular.destroy');
            Route::post('/report-cards/{report}/p5', [ReportCardController::class, 'storeP5'])->name('report-cards.p5.store');
            Route::delete('/report-cards/p5/{p5}', [ReportCardController::class, 'destroyP5'])->name('report-cards.p5.destroy');

            // Student Progression & Graduation (DP-40, DP-41, F-09, HTTP-24)
            Route::get('/progressions', [ProgressionController::class, 'index'])->name('progressions.index');
            Route::post('/progressions', [ProgressionController::class, 'store'])->name('progressions.store');
            Route::get('/progressions/{progression}', [ProgressionController::class, 'show'])->name('progressions.show');
            Route::patch('/progressions/{progression}', [ProgressionController::class, 'update'])->name('progressions.update');
            Route::delete('/progressions/{progression}', [ProgressionController::class, 'destroy'])->name('progressions.destroy');
            Route::post('/progressions/{progression}/submit', [ProgressionController::class, 'submit'])->name('progressions.submit');
            Route::post('/progressions/{progression}/approve', [ProgressionController::class, 'approve'])->name('progressions.approve');
            Route::post('/progressions/{progression}/reject', [ProgressionController::class, 'reject'])->name('progressions.reject');
            Route::post('/progressions/batch-approve', [ProgressionController::class, 'batchApprove'])->name('progressions.batch-approve');
            Route::post('/progressions/batch-reject', [ProgressionController::class, 'batchReject'])->name('progressions.batch-reject');
            Route::post('/progressions/{progression}/apply', [ProgressionController::class, 'apply'])->name('progressions.apply');
            Route::post('/progressions/batch-apply', [ProgressionController::class, 'batchApply'])->name('progressions.batch-apply');
        });
    });
});
