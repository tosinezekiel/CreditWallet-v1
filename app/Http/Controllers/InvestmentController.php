<?php

namespace App\Http\Controllers;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Investment;
use Carbon\Carbon;
class InvestmentController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
    //retrieving from loan disk using email;
    $url = "https://api-main.loandisk.com/3546/4110/borrower/borrower_email/".request('email');
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
    $borrower_id = $savings_no = $uniquenumber = "";
    // return json_decode(['data']);
        if(isset($data['error'])){
            return response(['Message'=>'the specified email can not be found','Status' => 'error'], 404);
        }
        if($data['http']["code"] == 200){
            $resultObj =  $data['response']['Results']; 
            $result = $resultObj[0]; 
            // return $result;
            $rand = $this->getRandomString();
            $hashed_password = Hash::make($rand);

            $array = [
                'username' => $result['borrower_unique_number'],
                'password' => $hashed_password,
                'email' => $result['borrower_email'],
                'borrower_id' => $result['borrower_id'],
                'first_login' => 0
            ];
            //storing data
            $investment = Investment::create($array);
            // return response(['data'=>$investment,'original'=>$rand], 200);
            // mailing user
            $array_data = array(
                'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
                'to'=> $investment->email,
                'subject'=> "Account created successfully",
                'html'=> "<ul><li>Username: ".request()->email."</li><li>Password: ".$rand."</li></ul>",
                'h:Reply-To'=> $investment->email
                );
                $session = curl_init(env('MAILGUN_URL').'/messages');
                curl_setopt($session, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
                curl_setopt($session, CURLOPT_USERPWD, 'api:'.env('MAILGUN_KEY'));
                curl_setopt($session, CURLOPT_POST, true);
                curl_setopt($session, CURLOPT_POSTFIELDS, $array_data);
                curl_setopt($session, CURLOPT_HEADER, false);
                curl_setopt($session, CURLOPT_ENCODING, 'UTF-8');
                curl_setopt($session, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($session, CURLOPT_SSL_VERIFYPEER, false);
                $response = curl_exec($session);
                curl_close($session);
                $results = json_decode($response, true);
                $results['Status'] = "success";
                $results['Message'] = "Account Created Successfully";
                return $results; 
        }else{
            $response['status'] = "error";
            $response['data'] = $data;
            $response['message'] = "Something went wrong, please try again but if problem persist, please contact our customer support team on support@creditwallet.ng";
            echo json_encode($response);
        }
    // return $data; 
    }

    

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }
    public function savingsDashboard()
    {

        $notification = array(
            'message' => "Due to the high volume of deposit investments that we have received recently, please note that we are currently not accepting deposit investments at this point. However, the investment platform will be available from 30th June, 2020. Thank you for choosing Princeps Credit Systems Limited",
            'status' => 1
        );
        //check for token
        if(! $token = JWTAuth::getToken()){
            return response(['message' => 'unauthorized access', 'status'=>'error'], 401);
        }
        // check if expired
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token', 'status' => 'error'], 401);
        }
        // get tokens payload
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];

      
        // return $uuid->borrower_id;
        $url = "https://api-main.loandisk.com/3546/4110/saving/borrower/".$uuid->borrower_id."/from/1/count/50";
        // return $url;
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

        
        $savings_account = $data['response']['Results']['0'];

        
        $results['total_savings'] = $savings_account;
        $i_amount = 0;
        $earn_amount = 0;
        $trf_amount = 0;
        $gTrf_ammount = "";
        $maturity = [];
        foreach($savings_account as $value){
            $savings_id = $value['savings_id'];
            $maturity_date = date('d-m-Y', strtotime( str_replace('/', '-',$value["custom_field_1176"])));
            $date1 = date('d-m-Y');
            $date2 = $maturity_date;
            
            $ts1 = strtotime($date1);
            $ts2 = strtotime($date2);
            
            $year1 = date('Y', $ts1);
            $year2 = date('Y', $ts2);
            
            $month1 = date('m', $ts1);
            $month2 = date('m', $ts2);
            
            $diff = (($year2 - $year1) * 12) + ($month2 - $month1);
			$maturity[] = array(
			    'maturity_month' => $diff,
			    'savings_account' => $value
			);
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
            $data = json_decode(curl_exec($curl), true); 
            $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
            $pre_data = $data['response']['Results']['0'];
            $x = 1;
            $y = 1;
            $z = 1;
            $empty = [];
            
            $all_deposit = [];
            $all_last_interest = [];
            $all_next_interest = [];
  
            // $result['last_deposit'] =  [];
            $total_interest_recievable = $total_interest_earned = $last_interest = $next_interest = 0;
            foreach($pre_data as $value){
                
                $transfer_out_payment_date = date('28-m-Y');
                $last_day_of_month = date('t-m-Y');
                if($value['transaction_type_id'] === 1){
                    $results['total_investment'] = $i_amount += $value['transaction_amount'];
                    $results['last_deposit'] = $value['transaction_amount'];
                    $all_deposit[] = $value;
                }
                
                if($value['transaction_type_id'] === 9){
                   
                    
                    $transactiondate = date('d-m-Y', strtotime( str_replace('/', '-',$value["transaction_date"])));
			
        			if( strtotime($transactiondate) >= time() ){
        			    $total_interest_recievable = $total_interest_recievable + $value["transaction_amount"];
        				$all_next_interest[] = $value;
        			}
        			
        			
        			if(date("d") <= 28){
        			    if ($transactiondate == $last_day_of_month){
                            $next_interest = $next_interest + $value['transaction_amount'];
                        }
        			}else{
        			    $lastDateOfNextMonth =strtotime('last day of next month') ;

                        $last_next_month_day = date('d-m-Y', $lastDateOfNextMonth);
                        if ($transactiondate == $last_next_month_day){
                            $next_interest = $next_interest + $value['transaction_amount'];
                        }
        			}
        			
        			
                }
                
                if($value['transaction_type_id'] === 14){
                     
                    $transactiondate = date('d-m-Y', strtotime( str_replace('/', '-',$value["transaction_date"])));
			
        			if( strtotime($transactiondate) <= time() ){
        				$all_last_interest[] = $value;
        				$total_interest_earned = $total_interest_earned + $value["transaction_amount"];
        			}
        			
        			if(date("d") <= 28){
        			    $date = date('Y-m-d', strtotime('- 1 months'));
                    	$year = date('Y', strtotime($date));
                    	$month = date('m', strtotime($date));
                    	$last_interest_payment_date = date("28-".$month."-".$year);
        			    if ($transactiondate == $last_interest_payment_date){
                            $last_interest = $last_interest + $value['transaction_amount'];
                        }
        			}else{
        			    $last_interest_payment_date = date("28-m-Y");
        			    if ($transactiondate == $last_interest_payment_date){
                            $last_interest = $last_interest + $value['transaction_amount'];
                        }
        			}
                }
                
            }
            
        }
        
        $results['all_next_interest'] = $all_next_interest;
        $results['all_last_interest'] = $all_last_interest;
        $results['all_deposit'] = $all_deposit;
        $results['maturity'] = $maturity;
        $results['notification'] = $notification;
        $results['last_interest'] = $last_interest;
        $results['next_interest'] = $next_interest;
        $results['total_savings'] = $savings_account;
        $results['total_interest_recievable'] = $total_interest_recievable;
        $results['total_interest_earned'] = $total_interest_earned;
        $results['Status'] = "success";
        return $results;
    }

    public function savings($savings_id){
        $results = [];
        $data = $this->getSavingsAccount($savings_id);
       
        $results['savings'] = $data['response']['Results']['0'];
        
        $results['saving_transactions'] = $this->getSingleSavingsTransactions($savings_id);  

        return $results;
    }

    public function deleteSavingsTransactionsOfOtherMonths(){
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
        $results = [];
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'Savings not found!'], 404);
        }else{
            $savings_account = $data["response"]["Results"]["0"];
            if($savings_account === null){
                return response(['status' => 'Error','message' => 'Savings not found!'], 404);
            }
        }
    
        $savings_account = $data['response']['Results']['0'];
        // $results['savings'] = $savings_account;

        $savings_id = $savings_account['savings_id'];

        
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $pre_data = $data['response']['Results']['0'];
        // return $pre_data;
        $results['saving_transactions'] = $pre_data;     
        
        $investmentdate = str_replace('/','-',request()->investment_start_date);
        
        $startdeletingdate = date('d-m-Y',strtotime('+1 month',strtotime($investmentdate)));



        foreach($results['saving_transactions'] as $key){
            $new_tdate = str_replace('/', '-', $key['transaction_date']);

                if($key['transaction_type_id'] === 9 && strtotime($new_tdate) > strtotime($startdeletingdate)){
                    $url = "https://api-main.loandisk.com/3546/4110/saving_transaction/".$key['transaction_id'];
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
                }
            
        }
       return response(['message'=>'successfully Deleted', 'Status'=>'succcess'], 200);
    }
    public function addtransaction(){
        request()->validate([
            'amount' => 'required|numeric',
            'investment_start_date' => 'date',
            'savings_id' => 'required|numeric'
        ]);
        date_default_timezone_set('Africa/Lagos');
        $time = date('h:i A');
        // return $time;
        $new_amount = number_format(request()->amount,2);
        $post = [
            'savings_id' => request()->savings_id,
            'transaction_date' => str_replace('-','/',request()->investment_start_date),
            'transaction_time'   => $time,
            'transaction_type_id' => 1,
            'transaction_amount' => request()->amount,
            'transaction_description' => 'Additional Deposit of '.$new_amount
        ];
        $url = "https://api-main.loandisk.com/3546/4110/saving_transaction";

        // return $post;
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $results = [];
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }else{
            $result = $data["response"]["Results"]["0"];
            if($result === null){
                return response(['status' => 'Error','message' => 'result not found!'], 404);
            }
        }
        return $data;
    }

    public function calculateThisMonthInterest(){
        request()->validate([
            'amount' => 'required|numeric',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'rate' => 'required|numeric',
            'savings_id' => 'required|numeric'
        ]);
        $history=array();
        $rate = request()->rate/100;
        $amount = request()->amount;
        $investmentstartdate = date("Y-m-d",strtotime(request()->investment_start_date));
    
        $startdateenddate =  date("Y-m-t", strtotime(request()->investment_start_date));
        $date1=date_create($investmentstartdate);
        $date2=date_create($startdateenddate);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff->days;
        
        $month = date("m",strtotime($investmentstartdate));
        $year = date("Y",strtotime($investmentstartdate));
        $daysinamonthone = cal_days_in_month(CAL_GREGORIAN,$month,$year);
        $isday = (int) date("d",strtotime(request()->investment_start_date));
       
        if($isday > 24){
            $precalc = ($actualtenor * $rate) / $daysinamonthone;
            $interest = round($precalc * $amount, 2);
            return response(['next_interest'=> $interest]);
        }
        // return $daysinamonthone;
        $precalc = ($actualtenor * $rate) / $daysinamonthone;
        $interest = round($precalc * $amount, 2);
        // return $interest;
        
        $dated = str_replace('-', '/', date('t-m-Y',strtotime($investmentstartdate)));

        // return $dated;
        $newfmtdate = date('Y-m-t', strtotime($investmentstartdate));
        
        $txndate = str_replace('-', '/', $newfmtdate);
        // return $txndate;
       
        $post = [
            'savings_id' => request()->savings_id,
            'transaction_date' => $dated,
            'transaction_time'   => date('h:i:s A'),
            'transaction_type_id' => 9,
            'transaction_amount' => $interest,
            'transaction_description' => 'Interest Due on '.$dated.' for additional '.number_format(request()->amount, 2)
        ];
        // return $post;
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
        // return $sdata;
        return response(['next_interest'=> 0]); 
    }
