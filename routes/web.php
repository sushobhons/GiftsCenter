<?php

use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\SitemapGenerator;

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



Route::get('/generate-sitemap', function () {
    SitemapGenerator::create('https://www.taraf-jo.com/')->writeToFile(public_path('sitemap.xml'));

    return 'Sitemap generated!';
});
