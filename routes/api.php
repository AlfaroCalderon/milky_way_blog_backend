<?php

use App\Http\Controllers\BlogPostController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;

Route::middleware('api.key')->group(function(){
    Route::prefix('v1/user')->group(function(){
        Route::post('/signup', [UserController::class, 'register']);
        Route::post('/signin', [UserController::class, 'login']);

        Route::prefix('/management')->middleware('jwt.auth')->group(function(){
            Route::get('/users',[UserController::class,'getAllUsers']);
            Route::get('/{id}',[UserController::class, 'getUserById']);
            Route::patch('/update/{id}',[UserController::class, 'updateUser']);
            Route::patch('/delete/{id}', [UserController::class,'deleteUser']);
        });
    });
    Route::prefix('v1/blog')->group(function(){

        Route::get('/list', [BlogPostController::class, 'getAllPost']);
        Route::get('/{id}',[BlogPostController::class, 'getPostById']);

        Route::prefix('/management')->middleware('jwt.auth')->group(function(){
            Route::post('/create',[BlogPostController::class, 'createBlogPost']);
            Route::get('/user_posts/{id}', [BlogPostController::class, 'getAllPostByUser']);
            Route::patch('/update/{id}',[BlogPostController::class, 'updatePost']);
            Route::patch('/delete/{id}',[BlogPostController::class, 'deletePost']);
        });
    });
});
