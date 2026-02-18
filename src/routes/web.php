<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\CorrectionRequestController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminAttendanceController;
use App\Http\Controllers\Admin\AdminStaffController;
use App\Http\Controllers\Admin\AdminCorrectionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
// routes/web.php
Route::get('/', function () {
    return redirect('/login');
});

Route::get('/admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminAuthController::class, 'login'])->name('admin.login.submit');

Route::middleware('auth')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance');
    Route::post('/attendance/start', [AttendanceController::class, 'start'])->name('attendance.start');
    Route::post('/attendance/end', [AttendanceController::class, 'end'])->name('attendance.end');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}/correct', [AttendanceController::class, 'correct'])->name('attendance.correct');
    Route::get('/stamp_correction_request/list', [CorrectionRequestController::class, 'list'])->name('correction.user.list');
});

Route::prefix('admin')->name('admin.')->middleware('auth:admin')->group(function () {
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
    Route::get('/attendance/list', [AdminAttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/{id}', [AdminAttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/{id}', [AdminAttendanceController::class, 'update'])->name('attendance.update');
    Route::get('/staff/list', [AdminStaffController::class, 'list'])->name('staff.list');
    Route::get('/attendance/staff/{id}', [AdminStaffController::class, 'staffAttendance'])->name('staff.attendance');
    Route::get('/attendance/staff/{id}/csv', [AdminStaffController::class, 'exportCsv'])->name('staff.csv');
    Route::get('/stamp_correction_request/approve/{id}', [AdminCorrectionController::class, 'show'])->name('correction.show');
    Route::get('/stamp_correction_request/list', [AdminCorrectionController::class, 'list'])
    ->name('correction.list');
    Route::post('/stamp_correction_request/approve/{id}', [AdminCorrectionController::class, 'approve'])->name('correction.approve');
});
