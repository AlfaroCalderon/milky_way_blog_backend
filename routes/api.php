<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware('api.key')->group(function(){
    Route::prefix('v1/user')->group(function(){
        Route::post('/signup', [UserController::class, 'register']);
        Route::post('/signin', [UserController::class, 'login']);
    });
});
