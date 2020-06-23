<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Investmentstart;
use PDF;

class PDFController extends Controller
{
    public function updatestage(){
        request()->validate([
            "id" => "required",
            "stage" => 'required'
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        $update->update(['stage'=> request()->stage]);
    }

    public function updatestatus(){
        request()->validate([
            "id" => "required"
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        $update->update(['status'=> 1]);
    }
    
    public function getnextinterest(){
        request()->validate([
            "id" => "required"
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        return $update->next_interest;
        // $update->update(['next_interest'=> $value]);
    }

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

        if($savings_account["savings_product_id"] == "717"){
            $rate = "3";
        }
 
        if($savings_account["savings_product_id"] == "2135"){
            $rate = "2.5";
        }
 
        if($savings_account["savings_product_id"] == "2157"){
            $rate = "2";
        }
        // return $savings_account['borrower_id'];
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
    
        // return $bemail;


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
        
        $firstdate = '';
        $i = 1;
        $ni = 0;
        $amt = 0;
    
        $month = date('m',strtotime(request()->investment_start_date)); 
        
        $ldate = date('t-m-Y',strtotime(request()->investment_start_date)); 
        
        $nextinterest = $this->getnextinterest();
        foreach($pre_data as $key){
            if($key['transaction_type_id'] == 1){
                $amt += $key['transaction_amount'];
                if($i == 1){
                    $firstdate = $key['transaction_date'];
                }
                $i++;
            }
            if($nextinterest === 0){
                if($key['transaction_type_id'] == 9){
                    $fmt = str_replace('/','-',$key['transaction_date']);
                    if (strtotime($fmt) == strtotime($ldate)){
                        $ni = $ni + $key['transaction_amount'];
                    }
                }
            }
            if($nextinterest > 0){
                if($key['transaction_type_id'] == 9){
                    $fmt = str_replace('/','-',$key['transaction_date']);
                    if (strtotime($fmt) == strtotime($ldate)){
                        $ni = $ni + $key['transaction_amount'];
                    }
                }
            }
        }
        
        $fd = str_replace('/', '-', $firstdate);
        // return $pre_data[2]['transaction_type_id'];
        //rate and times 12
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
        
        // $precalc = ($actualtenor * $rate) / $daysinamonthone;
        // $firstinterest = round($precalc * $amount, 2);

        $successiveinterest = $amt * ($rate/100);

        //data
        $result = [
            'amount' => number_format(request()->amount),
            'rate' => $rate,
            'per_annum' => $rateX12,
            'mat_date' => date('F j, Y',strtotime(str_replace('/','-',$savings_account['custom_field_1176']))),
            'duration' => request()->duration." months",
            'totaldeposit' => number_format($amt),
            'investmentdate' => date('F j, Y',strtotime($investmentstartdate)),
            'investmentenddate' => date('F t, Y',strtotime($investmentstartdate)),
            'successiveinterest' => number_format($successiveinterest),
            'firstdate' => date('F j, Y',strtotime($fd)),
            'fullname' => $fullname,
            'next_interest' => $ni,
            'investment_start_date' => request()->investment_start_date
        ];

        $pdf = PDF::loadView('deposit_investment_terms', $result);
  
        $pdf->setPaper('A4','portrait');
        $terms = date("YmdHis") . $this->randomString(20, true) . '.pdf';

        Storage::put('public/pdf/'.$terms, $pdf->output());

        // $file2url = 'http://localhost:8000'.Storage::url('public/pdf/invoice2.pdf');

        $termObj = [];
        $termObj['pdf'] = $terms;
        $termObj['bemail'] = $bemail; 
        $termObj['full_name'] = $fullname;
        return $termObj;
        // return 'storage/pdf/'.$terms;
        // return $pdf->download('deposit_investment_terms.pdf');
    }
    public function generatePDF2(){
        // $fileurl = 'http://localhost:8000'.Storage::url('public/pdf/invoice.pdf');
        // return $fileurl;
        if(!Investmentstart::where('id',request()->id)->where('stage',5)->exists()){
            return response(['message'=>'stage passed','status'=>'success'], 200);
        }
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

        $savings_account = $data['response']['Results']['0'];

        if($savings_account["savings_product_id"] == "717"){
            $irate = "3";
        }
 
        if($savings_account["savings_product_id"] == "2135"){
            $irate = "2.5";
        }
 
        if($savings_account["savings_product_id"] == "2157"){
            $irate = "2";
        }
        $savings_account['rate_per_annum'] = number_format($irate * 12,1);
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
        $pre_data = $txndata['response']['Results']['0'];
        
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

        $terms = $this->generatePDF();
        // return $terms;
        $fn = $terms['full_name'];
        $dtpdf = 'storage/pdf/'.$terms['pdf'];
        // creating pdf;
        $pdf = PDF::loadView('doc', compact('savings_account','testarray','fn'));
        $pdf->setPaper('A4','portrait');

        $investmentschedule = date("YmdHis") . $this->randomString(20, true) . '.pdf';
        Storage::put('public/pdf/'.$investmentschedule, $pdf->output());

        $inv = 'storage/pdf/'.$investmentschedule;
        // return $inv;
        
        $to = $terms['bemail'];
        $toname = $fn;
        $subject = "Deposit Investment";
        $replyto = 'support@creditwallet.ng';

        // $fileurl = 'http://localhost:8000'.Storage::url('public/pdf/invoice.pdf');

        $html = $this->investmentstartemail($fn, request()->amount, request()->investment_start_date);
        
        $array_data = array(
                'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
                'to'=> $toname.'<'.$to.'>',
                'subject'=> $subject,
                'html'=> $html,
                'h:Reply-To'=> $replyto,
                'attachment[1]' => curl_file_create($dtpdf, 'application/pdf', 'Deposit Terms.pdf'),
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
            $this->updatestage();
            $this->updatestatus();
            return $results;

    }
    public function generatePDFtest($savings_id){
        
        // $fileurl = 'http://localhost:8000'.Storage::url('public/pdf/invoice.pdf');
        // return $fileurl;
        date_default_timezone_set('Africa/Lagos');
        $new_array = [];
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
        
        $savings_account = $data['response']['Results']['0'];

        if($savings_account["savings_product_id"] == "717"){
            $irate = "3";
        }
 
        if($savings_account["savings_product_id"] == "2135"){
            $irate = "2.5";
        }
 
        if($savings_account["savings_product_id"] == "2157"){
            $irate = "2";
        }
        $savings_account['rate_per_annum'] = number_format($irate * 12,1);

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


        $url = "https://api-main.loandisk.com/3546/4110/saving_transaction/saving/".$savings_id."/from/1/count/50";
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
        // return $testarray;
        usort($testarray, function ($a, $b) {
            return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
        });

        // creating pdf;
        $pdf = PDF::loadView('doc', compact('savings_account','testarray','full_name'));
  
        $pdf->setPaper('A4','portrait');

        $investmentschedule = date("YmdHis") . $this->randomString(20, true) . '.pdf';

        Storage::put('public/pdf/'.$investmentschedule, $pdf->output());

        $fileurl = env('APP_URL').Storage::url('public/pdf/'.$investmentschedule);
        
        return response(['url'=>$fileurl, 'status'=>'success'], 200);
    }
    public function generatePDF3(){

        $result = ['title'=>'i love this'];
        $pdf = PDF::loadView('generate_pdf', $result);
  
        return $pdf->download('generate_pdf.pdf');
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
    public function TestMailWithAttachment($name, $email, $department) {

        $template = "
            <!DOCTYPE html>
            <html>
            <head>

            <title>
            Interest Payment
            </title>
        
            </head>
            <body>
            <p>Dear ". $name .",</p>
            <p>Your resource request was rejected. Kindly see the reason(s) below.</p>
            <p>Thank you for the cooperation.</p>
            <p>Kind regards,</p>
            <div style = 'font-size:11px'>
            <p><strong>".$department." Team</strong> <br/>
            <span style = 'color:gray' ><strong>Princeps Credit Systems Limited (aka Credit Wallet)</strong> <br/>Pentagon Plaza, 2<sup>nd</sup> Floor (Wing D),<br/>23 Opebi Rd, Ikeja, Lagos, Nigeria <br/>
            Email: <a href='mailto:finance@creditwallet.ng'> ".$email."</a> | Phone: 07085698828</span></p>
            </div>
        
            <p><img src='https://creditwallet.ng/signature.png' alt='signature' width='398' height='74' /></p>
            <p  style = 'color:white' >List-Unsubscribe: <mailto: finance@mail.creditwallet.ng?subject=unsubscribe>, <http://www.creditwallet.ng/unsubscribe.html></p>
            </body>
            <html>";

            return $template;
    }
    function investmentstartemail($firstname,$amount, $date, $link = "https://customers.creditwallet.ng"){
 
        $template = "<p>Dear ".ucwords(strtolower($firstname)).",</p>
        <p>We confirm receipt of the sum of N".number_format($amount,2)." on ".date('F j, Y',strtotime($date))." being your deposit investment with Princeps Credit Systems Limited.</p>
        <p>Attached are the terms and investment schedule.</p>
        <p>We sent an email earlier inviting you to register for online access to view your account status and download your statements.</p>
        <p>You can also use link below to complete your online access registration.</p>
        <p>".$link."</p>
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
}
