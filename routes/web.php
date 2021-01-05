<?php

use App\Http\Middleware\AllowOnlyOwner;
use App\Http\Middleware\AuthenticateSlackApp;
use Illuminate\Support\Facades\Route;

Route::view('about','about')->name('about');
Route::get('login/github', 'Auth\LoginController@redirectToProvider')->name('oauth.login.redirect');
Route::get('oauth/callback', 'Auth\LoginController@handleProviderCallback')->name('oauth.login.callback');
Route::post('logout', 'Auth\LoginController@logout')->name('logout');

Route::view('/', 'home')->name('home');

Route::prefix('growth_sessions')->middleware(AuthenticateSlackApp::class)->name('growth_sessions.')->group(function() {
    Route::get('week', 'GrowthSessionController@week')->name('week');
    Route::get('day', 'GrowthSessionController@day')->name('day');
    Route::get('{growth_session}', 'GrowthSessionController@show')->name('show');
    Route::get('{growth_session}/edit', 'GrowthSessionController@edit')->middleware(['auth','can:update,growth_session'])->name('edit');
    Route::post('{growth_session}/join', 'GrowthSessionController@join')->middleware(['auth', 'can:join,growth_session'])->name('join');
    Route::post('{growth_session}/leave', 'GrowthSessionController@leave')->middleware(['auth', 'can:leave,growth_session'])->name('leave');
});
Route::resource('growth_sessions', 'GrowthSessionController')->middleware('auth')->only(['store', 'update', 'destroy']);

Route::prefix('growth_sessions/{growth_session}/comments')->name('growth_sessions.comments.')->group(function() {
    Route::get('/', 'CommentController@index')->name('index');
});
Route::resource('growth_sessions.comments', 'CommentController')->middleware('auth')->only(['store','update','destroy']);
