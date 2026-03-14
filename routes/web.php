<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DailyNoteController;
use App\Http\Controllers\HrCounterController;
use App\Http\Controllers\InternshipSettingsController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TaskPageController;
use App\Http\Controllers\WorkSessionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'Welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::get('hr-counter', HrCounterController::class)->name('hr-counter');
    Route::get('tasks', TaskPageController::class)->name('tasks.index');
    Route::post('internship-settings', [InternshipSettingsController::class, 'store'])->name('internship-settings.store');
    Route::post('daily-notes', [DailyNoteController::class, 'store'])->name('daily-notes.store');

    Route::post('work-sessions', [WorkSessionController::class, 'store'])->name('work-sessions.store');
    Route::post('work-sessions/bulk-add', [WorkSessionController::class, 'bulkStore'])->name('work-sessions.bulk-store');
    Route::delete('work-sessions/by-date', [WorkSessionController::class, 'destroyByDate'])->name('work-sessions.destroy-by-date');
    Route::patch('work-sessions/{workSession}', [WorkSessionController::class, 'update'])->name('work-sessions.update');
    Route::delete('work-sessions/{workSession}', [WorkSessionController::class, 'destroy'])->name('work-sessions.destroy');
    Route::post('work-sessions/clock-in', [WorkSessionController::class, 'clockIn'])->name('work-sessions.clock-in');
    Route::post('work-sessions/clock-out', [WorkSessionController::class, 'clockOut'])->name('work-sessions.clock-out');

    Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
    Route::patch('tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');
    Route::delete('tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
});

require __DIR__.'/settings.php';
