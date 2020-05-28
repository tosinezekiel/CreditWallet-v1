<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PDF;

class PDFController extends Controller
{
    public function generatePDF()
    {
        // return request()->savings_id;
        date_default_timezone_set('Africa/Lagos');
        $url = "https://api-main.loandisk.com/3546/4110/saving/".request()->savings_id;
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
        
        $savings_account = $data['response']['Results']['0'];

        $url = "https://api-main.loandisk.com/3546/4110/saving_transaction/saving/".request()->savings_id."/from/1/count/50";
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
        $txndata = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $pre_data = $txndata['response']['Results']['0'];
        // return $pre_data;
        $firstdate = '';
        $i = 0;
        $amt = 0;
        
        foreach($pre_data as $key){
            if($key['transaction_type_id'] == 1){
                $amt += $key['transaction_amount'];
                if($firstdate == ''){
                    $firstdate = $key['transaction_date'];
                }
            }
        }
        // return $firstdate;
        $fd = str_replace('/', '-', $firstdate);
        // return $pre_data[2]['transaction_type_id'];
        //rate and times 12
        $rate = request()->rate;
        $rateX12 = $rate * 12;

        // present interest 
        $amount = request()->amount;
        $investmentstartdate = request()->investment_start_date;
        $startdateenddate =  date("Y-m-t", strtotime(request()->investment_start_date));
        $date1=date_create($investmentstartdate);
        $date2=date_create($startdateenddate);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff->days;
        $month = date("m",strtotime($investmentstartdate));
        $year = date("Y",strtotime($investmentstartdate));
        $daysinamonthone = cal_days_in_month(CAL_GREGORIAN,$month,$year);
        
        $precalc = ($actualtenor * $rate) / $daysinamonthone;
        $firstinterest = round($precalc * $amount, 2);

        $successiveinterest =  ($amt/100) * $rate;
        //data
        $result = [
            'amount' => number_format(request()->amount),
            'rate' => $rate,
            'per_annum' => $rateX12,
            'mat_date' => date('F j, Y',strtotime($savings_account['custom_field_1176'])),
            'duration' => request()->duration." months",
            'firstinterest' => number_format($firstinterest),
            'totaldeposit' => number_format($amt),
            'investmentdate' => date('F j, Y',strtotime($investmentstartdate)),
            'investmentenddate' => date('F t, Y',strtotime($investmentstartdate)),
            'successiveinterest' => number_format($successiveinterest),
            'firstdate' => date('F j, Y',strtotime($fd))
        ];
        // return $result;

        $pdf = PDF::loadView('deposit_investment_terms', $result);
  
        $pdf->setPaper('A4','portrait');
        return $pdf->download('deposit_investment_terms.pdf');
    }
    public function generatePDF2(){
        date_default_timezone_set('Africa/Lagos');
        $new_array = [];
        $url = "https://api-main.loandisk.com/3546/4110/saving/".request()->savings_id;
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
        
        $savings_account = $data['response']['Results']['0'];
        // $savings_account = ['title'=>'tosin'];

        // return $savings_account;
        $url = "https://api-main.loandisk.com/3546/4110/saving_transaction/saving/".request()->savings_id."/from/1/count/50";
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
        $txndata = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        // $new_array['savings_account'] = $savings_account;
        // $new_array['transaction_account'] = [];
        $pre_data = $txndata['response']['Results']['0'];
        
        foreach($pre_data as $txn){
            if($txn['transaction_type_id'] == 1 || $txn['transaction_type_id'] == 9 || $txn['transaction_type_id'] == 14){
                array_push($new_array, $txn);
            }
        }
        $savings_account['acc_name'] = request()->name;
        // return $savings_account;
        $pdf = PDF::loadView('doc', compact('savings_account','new_array'));
  
        $pdf->setPaper('A4','portrait');

        return $pdf->download('doc.pdf');
    }
    public function generatePDF3(){

        $result = ['title'=>'i love this'];
        $pdf = PDF::loadView('generate_pdf', $result);
  
        return $pdf->download('generate_pdf.pdf');
    }
}