public function calculateForStageFour(){
    date_default_timezone_set('Africa/Lagos');
    request()->validate([
        'amount' => 'required|numeric',
        'duration' => 'required|integer|min:6',
        'investment_start_date' => 'date',
        'rate' => 'required|numeric',
        'savings_id' => 'required|numeric'
    ]);
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
    $results = [];
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'Savings not found!'], 404);
        }else{
            $savings_account = $data["response"]["Results"]["0"];
            if($savings_account === null){
                return response(['status' => 'Error','message' => 'Savings not found!'], 404);
            }
        }
    $savings_account = $data['response']['Results']['0'];
    
    
    $last_date = strtotime(date('t-m-Y',strtotime(request()->investment_start_date)));

    // return $last_date;
    // $last_date = strtotime(date('t-m-Y'));
    $mat_date = strtotime(str_replace('/', '-', $savings_account['custom_field_1176']));
    
    
    $diff = abs($last_date - $mat_date); 
    $years = floor($diff / (365*60*60*24));  
    $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
    // return $months;
    if($months < 6){
        $newmonth = $months + request()->duration;
        // return $newmonth;
        strtotime(request()->investment_start_date);
        $date = date('Y/m/d', strtotime('+'.$newmonth.' month',strtotime(request()->investment_start_date)));

        $year = date('Y', strtotime($date));
        $month = date('m', strtotime($date));

        // return $year."-".$month;
        $startdate = date($year.'-'.$month.'-01');
        $new_date =  date($year.'-'.$month.'-t', strtotime($startdate));
        $new_mat_date = strtotime(str_replace('-', '/', $new_date));
        $dto = date('d-m-Y', $new_mat_date);
        $matdate = str_replace('-', '/', $dto);
        // return $matdate;
        $update =  [
            "savings_id" => $savings_account['savings_id'],
            "savings_product_id" => $savings_account['savings_product_id'],
            "borrower_id" => $savings_account['borrower_id'],
            "savings_account_number" => $savings_account['savings_account_number'],
            "savings_fees" => $savings_account['savings_fees'],
            "savings_description" => $savings_account['savings_description'],
            "savings_balance" => $savings_account['savings_balance'],
            "custom_field_1176" => $matdate,
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
        $mdata = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        }
        
        return ['Status' => 'Success'];
    }
    public function calculateForStageFive(){
        date_default_timezone_set('Africa/Lagos');
        request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'rate' => 'required|numeric',
            'savings_id' => 'required|numeric',
            'current_interest' => 'required|numeric'
        ]);
        $investmentstartdate = request()->investment_start_date;
        // return $td;
        // $ttt = strtotime(str_replace('/', '-', $td));
        // $tf = str_replace('-', '/', $td);
        // $dt = date('Y-m-t', strtotime('+1 month',$ttt));
        // return $tf;
        $rate = request()->rate/100;
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
        $results = [];
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'Savings not found!'], 404);
        }else{
            $savings_account = $data["response"]["Results"]["0"];
            if($savings_account === null){
                return response(['status' => 'Error','message' => 'Savings not found!'], 404);
            }
        }
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $pre_data = $data['response']['Results']['0'];
        $results['saving_transactions'] = $pre_data; 
        $amt = 0;
        foreach($pre_data as $key){
            if($key['transaction_type_id'] == 1){
                $amt += $key['transaction_amount'];
            }
        }
        
        
        $interest = $rate * $amt;



        // return request()->current_interest."-".$interest."-".$amt;
        

        $last_date = strtotime(str_replace('/', '-', request()->investment_start_date));
        // $mat_date = strtotime(str_replace('/', '-', $savings_account['custom_field_1176']));
        $mat_date  = date('d-m-Y', strtotime( str_replace('/', '-',$savings_account["custom_field_1176"])));

        // return date('d/m/Y',$mat_date);
        // $diff = abs($last_date - $mat_date); 
        // $years = floor($diff / (365*60*60*24));  
        // $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
        

        // return $months;
        $startdate = date('d-m-Y', strtotime(request()->investment_start_date));
        
        $date1=date_create($startdate);
        $date2=date_create($mat_date);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff;

        $months = $datediff->m;
        if($months === 0){
            $months = $datediff->y * 12;
        }
        // return $months;
        
        for($x=1; $x <= $months; $x++){
            if($x==1){
                $interest = request()->current_interest + ($rate * $amt);
            }
            $ttt = strtotime($investmentstartdate);
            
            $dt = date('t-m-Y', strtotime('+'.$x.' month',$ttt));
            
            $txndate = str_replace('-', '/', $dt);

        //     return $txndate;
        // break;
            
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

            // return $sdata;
            // sleep(30);
        }
        
        return $data;
    }
    public function singleSavings($savings_id){
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
        $savings_account = $data['response']['Results']['0'];
        $results['savings'] = $savings_account;
        $savings_transactions = $this->getSingleSavingsTransactions($savings_id);
        
        usort($savings_transactions, function ($a, $b) {
            return strtotime(str_replace('/','-',$a['transaction_date'])) - strtotime(str_replace('/','-',$b['transaction_date']));
        });
        $fmttxn = array_map(function($a){
            return [
                'date' => str_replace('-','/',$a['transaction_date'])." ".$a['transaction_time'],
                'transaction' => $a['transaction_type_id'],
                'description' => $a['transaction_description'], 
                'debit' => ($a['transaction_type_id'] === 14) ? $a['transaction_amount'] : '',
                'credit' => ($a['transaction_type_id'] === 1 || $a['transaction_type_id'] === 9) ? $a['transaction_amount'] : '',
                'balance' => $a['transaction_balance']
            ];
        }, $savings_transactions);
        // return $results;
        $results['saving_transactions'] = $fmttxn;

        return $results;
    }
    public function changePassword(){
        request()->validate([
            'oldpassword' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);
        // get tokens payload
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];

        if(!Hash::check(request()->oldpassword, $uuid->password)){
            return response(['message' => 'current password is invalid or wrongly typed', 'status'=>'error'], 401);
        }
        $hashedpassword = Hash::make(request()->password);
        $investment = Investment::whereEmail($uuid->email)->wherePassword($uuid->password)->first();

        $investment->update(['password'=>$hashedpassword]);

        $customClaims = $this->createCustomClaim($investment);

        $factory = JWTFactory::customClaims([
            'sub'   => env('APP_KEY'),
            'uuid' =>  $customClaims
        ]);

        $payload = $factory->make();
        $token = JWTAuth::encode($payload);

        return response(['message' => 'password updated successfully', 'status' => 'success', 'token' => "{$token}"], 200);

    }

    private function createCustomClaim($data){
        date_default_timezone_set('Africa/Lagos');
        $now = Carbon::now();

        $customClaims = $data;
        $customClaims['now'] = $now->format('Y-m-d H:i:s');
        $customClaims['expiry'] = $now->addHour(6)->format('Y-m-d H:i:s');
        return $customClaims;
    }
    public function merge($savings_id){
        $this->validateAmountAndDuration();
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
        $savings_account = $data['response']['Results']['0'];
        $results['savings'] = $savings_account;
        // return $results;
        //get months between
        $last_date = strtotime(date('t-m-Y'));
        $mat_date = strtotime(str_replace('/', '-', $results['savings']['custom_field_1176']));
        
        $diff = abs($last_date - $mat_date); 
        $years = floor($diff / (365*60*60*24));  
  
        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24));  
        
        
        $deposits['total_funds'] = $total_amount + request()->amount;

        return $deposits;
        
    }
    public function passwordChange(Request $request)
    {
        if(! $token = JWTAuth::getToken()){
            return response(['message' => 'unauthorized access', 'status'=>'error'], 401);
        }
        // check if expired
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token', 'status' => 'error'], 401);
        }
        // get tokens payload
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
        
        //check matching record in database
        if(!Investment::where('username', $uuid->username)->where('password',$uuid->password)->exists()){
            return response(['message' => 'unauthenticated', 'status'=>'error'], 401);
        }
        $investment = Investment::where('username', $uuid->username)->where('password',$uuid->password)->first();
        $this->validatePasswordRequest();
        if(!Hash::check(request()->password, $investment->password)){
            return response(['message' => 'incorrect password', 'status'=>'error'], 401);
        }
        $new_password = Hash::make(request()->new_password);
        $investment->update(['password'=>$new_password]);

        $customClaims = $this->createCustomClaims($investment);
        $factory = JWTFactory::customClaims([
            'sub'   => env('APP_KEY'),
            'uuid' =>  $customClaims
        ]);
        $payload = $factory->make();
        $token = JWTAuth::encode($payload);
        // return response(['token'=> "{$token}"], 200);
        return response(['message' => 'password changed successfully', 'status'=>'success', 'token'=>"{$token}"], 200);
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    private function getSavingsAccount($savings_id){
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
        return $data;
    }
    private function getSingleSavingsTransactions($savings_id){
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        $pre_data = $data['response']['Results']['0'];
        return $pre_data;
    }

    public function getSingleSavingsTransactionsTest(){
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
        $pre_data = $data['response']['Results']['0'];
        
        $m = date('m',strtotime(request()->investment_start_date));
        // return $m;
        $results = [];
        foreach($pre_data as $txn){
            $fmtdate = str_replace('/','-',$txn['transaction_date']);
            $txnmonth = date('m',strtotime($fmtdate));
            if($txn['transaction_type_id'] === 9 && $txnmonth === $m){
                array_push($results,$txn);
            }
        }
        return $results;
    }
    public function edit($id)
    {
        //
    }
    private function strtonum($string)
    {
        $units = [
            'M' => '1000000',
            'K' => '1000',
        ];
    
        $unit = substr($string, -1);
    
        if (!array_key_exists($unit, $units)) {
            return 'ERROR!';
        }
    
        return (int) $string * $units[$unit];
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    private function validatePasswordRequest(){
        return request()->validate([
            'password' => 'required|min:6',
            'new_password' => 'required|min:6|confirmed'
        ]);
    }
    private function validateAmountAndDuration(){
        return request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6'
        ]);
    }

    private function createCustomClaims($request){
        date_default_timezone_set('Africa/Lagos');
        $now = Carbon::now();

        $customClaims = $request->all();
        $customClaims['now'] = $now->format('Y-m-d H:i:s');
        $customClaims['expiry'] = $now->addHour(6)->format('Y-m-d H:i:s');
        return $customClaims;
    }
    private function getRandomString() { 
        $n=10; 
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
        $randomString = ''; 
    
        for ($i = 0; $i < $n; $i++) { 
            $index = rand(0, strlen($characters) - 1); 
            $randomString .= $characters[$index]; 
        } 
    
        return $randomString; 
    } 
}
