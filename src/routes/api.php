<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

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

Route::get('/getdoctype', '\Erp\Http\Controllers\BaseDocument@getdoctype');
Route::post('/method/{name}', '\Erp\Http\Controllers\BaseDocument@method');
Route::post('/savedoc', '\Erp\Http\Controllers\BaseDocument@savedoc');

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });