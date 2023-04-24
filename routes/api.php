<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\OrderController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
*/
Route::middleware('validate.request', 'form.validate')->group(function(){
    Route::group(['prefix'=>'orders'],function(){
        Route::get('','App\Http\Controllers\OrderController@index');
        Route::post('','App\Http\Controllers\OrderController@create');
        Route::put('/{id}','App\Http\Controllers\OrderController@update');
        Route::delete('/{id}','App\Http\Controllers\OrderController@delete');
        Route::post('/{id}/add','App\Http\Controllers\OrderController@addProduct');
        Route::post('/{id}/pay','App\Http\Controllers\OrderController@payOrder');
    });
});
