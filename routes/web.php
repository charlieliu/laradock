<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InspiringController;

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
    return view('content_p', [
        'active'    => 'hello_world',
        'title'     => 'Hello world',
        'content'   => 'Hello World！'
    ]);
});


Route::get('/hello-world', function () {
    return view('content_p', [
        'active'    => 'hello_world',
        'title'     => 'Hello world',
        'content'   => 'Hello World！'
    ]);
});

Route::get('/about_us', function () {
    return view('content_p', [
        'active'    => 'about_us',
        'title'     => 'About Us',
        'content'   => '嗨！大家好！我們是 Laravel 範例'
    ]);
});


Route::get('/inspire',      [InspiringController::class, 'list']);
Route::get('/inspire/data', [InspiringController::class, 'inspire']);
