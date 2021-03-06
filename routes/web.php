<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::get('signup/activate/{token}', 'App\Http\Controllers\AuthController@signupActivate');

Route::get('find/{token}', 'App\Http\Controllers\PasswordResetController@find');
Route::post('reset', 'App\Http\Controllers\PasswordResetController@reset');