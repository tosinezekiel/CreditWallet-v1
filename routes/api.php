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
    Route::post('investments/invite', 'InvestmentController@create'); 
    Route::post('investment/forget-password', 'InvestmentLoginController@forgotPassword');
    Route::get('investment/verify-forget-password-token', 'InvestmentLoginController@VerifyForgotPasswordToken');    
    Route::get('generate-pdf','PDFController@generatePDF');
});

Route::namespace('Investment')->prefix('v1')->group(function () {
    Route::post('investments/login', 'InvestmentLoginController@login');
});
Route::group(['middleware' => 'investments', 'prefix' => 'v1'],function () {
    Route::get('investments/logout', 'InvestmentLoginController@logout');
    Route::post('investments/change-password', 'InvestmentController@changePassword');
    Route::get('investments/dashboard', 'InvestmentController@savingsDashboard'); 
    Route::get('investments/{savings_id}/single_savings', 'InvestmentController@singleSavings');
    Route::get('investments/{savings_id}/savings', 'InvestmentController@savings');
    Route::post('investments/{savings_id}/merge', 'InvestmentController@merge');
    Route::post('investments/{savings_id}/merge', 'InvestmentController@multiaction');
    Route::post('investments/stage1/transactions', 'InvestmentController@deleteSavingsTransactionsOfOtherMonths');
    Route::post('investments/stage2/add', 'InvestmentController@addtransaction');
    Route::post('investments/stage3/proceed', 'InvestmentController@calculateThisMonthInterest');
    Route::post('investments/stage4/proceed', 'InvestmentController@calculateForStageFour');
    Route::post('investments/stage5/proceed', 'InvestmentController@calculateForStageFive');
    // Route::get('generate-pdf','PDFController@generatePDF');
    Route::get('generate-pdf2','PDFController@generatePDF2');
    Route::get('generate-lie','PDFController@generatePDFtest');
    Route::get('investments/{savings_id}/generate-statement','PDFController@generatePDFtest');
    Route::post('investments/txntest','InvestmentController@getSingleSavingsTransactionsTest');
    //investment start
    Route::post('investments/merge/start', 'InvestmentstartController@create');
    Route::post('investments/merge/initiate', 'InvestmentstartController@initiate');
});

Route::namespace('Api')->group(function(){
    Route::post('/phloans','PhloanController@store');
});


