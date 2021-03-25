<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('create', 'App\Http\Controllers\PasswordResetController@create');
Route::post('forgetPassword', 'App\Http\Controllers\PasswordResetController@forgetPassword');
Route::post('register', 'App\Http\Controllers\AuthController@register');
Route::post('login', 'App\Http\Controllers\AuthController@login');
Route::post('generate-otp', 'App\Http\Controllers\AuthController@generateOtp');
Route::post('login-otp', 'App\Http\Controllers\AuthController@loginOtp');
Route::post('login-fb', 'App\Http\Controllers\AuthController@fbLogin');
Route::post('reset-password', 'App\Http\Controllers\AuthController@saveResetPassword');

Route::group(['middleware' => 'auth:api'], function() {
	Route::get('logout', 'App\Http\Controllers\AuthController@logout');

	Route::put('update', 'App\Http\Controllers\UserController@update');
	Route::post('user-activate', 'App\Http\Controllers\UserController@activateUser');
	Route::delete('delete/{id}', 'App\Http\Controllers\AuthController@delete');
	Route::get('me', 'App\Http\Controllers\AuthController@me');
	Route::get('all', 'App\Http\Controllers\UserController@show');

	Route::post('upload-notify', 'App\Http\Controllers\UserController@uploadAndNotify');
	Route::post('all-notifications', 'App\Http\Controllers\UserController@allNotifications');
	Route::post('users-notifications', 'App\Http\Controllers\UserController@usersNotifications');

	
});