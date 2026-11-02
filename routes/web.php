<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\PortalController;
use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::post('/language/{locale}', function (Request $request, string $locale) {
    abort_unless(in_array($locale, ['en', 'my'], true), 404);
    $request->session()->put('locale', $locale);

    return back()->withCookie(cookie('locale', $locale, 60 * 24 * 365));
})->name('language');
Route::get('/', [PortalController::class, 'home'])->name('home');
Route::get('/announcements', [PortalController::class, 'announcements'])->name('announcements');
Route::get('/grades', [PortalController::class, 'grades'])->name('grades');
Route::get('/grades/{grade}', [PortalController::class, 'grade'])->name('grade');
Route::get('/students/{student}', [PortalController::class, 'student'])->whereNumber('student')->name('student');
Route::get('/teachers', [PortalController::class, 'teachers'])->name('teachers');
Route::get('/top-achievers', [PortalController::class, 'achievers'])->name('achievers');
Route::get('/overview', [PortalController::class, 'overview'])->name('overview');
Route::get('/media/{path}', function (string $path) {
    abort_unless(Storage::disk('public')->exists($path), 404);

    return Storage::disk('public')->response($path);
})->where('path', '.*')->name('media');
Route::get('/portraits/{type}/{id}', function (string $type, int $id) {
    $model = match ($type) {
        'students' => Student::findOrFail($id),
        'teachers' => Teacher::findOrFail($id),
        default => abort(404),
    };

    abort_unless($model->photo_data && $model->photo_mime, 404);

    return response(base64_decode($model->photo_data), 200, ['Content-Type' => $model->photo_mime, 'Cache-Control' => 'public, max-age=86400']);
})->whereNumber('id')->name('portrait');
Route::get('/robots.txt', fn () => response("User-agent: *\nDisallow: /".config('school.admin_path')."/\n", 200, ['Content-Type' => 'text/plain']));
Route::prefix(config('school.admin_path'))->middleware('noindex')->group(function () {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:10,1')->name('login.store');
    Route::middleware(['auth', 'staff'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
        Route::get('/', [AdminController::class, 'overview'])->name('admin.overview');
        Route::get('/attendance', [AdminController::class, 'attendance'])->name('admin.attendance');
        Route::post('/attendance', [AdminController::class, 'saveAttendance'])->name('admin.attendance.save');
        Route::get('/marks', [AdminController::class, 'marks'])->name('admin.marks');
        Route::post('/marks', [AdminController::class, 'saveMarks'])->name('admin.marks.save');
        Route::get('/fees', [AdminController::class, 'fees'])->name('admin.fees');
        Route::put('/fees/{student}', [AdminController::class, 'saveFee'])->name('admin.fees.save');
        Route::post('/head-teachers', [AdminController::class, 'assignHead'])->name('admin.head');
        Route::get('/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::put('/settings', [AdminController::class, 'saveSettings'])->name('admin.settings.save');
        Route::get('/{resource}', [AdminController::class, 'records'])->whereIn('resource', ['students', 'teachers', 'announcements'])->name('admin.records');
        Route::get('/{resource}/create', [AdminController::class, 'edit'])->whereIn('resource', ['students', 'teachers', 'announcements'])->name('admin.create');
        Route::get('/{resource}/{id}/edit', [AdminController::class, 'edit'])->whereIn('resource', ['students', 'teachers', 'announcements'])->whereNumber('id')->name('admin.edit');
        Route::post('/{resource}', [AdminController::class, 'save'])->whereIn('resource', ['students', 'teachers', 'announcements'])->name('admin.store');
        Route::put('/{resource}/{id}', [AdminController::class, 'save'])->whereIn('resource', ['students', 'teachers', 'announcements'])->whereNumber('id')->name('admin.update');
        Route::delete('/{resource}/{id}', [AdminController::class, 'delete'])->whereIn('resource', ['students', 'teachers', 'announcements'])->whereNumber('id')->name('admin.delete');
    });
});
