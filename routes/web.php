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
Route::get('/login', function () {
    return view('welcome');
});
Route::get('/signin', function () {
    return view('welcome');
});
Route::get('/user', function () {
    return view('welcome');
});
Route::get('/main', function () {
    return view('welcome');
});
Route::get('/region', function () {
    return view('welcome');
});


// 잘못된 URL입력시
Route::fallback(function(){
    return redirect('/error');
});

Route::get('/error', function () {
    return view('welcome');
});