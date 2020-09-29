<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Route::get('', 'VideoController@index');
// Route::get('uploader', 'VideoController@uploader')->name('uploader');
// Route::post('upload', 'VideoController@store')->name('upload');
// Route::get('video/{video_id}/{quality}/{format}', 'VideoController@retrieve')->name('retrieve');
// Route::get('video/delete/{video_id}', 'VideoController@destroy')->name('destroy');