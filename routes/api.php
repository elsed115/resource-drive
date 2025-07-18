<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Elses115\ResourceDrive\Http\Controllers\FileController;
/*
|--------------------------------------------------------------------------
| Tool API Routes
|--------------------------------------------------------------------------
|
| Here is where you may register API routes for your tool. These routes
| are loaded by the ServiceProvider of your tool. You're free to add
| as many additional routes to this file as your tool may require.
|
*/

Route::get('/list', [FileController::class, 'list']);
Route::post('/upload', [FileController::class, 'upload']);
Route::post('/create-folder', [FileController::class, 'createFolder']);
Route::post('/rename', [FileController::class, 'rename']);
Route::post('/delete', [FileController::class, 'delete']);
