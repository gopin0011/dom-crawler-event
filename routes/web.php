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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/liputan6', 'EventController@liputan6Index');

Route::post('/liputan6', function () {
    return abort(404);
});

Route::get('/getPost', 'EventController@getPost');

Route::post('/getPost', function () {
    return abort(404);
});
