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

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
Route::prefix('v1')->group(function () {
    Route::post('get-token', 'AdminloginController@getToken');
    Route::get('read-token', 'AdminloginController@readToken');
    Route::post('resources-requests', 'ResourcerequestController@store');
    Route::get('resources-requests', 'ResourcerequestController@index');
    Route::get('resources-requests/{resourcerequest}', 'ResourcerequestController@show');
    Route::post('resources-requests/{resourcerequest}/reject', 'ResourcerequestController@reject');
    Route::get('resources-requests/{resourcerequest}/approve', 'ResourcerequestController@approve');
    Route::get('resources-requests/{resourcerequest}/cancel', 'ResourcerequestController@cancel');
});

Route::group(['middleware' => ['jwt.verify']], function() {
    Route::get('verify-token', 'UserController@checkToken');
});
















Route::namespace('Api')->group(function(){
    Route::post('/phloans','PhloanController@store');
});


