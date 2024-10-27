<?php
use Modules\Order\Http\Controllers\OrderController;

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
Route::get('/checkout', 'OrderController@checkout')->name('checkout');
Route::post('/set-delivery', 'OrderController@setDelivery')->name('set-delivery');
Route::post('/fetch-cities', 'OrderController@fetchCities')->name('fetch-cities');
Route::post('/delete-delivery-address', 'OrderController@deleteDeliveryAddress')->name('delete-delivery-address');
Route::get('/payment', 'OrderController@payment')->name('payment');
Route::post('/send-redeem-otp', 'OrderController@sendRedeemOtp')->name('send-redeem-otp');
Route::post('/verify-redeem-otp', 'OrderController@verifyRedeemOtp')->name('verify-redeem-otp');
Route::post('/redeem-loyalty-points', 'OrderController@redeemLoyaltyPoints')->name('redeem-loyalty-points');
Route::post('/verify-voucher-code', 'OrderController@verifyVoucherCode')->name('verify-voucher-code');
//Route::get('/order-processing', 'OrderController@orderProcessing')->name('order-processing');
Route::match(['get', 'post'], '/order-processing', 'OrderController@orderProcessing')->name('order-processing');
Route::post('/montypay-callback', 'OrderController@montyPayCallback')->name('montypay-callback')->withoutMiddleware(['web', 'csrf']);
Route::post('/order-placed', 'OrderController@orderPlaced')->name('order-placed');
Route::get('/test-order-placed', 'OrderController@testOrderPlaced')->name('test-order-placed');
Route::get('/order-placed-test', 'OrderController@orderPlacedTest')->name('order-placed-test');
Route::post('/add-survey', 'OrderController@addSurvey')->name('add-survey');
Route::post('/order-review', 'OrderController@orderReview')->name('order-review');
Route::post('/order-share', 'OrderController@orderShare')->name('order-share');
Route::prefix('order')->group(function() {
    Route::get('/', 'OrderController@index');
});
Route::get('/orders/{customerId}', 'OrderController@orderListById')->name('orders');
Route::get('/order-details/{customerId}/{invoiceNumber}', 'OrderController@orderDetailsById')->name('order-details-by-id');
Route::get('/your-orders', 'OrderController@orderList')->name('order-history');

//Route::get('/manage-orders', 'OrderController@fetchAll')->name('manage-orders');
//Route::get('/manage-orders', [OrderController::class, 'fetchAll'])->name('manage-orders');
Route::post('/fetch-orders', 'OrderController@fetchOrders')->name('fetch-orders');
Route::post('/fetch-points', 'OrderController@fetchPoints')->name('fetch-points');
Route::post('/export-excel', 'OrderController@exportExcel')->name('export-excel');
Route::get('/order/{orderId}', 'OrderController@orderDetails')->name('order-details');
Route::get('/point-transactions', 'OrderController@pointList')->name('point-transactions');
Route::get('/point-transactions/{customerId}', 'OrderController@pointListById')->name('point-transactions-by-id');







