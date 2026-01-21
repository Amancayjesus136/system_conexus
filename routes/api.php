<?php

use App\Http\Controllers\Admin\AlmacenController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\MedidorController;
use App\Http\Controllers\Api\MedidoresController;
use App\Http\Controllers\dev\CommandController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

    Route::post('/almacen/store', [MedidorController::class, 'store_django']);


Route::group([
    'middleware' => 'api',
    'prefix' => 'auth',
],function($router){

        Route::middleware('auth:api')->group(function () {


    });
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
