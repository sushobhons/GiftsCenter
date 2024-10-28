<?php

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

Route::post('/add-to-cart','CartController@addToCart')->name('add-to-cart');
Route::post('/add-to-wishlist','CartController@addToWishlist')->name('add-to-wishlist');
Route::post('/remove-from-wishlist','CartController@removeFromWishlist')->name('remove-from-wishlist');
Route::get('/my-basket','CartController@showCart')->name('my-basket');
Route::post('/fetch-cart','CartController@fetchCart')->name('fetch-cart');
Route::post('/update-cart','CartController@updateCart')->name('update-cart');
Route::post('/delete-cart','CartController@deleteCart')->name('delete-cart');
Route::post('/apply-coupon','CartController@applyCoupon')->name('apply-coupon');
Route::post('/fetch-offers','CartController@fetchOffers')->name('fetch-offers');
Route::post('/check-offer','CartController@checkOffer')->name('check-offer');
Route::post('/apply-offer','CartController@applyOffer')->name('apply-offer');
Route::post('/fetch-gifts','CartController@fetchGifts')->name('fetch-gifts');
Route::post('/add-gift-to-cart','CartController@addGiftToCart')->name('add-gift-to-cart');
Route::post('/proceed-to-checkout','CartController@proceedToCheckout')->name('proceed-to-checkout');
Route::post('/reorder','CartController@reorder')->name('reorder');
