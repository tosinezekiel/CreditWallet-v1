<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PDF;

class InvestmentrolloverController extends Controller
{
    public function rollover(){
        // End Point that accept savings_id and duration and do the following

        // 1. Derive Rollover Start Date =  Maturity Date + 1 day, and rollover start month
        // 2. Update the maturity date using the investment merge technique with the duration
        // 3.  Derive the current interest rate for the investment
        // 4.  Add new transactions for the number of duration using the add transaction technique starting from the rollover month end date
        // 5. Generate Rollover Deposit Term and Investment Schedule PDF (Check Email for Rollover Word Doc)
        // 6. Send Attachment to Borrower

        request()->validate([
            'savings_id' => 'required',
            'duration' => 'required'
        ]);
        $data = $this->getSavingsAccount(request()->savings_id);
        $savings_account = $data;
    
        if($this->updatematuritydate($savings_account)){
            $irate = $this->getrate($savings_account);
            $totaldeposit = $this->getTotalDeposit();
            if(!$totaldeposit['status']){
                return response(['message'=>'something went wrong!','status'=>'error'], 422);
            }
            $rate = $irate/100;
            $interest = $rate * $totaldeposit['amount'];     
        }
        $currentmaturitydate = str_replace('/','-',$savings_account["custom_field_1176"]);
        $plusoneday = date('d-m-Y', strtotime($currentmaturitydate . ' +1 day'));
        $rolloverstartdate = date('01-m-Y', strtotime($plusoneday));
        // $rolloverdurationdate = date('t-m-Y', strtotime('+'.request()->duration.' month',strtotime($plusoneday)));
        for($x=0; $x < request()->duration; $x++){
            $ttt = strtotime($plusoneday);
        
            $dt = date('t-m-Y', strtotime('+'.$x.' month',$ttt));
            
            $txndate = str_replace('-', '/', $dt);
            $post = [
                'savings_id' => request()->savings_id,
                'transaction_date' => $txndate,
                'transaction_time'   => date('h:i:s A'),
                'transaction_type_id' => 9,
                'transaction_amount' => round($interest, 2),
                'transaction_description' => 'Interest Due on '.$txndate
            ];

            $url = "https://api-main.loandisk.com/3546/4110/saving_transaction";
            $curl = curl_init();
            curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => json_encode($post),
                CURLOPT_HTTPHEADER => array(
                    "accept: application/json",
                    "cache-control: no-cache",
                    "content-type: application/json",
                    "Authorization: Basic AkMbezWYERkE5NcDsXAM7YzkxDySG9amAKvajU9d"
                ),
            ));
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
            $sdata = json_decode(curl_exec($curl), true); 
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        }

        return response(['status'=>'success','rolloverstartdate'=>$plusoneday],200);

    }

    public function updatematuritydate($savings_account){
        $currentmaturitydate = str_replace('/','-',$savings_account["custom_field_1176"]);

        $plusoneday = date('d-m-Y', strtotime($currentmaturitydate . ' +1 day'));
        $plusonedaymonth = date('m', strtotime($plusoneday));
        $newmonth = request()->duration;
        $reduce = (int)$newmonth - 1;
        $date = date('Y/m/d', strtotime('+'.$reduce.' month',strtotime($plusoneday)));

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));
        
        $newmatenddate =  date($year.'-'.$month.'-t', strtotime($date));

        $fmt_newmatenddate = str_replace('-', '/', $newmatenddate);

        if(!array_key_exists("custom_field_4709", $savings_account)){
            $savings_account['custom_field_4709'] = 0;
        }
        $update =  [
            "savings_id" => $savings_account['savings_id'],
            "savings_product_id" => $savings_account['savings_product_id'],
            "borrower_id" => $savings_account['borrower_id'],
            "savings_account_number" => $savings_account['savings_account_number'],
            "savings_fees" => $savings_account['savings_fees'],
            "savings_description" => $savings_account['savings_description'],
            "savings_balance" => $savings_account['savings_balance'],
            "custom_field_1176" => $fmt_newmatenddate,
            "custom_field_4709" => $savings_account['custom_field_4709']
        ];
        
        $url = "https://api-main.loandisk.com/3546/4110/saving";
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "PUT",
        CURLOPT_POSTFIELDS => json_encode($update),
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
        if($data['http']['code']== 200){
            return true;
        }
    }

    private function getrate($savings_account){
        if($savings_account["savings_product_id"] == "717"){
            $irate = "3";
        }
 
        if($savings_account["savings_product_id"] == "2135"){
            $irate = "2.5";
        }
 
        if($savings_account["savings_product_id"] == "2157"){
            $irate = "2";
        }
        return $irate;
    }

    public function getTotalDeposit(){
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $status = '';
        if(!empty($data['error'])){
            $status = false;
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }

        if(empty($data)){
            $status = false;
            return response(['status' => 'Error','message' => 'Bad Connection!'], 404); 
        }

        $pre_data = $data['response']['Results']['0'];
        if($pre_data === null){
            $status = false;
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }
        $amount = 0;
        $i = 1;
        $firstdate = '';
        foreach($pre_data as $txn){
            if($txn['transaction_type_id'] === 1){
                if($i == 1){
                    $firstdate = $txn['transaction_date'];
                }
                $amount += $txn['transaction_amount'];
                $status = true;
                $i++;
            }
        }
        return ['status'=>$status,'amount'=>$amount, 'firstdate'=>$firstdate, 'transactions'=>$pre_data];
    }

    public function generateRolloverDepositTermsPDF($savings_account,$totaldeposit,$rolloverstartdate)
    {
        $rate = $this->getrate($savings_account);

        $url = "https://api-main.loandisk.com/3546/4110/borrower/".$savings_account['borrower_id'];
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
        $bordata = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $bbio = $bordata["response"]["Results"]["0"];

        $fullname = $bbio['borrower_firstname']." ".$bbio['borrower_lastname'];

        $bemail = $bbio['borrower_email'];
        
        $fd = str_replace('/', '-', $totaldeposit['firstdate']);
        
        $rateX12 = $rate * 12;
        
        $successiveinterest = $totaldeposit['amount'] * ($rate/100);

        //data
        $result = [
            'amount' => number_format($totaldeposit['amount']),
            'rate' => $rate,
            'per_annum' => $rateX12,
            'mat_date' => date('F j, Y',strtotime(str_replace('/','-',$savings_account['custom_field_1176']))),
            'duration' => request()->duration." months",
            'totaldeposit' => number_format($totaldeposit['amount']),
            'successiveinterest' => number_format($successiveinterest),
            'firstdate' => date('F j, Y',strtotime($fd)),
            'fullname' => $fullname,
            'rolloverstartdate' => date('F j, Y',strtotime($rolloverstartdate))
        ];

        
        $pdf = PDF::loadView('rollover_investment_terms', $result);
  
        $pdf->setPaper('A4','portrait');
        $terms = date("YmdHis") . $this->randomString(20, true) . '.pdf';

        Storage::put('public/pdf/'.$terms, $pdf->output());


        $termObj = [];
        $termObj['pdf'] = $terms;
        $termObj['bemail'] = $bemail; 
        $termObj['full_name'] = $fullname;
        return $termObj;
    }
    public function generateSchedulePDF(){
        date_default_timezone_set('Africa/Lagos');
        $new_array = [];
        $savings_account = $this->getSavingsAccount(request()->savings_id);

        $rolloverstartdate = request()->rolloverstartdate;

        $totaldeposit = $this->getTotalDeposit();

        $irate = $this->getrate($savings_account);
        $savings_account['rate_per_annum'] = number_format($irate * 12,1);
        // return $savings_account;

        $pre_data = $totaldeposit['transactions'];
        
        foreach($pre_data as $txn){
            if($txn['transaction_type_id'] == 1 || $txn['transaction_type_id'] == 9 || $txn['transaction_type_id'] == 14){
                array_push($new_array, $txn);
            }
        }
        
        $testarray = array_map(function($a){
            return [
                'transaction_id' => $a['transaction_id'],
                'savings_id' => $a['savings_id'],
                'transaction_date' => str_replace('/','-',$a['transaction_date']),
                'transaction_time' => $a['transaction_time'],
                'transaction_type_id' => $a['transaction_type_id'], 
                'transaction_amount' => $a['transaction_amount'], 
                'transaction_description' => $a['transaction_description'],
                'transaction_balance' => $a['transaction_balance']
            ];
        }, $new_array);

        usort($testarray, function ($a, $b) {
            return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
        });
        
        $terms = $this->generateRolloverDepositTermsPDF($savings_account,$totaldeposit,$rolloverstartdate);
        // return $terms;
        $fn = $terms['full_name'];
        $dtpdf = 'storage/pdf/'.$terms['pdf'];
        // creating pdf;
        $pdf = PDF::loadView('doc', compact('savings_account','testarray','fn'));
        $pdf->setPaper('A4','portrait');

        $investmentschedule = date("YmdHis") . $this->randomString(20, true) . '.pdf';
        Storage::put('public/pdf/'.$investmentschedule, $pdf->output());

        $inv = 'storage/pdf/'.$investmentschedule;
        
        $to = $terms['bemail'];
        $toname = $fn;
        $subject = "Rollover Deposit Terms";
        $replyto = 'support@creditwallet.ng';

        $html = $this->rolloveremail($fn);
        
        $array_data = array(
                'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
                'to'=> $toname.'<'.$to.'>',
                'subject'=> $subject,
                'html'=> $html,
                'h:Reply-To'=> $replyto,
                'attachment[1]' => curl_file_create($dtpdf, 'application/pdf', 'Rollover Deposit Terms.pdf'),
                'attachment[2]' => curl_file_create($inv, 'application/pdf', 'Investment Schedule.pdf')
        );
            $session = curl_init(env('MAILGUN_URL').'/messages');
            curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($session, CURLOPT_USERPWD, 'api:'.env('MAILGUN_KEY'));
            curl_setopt($session, CURLOPT_POST, true);
            curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
            curl_setopt($session, CURLOPT_HEADER, false);
            curl_setopt($session, CURLOPT_HTTPHEADER, array('Content-Type: multipart/form-data'));
            curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
            $response = curl_exec($session);
            curl_close($session);
            $results = json_decode($response, true);
            $results['status'] = "success";
            return $results;

    }

    public function randomString($length = 32, $numeric = false) {
 
        $random_string = "";
        while(strlen($random_string)<$length && $length > 0) {
            if($numeric === false) {
                $randnum = mt_rand(0,61);
                $random_string .= ($randnum < 10) ?
                    chr($randnum+48) : ($randnum < 36 ? 
                        chr($randnum+55) : $randnum+61);
            } else {
                $randnum = mt_rand(0,9);
                $random_string .= chr($randnum+48);
            }
        }
        return $random_string;
    }
    private function rolloveremail($firstname,$link = "https://customers.creditwallet.ng"){
 
        $template = "<p>Dear ".ucwords(strtolower($firstname)).",</p>
        
        <p>We write to confirm your deposit investment rollover with Princeps Credit Systems Limited  for another ".request()->duration." Months.</p>
        <p>Attached are the terms and investment schedule.</p>
        <p>Thank you for choosing Princeps Credit Systems Limited.</p>
        <p>Kind regards,</p>
        <div style = 'font-size:11px'>
        <p><strong>Finance Team</strong> <br/>
        <span style = 'color:gray' ><strong>Princeps Credit Systems Limited (aka Credit Wallet)</strong> <br/>Pentagon Plaza, 2<sup>nd</sup> Floor (Wing D),<br/>23 Opebi Rd, Ikeja, Lagos, Nigeria <br/>
        Email: <a href='mailto:finance@creditwallet.ng'> finance@creditwallet.ng</a> | Phone: 07085698828</span></p>
        </div>
     
        <p><img src='https://creditwallet.ng/signature.png' alt='signature' width='398' height='74' /></p>";
        
        return $template;
    }
    public function getSavingsAccount($savings_id){
        $url = "https://api-main.loandisk.com/3546/4110/saving/".$savings_id;
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
        $results = [];
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }
        if(empty($data)){
            return response(['status' => 'Error','message' => 'Bad Connection!'], 404); 
        }
        $savings_account = $data["response"]["Results"]["0"];
        if($savings_account === null){
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }
        return $savings_account;
        
    }
}
