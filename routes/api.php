<?php

use Illuminate\Support\Facades\Route;
use Elsed115\ResourceDrive\Http\Controllers\FileController;

Route::get   ('/list'         , [FileController::class, 'list']);
Route::post  ('/upload'       , [FileController::class, 'upload']);
Route::post  ('/create-folder', [FileController::class, 'createFolder']);
Route::post  ('/rename'       , [FileController::class, 'rename']);
Route::post  ('/delete'       , [FileController::class, 'delete']);
