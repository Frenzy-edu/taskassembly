<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'message' => 'Task Manager API',
        'tasks_endpoint' => url('/api/tasks'),
    ]);
});
