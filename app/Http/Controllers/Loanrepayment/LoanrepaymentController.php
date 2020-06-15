<?php

namespace App\Http\Controllers\Loanrepayment;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;

class LoanrepaymentController extends Controller
{
    public function index($loan_id){
        // https://api-main.loandisk.com/3546/4110/loan
        // /loan/{loan_id}/from/{Page Number}/count/{Number of Results}
            $url = "https://api-main.loandisk.com/3546/4110/repayment/loan/".$loan_id."/from/1/count/50";
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

    public function update(){
        $rules = [
            'loan_id' => 'required',
            'repayment_id' => 'required'
        ];
        Validator::make(request()->all(), $rules)->validate();
        
        $url = "https://api-main.loandisk.com/3546/4110/repayment";
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode(request()->all()),
            CURLOPT_HTTPHEADER => array(
                "accept: application/json",
                "cache-control: no-cache",
                "content-type: application/json",
                "Authorization: Basic AkMbezWYERkE5NcDsXAM7YzkxDySG9amAKvajU9d"
            ),
        ));
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        $mdata = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if(isset($mdata['response']['Errors'])){
            return response(['message'=>$mdata['response']['Errors'][0],'status'=>'error'], 422);
        }
        if($mdata['http']['code'] == 200){
            return response(['message'=>"loan has been updated successfully",'status'=>'success'], 200);
        }
        $response['status'] = "error";
        $response['data'] = $mdata;
        $response['message'] = "Something went wrong, please try again but if problem persist, please contact our customer support team on support@creditwallet.ng";
        echo json_encode($response);
    }

    public function delete($loan_id){
        $rules = [
            'loan_id' => 'required',
            'repayment_id' => 'required'
        ];
        Validator::make(request()->all(), $rules)->validate();
        // return request()->all();
        $url = "https://api-main.loandisk.com/3546/4110/repayment/".request()->repayment_id;
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "DELETE",
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
        // return $data;
        if(isset($data['error'])){
            return response(['message'=>$data['error']['message'],'status'=>'error'], 404);
        }
        if($data['http']['code'] == 200){
            return response(['message'=>"loan has been deleted successfully",'status'=>'success'], 200);
        }
        $response['status'] = "error";
        $response['data'] = $data;
        $response['message'] = "Something went wrong, please try again but if problem persist, please contact our customer support team on support@creditwallet.ng";
        echo json_encode($response);
    }
}
