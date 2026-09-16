<?php

use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RegionController;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;


$locale = request()->segment(1);
$urlLocales = ['ru', 'ua', 'en'];

if (!in_array($locale, $urlLocales)) {
    $locale = '';
}

Route::group([
    'prefix' => $locale,
], function () {
    Route::get('/', [\App\Http\Controllers\RouteController::class, 'showHome'])->name('home');
    Route::get('/services/{service}', [\App\Http\Controllers\RouteController::class, 'showService'])->name('service');
});

Route::post('/appointment', [AppointmentController::class, 'store'])->name('appointments.store');
Route::post('/checkout', [AppointmentController::class, 'checkout'])->name('checkout.promo');


Route::get('/info', function () {
    return [
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
    ];
});
