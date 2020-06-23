<?php

use App\Loan;
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
    Route::post('investments/setinvitecode', 'InvestmentController@setinvitecode');
    Route::post('investments/invite', 'InvestmentController@create'); 
    Route::post('investment/forget-password', 'InvestmentLoginController@forgotPassword');
    Route::get('investment/verify-forget-password-token', 'InvestmentLoginController@VerifyForgotPasswordToken');    
    Route::get('generate-pdf','PDFController@generatePDF');
    
    Route::post('investments/merge/start', 'InvestmentstartController@create');
    Route::post('investments/merge/initiate', 'InvestmentstartController@initiate');
    
    Route::post('investments/set-password','InvestmentController@setPassword');

    // test without guard

    Route::post('loans/calculate-repayment','LoanController@calculateRepayment');
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
    
    // Route::get('generate-pdf','PDFController@generatePDF');
    Route::get('generate-pdf2','PDFController@generatePDF2');
    Route::get('generate-lie','PDFController@generatePDFtest');
    Route::get('investments/{savings_id}/generate-statement','PDFController@generatePDFtest');
    Route::post('investments/txntest','InvestmentController@getSingleSavingsTransactionsTest');
    
    //investment start
    // Route::post('investments/merge/start', 'InvestmentstartController@create');
    // Route::post('investments/merge/initiate', 'InvestmentstartController@initiate');
});

//admin guard
Route::group(['middleware' => 'admin', 'prefix' => 'v1'],function () {
    //resource requests endpoints
    Route::post('resources-requests/create', 'ResourcerequestController@store');
    Route::get('resources-requests/awaiting', 'ResourcerequestController@awaiting');
    Route::get('resources-requests/approved', 'ResourcerequestController@approved');
    Route::get('resources-requests/rejected', 'ResourcerequestController@rejected');
    Route::get('resources-requests/canceled', 'ResourcerequestController@canceled');
    Route::post('resources-requests', 'ResourcerequestController@index');
    // Route::post('getrequest-email', 'ResourcerequestController@testmail');
    Route::get('resources-requests/{resourcerequest}', 'ResourcerequestController@show');
    Route::post('resources-requests/reject', 'ResourcerequestController@reject');
    Route::post('resources-requests/approve', 'ResourcerequestController@approve');
    Route::get('resources-requests/{resourcerequest}/cancel', 'ResourcerequestController@cancel');
    Route::get('auth-users', 'UserController@index');
    
    //merge process
    
    Route::post('investmentstart/update', 'InvestmentController@updatestage');
    // Route::get('investments', 'InvestmentController@index');
    Route::post('investments', 'InvestmentController@listInvestment');
    Route::post('investments/search', 'InvestmentController@filterByParams');
    Route::post('investments/stage1/transactions', 'InvestmentController@deleteSavingsTransactionsOfOtherMonths');
    Route::post('investments/stage2/add', 'InvestmentController@addtransaction');
    Route::post('investments/stage3/proceed', 'InvestmentController@calculateThisMonthInterest');
    Route::post('investments/stage4/proceed', 'InvestmentController@calculateForStageFour');
    Route::post('investments/stage5/proceed', 'InvestmentController@calculateForStageFive');
    Route::post('investments/stage6/proceed','PDFController@generatePDF2');

    Route::post('investments/rollover','InvestmentrolloverController@rollover');
    Route::post('investments/rollover-pdf','InvestmentrolloverController@generateSchedulePDF');
    
    Route::get('loanrepayments/{loan_id}', 'loanrepayment\LoanrepaymentController@index');
    Route::post('loanrepayments/update', 'loanrepayment\LoanrepaymentController@update');
    Route::get('loanrepayments/{loan_id}/delete', 'loanrepayment\LoanrepaymentController@delete');

    Route::post('loans', 'LoanController@apply');
    Route::post('loans/list', 'LoanController@index');
    Route::post('loans/check', 'LoanController@generateofferletter');
    Route::post('loans/edit', 'LoanController@editloanapplication');
    Route::post('loans/comment', 'LoanController@addcomment');
    Route::post('loans/g-check', 'LoanController@checkmethod');

});

Route::namespace('Loanrepayment')->prefix('v1')->group(function () {
//   Route::get('loanrepayments/{loan_id}', 'LoanrepaymentController@index');
//   Route::post('loanrepayments/update', 'LoanrepaymentController@update');
//   Route::get('loanrepayments/{loan_id}/delete', 'LoanrepaymentController@delete');
});


