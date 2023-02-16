<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InspiringController;
use App\Http\Controllers\TelegramBotController;

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

Route::get('/tg_bot',                       [TelegramBotController::class, 'bots']);
Route::get('/tg_bot/chats',                 [TelegramBotController::class, 'chats']);
Route::get('/tg_bot/chat_messages/{id}',    [TelegramBotController::class, 'chatMessages']);
Route::get('/tg_bot/users',                 [TelegramBotController::class, 'users']);
Route::get('/tg_bot/user_messages/{id}',    [TelegramBotController::class, 'userMessages']);
Route::get('/tg_bot/read/{name}',           [TelegramBotController::class, 'read']);
Route::get('/tg_bot/run/{name}',            [TelegramBotController::class, 'run']);
