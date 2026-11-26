<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', [TaskController::class, 'index'])->name('dashboard');
Route::get('/tasks/calendar', [TaskController::class, 'calendar'])->name('tasks.calendar');
Route::get('/tasks/calendar/events', [TaskController::class, 'calendarEvents'])->name('tasks.calendar.events');
Route::get('/tasks/export', [TaskController::class, 'export'])->middleware('throttle:30,1')->name('tasks.export');
Route::post('/tasks/reorder', [TaskController::class, 'reorder'])->middleware('throttle:60,1')->name('tasks.reorder');
Route::patch('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status');
Route::resource('tasks', TaskController::class)->except(['show']);
