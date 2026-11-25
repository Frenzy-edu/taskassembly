<?php

use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::patch('tasks/{task}/status', [TaskController::class, 'updateStatus']);

Route::apiResource('tasks', TaskController::class);
