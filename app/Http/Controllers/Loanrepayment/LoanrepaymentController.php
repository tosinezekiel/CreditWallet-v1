<?php

namespace App\Http\Controllers\Loanrepayment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class LoanrepaymentController extends Controller
{
    public function index($loan_id){
        // https://api-main.loandisk.com/3546/4110/loan
            $url = "https://api-main.loandisk.com/3546/4110/loan/".$loan_id;
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "accept: application/json",
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "Authorization: Basic AkMbezWYERkE5NcDsXAM7YzkxDySG9amAKvajU9d"
                ),
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $data = json_decode(curl_exec($curl), true); 
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            if(!empty($data['error'])){
                return response(['status' => 'Error','message' => 'result not found!'], 404);
            }
    
            if(empty($data)){
                return response(['status' => 'Error','message' => 'Bad Connection!'], 404); 
            }
    
            $pre_data = $data['response']['Results']['0'];
            if($pre_data === null){
                return response(['status' => 'Error','message' => 'result not found!'], 404);
            }
            return $pre_data;
    }
}
