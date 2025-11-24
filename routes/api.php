<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::get('/hehe', [App\Http\Controllers\TestController::class, 'index']);
Route::post('register', [App\Http\Controllers\Auth\AuthController::class, 'register']);
Route::post('login', [App\Http\Controllers\Auth\AuthController::class, 'login']);
Route::get('/list-post', [App\Http\Controllers\PostController::class, 'listPost']);
Route::post('/add-post', [App\Http\Controllers\PostController::class, 'addPost']);
Route::post('/delete-post', [App\Http\Controllers\PostController::class, 'deletePost']);
Route::post('/like-post', [App\Http\Controllers\PostController::class, 'likePost']);
Route::post('/save-post', [App\Http\Controllers\PostController::class, 'savePost']);
Route::get('/my-post', [App\Http\Controllers\PostController::class, 'myPost']);
Route::get('/my-saved', [App\Http\Controllers\PostController::class, 'mySaved']);
Route::get('/explore-post', [App\Http\Controllers\PostController::class, 'explorePost']);
Route::get('/suggest-friend', [App\Http\Controllers\UserController::class, 'suggestFriend']);
Route::post('follow', [App\Http\Controllers\UserController::class, 'follow']);
Route::post('comment', [App\Http\Controllers\PostController::class, 'comment']);
Route::get('list-comment', [App\Http\Controllers\PostController::class, 'listComment']);
Route::get('list-story', [App\Http\Controllers\StoryController::class, 'listStory']);
Route::post('add-story', [App\Http\Controllers\StoryController::class, 'addStory']);
Route::post('delete-story', [App\Http\Controllers\StoryController::class, 'deleteStory']);
Route::get('get-info', [App\Http\Controllers\UserController::class, 'getInfo']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');


