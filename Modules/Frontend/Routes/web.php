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

Route::get('/', 'FrontendController@home')->name('home');
Route::get('/home', 'FrontendController@home');
Route::post('/fetch-home-segments', 'FrontendController@fetchHomeSegments')->name('fetch-home-segments');
Route::post('/fetch-brand-products', 'FrontendController@fetchBrandProducts')->name('fetch-brand-products');
Route::get('/fetch-feed-products', 'FrontendController@fetchFeedProducts')->name('feed-products');
Route::get('/page/{cmsslug}', 'FrontendController@cms');
Route::get('/find-store', 'FrontendController@stores')->name('find-store');
Route::get('/store/{storeId}', 'FrontendController@storeDetails')->name('store-detail');
//Route::get('/contact-us', 'FrontendController@contactUs');
Route::any('/contact-us', 'FrontendController@contactUs');

Route::any('/subscribe-newsletter', 'FrontendController@subscribeNewsletter');



Route::get('/brands', 'FrontendController@brandList')->name('brands');
Route::get('/brands/{slug}', function () {
    return view('frontend.pages.products');
})->name('brand-products');
Route::get('/cart', function () {
    return view('frontend.pages.cart');
})->name('cart');


// Route for: /brand/{brandSlug}/shop-by-collection/{collection}
Route::get('/brand/{brandSlug}/shop-by-collection/{collection}', 'FrontendController@productList')
    ->where(['brandSlug' => '[0-9A-Za-z_-]+', 'collection' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'collection')
    ->name('products.brand.collection');

// Route for: /brand/{brandSlug}/shop-by-collection
Route::get('/brand/{brandSlug}/shop-by-collection', 'FrontendController@productList')
    ->where('brandSlug', '[0-9A-Za-z_-]+')
    ->defaults('keyType', 'collection')
    ->name('products.brand.collection');

// Route for: /brand/{brandSlug}/{mainCat}/{subCatId}/{sort}/{start}/{list}
Route::get('/brand/{brandSlug}/{subCatId}/{sort}/{start}/{list}', 'FrontendController@productList')
    ->where(['brandSlug' => '[0-9A-Za-z_-]+', 'subCatId' => '[0-9A-Za-z_-]+', 'sort' => '[0-9A-Za-z_-]+', 'start' => '[0-9A-Za-z_-]+', 'list' => '[0-9A-Za-z_-]+'])
    ->name('products.brand.list');

// Route for: /brand/{brandSlug}/{mainCat}/{cat}/{subCat}
Route::get('/brand/{brandSlug}/{mainCat}/{cat}/{subCat}', 'FrontendController@productList')
    ->where(['brandSlug' => '[0-9A-Za-z_-]+', 'mainCat' => '[0-9A-Za-z_-]+', 'cat' => '[0-9A-Za-z_-]+', 'subCat' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'sub-category')
    ->name('products.brand.sub-category');

// Route for: /brand/{brandSlug}/{mainCat}/{cat}
Route::get('/brand/{brandSlug}/{mainCat}/{cat}', 'FrontendController@productList')
    ->where(['brandSlug' => '[0-9A-Za-z_-]+', 'mainCat' => '[0-9A-Za-z_-]+', 'cat' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'category')
    ->name('products.brand.category');

// Route for: /brand/{brandSlug}/{mainCat}
Route::get('/brand/{brandSlug}/{mainCat}', 'FrontendController@productList')
    ->where(['brandSlug' => '[0-9A-Za-z_-]+', 'mainCat' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'main-category')
    ->name('products.brand.main-category');

// Route for: /brand/{brandSlug}
Route::get('/brand/{brandSlug}', 'FrontendController@productList')
    ->where('brandSlug', '[0-9A-Za-z_-]+')
    ->defaults('keyType', 'brand')
    ->name('products.brand');

// Rule: skincare-by-concern/([0-9A-Za-z_-]+)
Route::get('/category/skincare-by-concern/{concernSlug}', 'FrontendController@productList')
    ->where('concernSlug', '[0-9A-Za-z_-]+')
    ->defaults('mainCategory', 'skin-care')
    ->defaults('keyType', 'by-concern')
    ->name('products.concern.category');

// Rule: skincare-by-concern
Route::get('/category/skincare-by-concern', 'FrontendController@productList')
    ->defaults('mainCategory', 'skin-care')
    ->defaults('keyType', 'by-concern')
    ->name('products.concern');

// Rule: shop-by-collection/([0-9A-Za-z_-]+)
Route::get('/category/shop-by-collection/{collectionSlug}', 'FrontendController@productList')
    ->where('collectionSlug', '[0-9A-Za-z_-]+')
    ->defaults('mainCategory', 'make-up')
    ->defaults('keyType', 'collection')
    ->name('products.collection.category');

// Rule: shop-by-collection
Route::get('/category/shop-by-collection', 'FrontendController@productList')
    ->defaults('mainCategory', 'make-up')
    ->defaults('keyType', 'collection')
    ->name('products.collection');

// Rule: category/([0-9A-Za-z_-]+)/([0-9A-Za-z_-]+)/([0-9A-Za-z_-]+)
Route::get('/category/{mainCategorySlug}/{categorySlug}/{subCategorySlug}', 'FrontendController@productList')
    ->where(['mainCategorySlug' => '[0-9A-Za-z_-]+', 'categorySlug' => '[0-9A-Za-z_-]+', 'subCategorySlug' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'sub-category')
    ->name('products.sub-category');

// Rule: category/([0-9A-Za-z_-]+)/([0-9A-Za-z_-]+)
Route::get('/category/{mainCategorySlug}/{categorySlug}', 'FrontendController@productList')
    ->where(['mainCategorySlug' => '[0-9A-Za-z_-]+', 'categorySlug' => '[0-9A-Za-z_-]+'])
    ->defaults('keyType', 'category')
    ->name('products.category');

// Rule: category/([0-9A-Za-z_-]+)
Route::get('/category/{mainCategorySlug}', 'FrontendController@productList')
    ->where('mainCategorySlug', '[0-9A-Za-z_-]+')
    ->defaults('keyType', 'main-category')
    ->name('products.main-category');

// Rule: offer/([0-9A-Za-z_-]+)
Route::get('/offer/{offerSlug}', 'FrontendController@productList')
    ->where('offerSlug', '[0-9A-Za-z_-]+')
    ->defaults('keyType', 'offer')
    ->name('products.offer');

// Rule: offer/([0-9A-Za-z_-]+)
Route::get('/offer', 'FrontendController@productList')
    ->defaults('keyType', 'offer')
    ->name('products.offer.all');

// Rule: category/([0-9A-Za-z_-]+)/([0-9A-Za-z_-]+)
Route::get('/best-seller/{mainCategorySlug}/{categorySlug}', 'FrontendController@productList')
    ->where(['mainCategorySlug' => '[0-9A-Za-z_-]+', 'categorySlug' => '[0-9A-Za-z_-]+'])
    ->defaults('segmentSlug', 'best-seller')
    ->defaults('keyType', 'segment')
    ->name('products.best-seller.category');

// Rule: category/([0-9A-Za-z_-]+)
Route::get('/best-seller/{mainCategorySlug}', 'FrontendController@productList')
    ->where('mainCategorySlug', '[0-9A-Za-z_-]+')
    ->defaults('segmentSlug', 'best-seller')
    ->defaults('keyType', 'segment')
    ->name('products.best-seller.main-category');

// Rule: category/([0-9A-Za-z_-]+)
Route::get('/best-seller', 'FrontendController@productList')
    ->defaults('segmentSlug', 'best-seller')
    ->defaults('keyType', 'segment')
    ->name('products.best-seller');

// Rule: category/([0-9A-Za-z_-]+)/([0-9A-Za-z_-]+)
Route::get('/new-arrival/{mainCategorySlug}/{categorySlug}', 'FrontendController@productList')
    ->where(['mainCategorySlug' => '[0-9A-Za-z_-]+', 'categorySlug' => '[0-9A-Za-z_-]+'])
    ->defaults('segmentSlug', 'new-arrival')
    ->defaults('keyType', 'segment')
    ->name('products.new-arrival.category');

// Rule: category/([0-9A-Za-z_-]+)
Route::get('/new-arrival/{mainCategorySlug}', 'FrontendController@productList')
    ->where('mainCategorySlug', '[0-9A-Za-z_-]+')
    ->defaults('segmentSlug', 'new-arrival')
    ->defaults('keyType', 'segment')
    ->name('products.new-arrival.main-category');

// Rule: category/([0-9A-Za-z_-]+)
Route::get('/new-arrival', 'FrontendController@productList')
    ->defaults('segmentSlug', 'new-arrival')
    ->defaults('keyType', 'segment')
    ->name('products.new-arrival');

// Rule: search/([0-9A-Za-z_-]+)
Route::get('/search/{searchSlug}', 'FrontendController@productList')
    ->where('searchSlug', '[0-9A-Za-z\s\-&]+')
    ->defaults('keyType', 'search')
    ->name('products.search');

// Rule: shop/([0-9A-Za-z_-]+)
Route::get('/shop/{segmentSlug}', 'FrontendController@productList')
    ->defaults('segmentSlug', '[0-9A-Za-z_-]+')
    ->defaults('keyType', 'segment')
    ->name('products.shop');





Route::get('/products', 'FrontendController@productList')->name('products-list');
Route::post('/products-filter', 'FrontendController@productFilter')->name('products-filter');
Route::post('/new-products-filter', 'FrontendController@newProductFilter')->name('new-products-filter');
Route::post('/products-search', 'FrontendController@productSearch')->name('products-search');
Route::post('/fetch-product-detail','FrontendController@fetchProductDetail')->name('fetch-product');
Route::post('/product-images','FrontendController@productImages')->name('product-images');
Route::get('/product/{slug}','FrontendController@productDetail')->name('product-detail');
Route::post('/fetch-related-products','FrontendController@fetchRelatedProducts')->name('fetch-related-product');
Route::get('/gift-vouchers', 'FrontendController@voucherList')->name('gift-vouchers');
Route::get('/e-gift-voucher', 'FrontendController@voucherDetail')->name('e-gift-voucher');
Route::post('/product-review', 'FrontendController@productReview')->name('product-review');


Route::prefix('frontend')->group(function () {
    Route::get('/', 'FrontendController@index');
});

Route::prefix('cron')->name('cron.')->group(function () {
    Route::get('/send-evoucher', 'FrontendController@cronSendEvoucher')->name('send-evoucher');
    Route::get('/send-birthday-sms', 'FrontendController@cronSendBirthdaySms')->name('send-birthday-sms');
    Route::get('/point-expiry', 'FrontendController@cronPointExpiry')->name('point-expiry');
    Route::get('/sitemap', 'FrontendController@cronGenerateSitemap')->name('sitemap');
});
