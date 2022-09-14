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
    return view('hello_world');
});


Route::get('/hello-world', function () {
    return view('hello_world', ['title'=>'Hello world']);
});

Route::get('/about_us', function () {
    return view('about_us', ['title'=>'About Us', 'name'=>'Laravel 範例']);
});


Route::get('/inspire', [InspiringController::class, 'inspire']);
