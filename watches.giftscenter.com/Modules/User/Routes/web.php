<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\User\Http\Controllers\UserController;

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

Route::get('sign-in', 'UserController@register')->name('register.form');

Route::prefix('user')->group(function () {
    Route::get('login', 'UserController@login')->name('login.form');
    Route::post('login', 'UserController@loginSubmit')->name('login.submit');
    Route::get('logout', 'UserController@logout')->name('user.logout');


    Route::post('register', 'UserController@registerSubmit')->name('register.submit');
   // Route::post('register', 'UserController@registerSubmit')->name('register.submit');

   Route::post('/send-otp', 'UserController@sendOTP');
   Route::post('/verify-otp', 'UserController@verifyOTP');
   Route::post('/sign-up', 'UserController@signUp');
});
Route::get('/loyalty-program', 'UserController@loyaltyProgram')->name('loyalty.program');
Route::get('/my-account', 'UserController@myAccount')->name('myaccount');
Route::post('/save-profile', 'UserController@saveProfile');

Route::get('/request-product', 'UserController@requestProduct')->name('request-product');
Route::post('/save-request-product', 'UserController@saveRequestProduct')->name('save-request-product');

Route::middleware(['web', 'auth'])->group(function () {
    // Protected routes requiring authentication
    
});
Route::get('/loves-list', 'UserController@lovesList')->name('lovesList');