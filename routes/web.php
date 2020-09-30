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


Route::middleware(['checkIp'])->group(function () {
    Route::get('/', 'VideoController@index');
    Route::get('uploader', 'VideoController@uploader')->name('uploader');
    Route::post('upload', 'VideoController@store')->name('upload');
    Route::get('video/{video_id}/{quality?}/{format?}', 'VideoController@retrieve')->name('retrieve');
    Route::get('video/delete/{video_id?}', 'VideoController@destroy')->name('destroy');
});
