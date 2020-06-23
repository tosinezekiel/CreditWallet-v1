<?php

namespace App\Http\Controllers;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Investment;
use App\Investmentstart;
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
        $new = Investmentstart::where('stage',0)->get();
        $completed = Investmentstart::where('stage',6)->get();
        $result = [];
        $result['new'] = $new;
        $result['completed'] = $completed;
        return response(['data'=>$result,'status'=>'success'], 200);
    }

    public function listInvestment(Request $request){
        request()->validate([
            'page_size' => 'required'
        ]);
        $query = Investmentstart::query();
        if(request()->filled('status')){
            if(request()->status !== null){
                $query->where('status',$request->status);
            }
        }
        if(!empty(request()->searchtext)){
            $query->where('firstname', 'LIKE', '%'.$request->searchtext.'%')
            ->orWhere('lastname', 'LIKE', '%'.$request->searchtext.'%')
            ->orWhere('savings_account_no', 'LIKE', '%'.$request->searchtext.'%');   
        }
        return $query->paginate(request()->page_size);
        // if($request->filled('from') && $request->filled('to')){
        //     // $this->validateList();
        //     $from = date($request->from);
        //     $begin = date("Y-m-d",strtotime($from . ' -1 day'));
        //     $to = date($request->to);
        //     $end = date("Y-m-d",strtotime($to . ' +1 day'));
        //     $query->whereBetween('created_at',[$begin,$end]);
        // }
       
    }    
    
    public function updatestage(){
        request()->validate([
            "id" => "required",
            "stage" => 'required'
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        $update->update(['stage'=> request()->stage]);
        

        // return response(['message'=>'stage successfully updated','status' => 'success'],200);
    }

    public function updatenextinterest($value){
        request()->validate([
            "id" => "required"
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        $update->update(['next_interest'=> $value]);
    }

    public function getnextinterest(){
        request()->validate([
            "id" => "required"
        ]);
        $update = Investmentstart::where('id',request()->id)->first();
        
        return $update->next_interest;
        // $update->update(['next_interest'=> $value]);
    }
    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function setinvitecode(){    
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
        if(isset($data['error'])){
            return response(['message'=> $data['error']['message'],'status'=>'error']);
        }

        if($data['http']["code"] == 200){
            $resultObj =  $data['response']['Results']; 
            $result = $resultObj[0]; 
            if(empty($result)){
                return response(['message'=> 'empty response','status'=>'error']);
            }
            $rand = $this->randomNumber(12);

            $array = [
                'username' => $result['borrower_unique_number'],
                'email' => $result['borrower_email'],
                'borrower_id' => $result['borrower_id'],
                'code' => $rand
            ];
            // return $array;
            
            if(Investment::whereEmail($result['borrower_email'])->exists()){
                return response(['first_login' => 1, 'status' => 'success'], 200);
                // return $results; 
            }
            //storing data
            $investment = Investment::create($array);
            return response(['code' => $rand, 'email'=> $result['borrower_email'], 'status' => 'success'], 200);
            // return $results; 
        }else{
            $response['status'] = "error";
            $response['data'] = $data;
            $response['message'] = "Something went wrong, please try again but if problem persist, please contact our customer support team on support@creditwallet.ng";
            echo json_encode($response);
        }
    }
    private function randomNumber($length) {
        $result = '';
    
        for($i = 0; $i < $length; $i++) {
            $result .= mt_rand(0, 9);
        }
    
        return $result;
    }
    public function create()
    {
        //retrieving from loan disk using email;     
        if(Investment::whereEmail(request()->email)->exists()){
            return response(['first_login' => 1, 'status' => 'success'], 200);
        }
        if(!Investment::whereEmail(request()->email)->whereCode(request()->code)->exists()){
            return response(['message' => 'customer not valid', 'status' => 'error'], 422);
        }
        //retrieving data
        $investment = Investment::whereEmail(request()->email)->first();
        $customClaims = $this->createCustomClaim($investment);
        $factory = JWTFactory::customClaims([
            'sub'   => env('APP_KEY'),
            'uuid' =>  $customClaims
        ]);
        $payload = $factory->make();
        $token = JWTAuth::encode($payload);

        return response(['first_login' => 0, 'status' => 'success', 'token' => "{$token}"], 200);
    }


    public function setPassword(){
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
        request()->validate([
            'password' => "required|confirmed"
        ]);
        // return $apy;
        if(!Investment::whereEmail($uuid->email)->exists()){
            return response(['message'=>'invalid credentials', 'status'=>'error'], 422);
        }

        $password = Hash::make(request()->password);

        $investor = Investment::whereEmail($uuid->email)->first();

        
        $investor->update(['password'=>$password]);
        
        return response(['message' => 'password set successfully','status'=>'success'], 200);
        
    }

    
    public function filterByParams(Request $request){
        // return "hi";
        $data = $this->filterResource($request);
        return response(['data' => $data,'status' => 'success'], 200); 
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
            'status' => 0
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
    

    public function deleteSavingsTransactionsOfOtherMonths(Request $request){
        if(!Investmentstart::where('id',request()->id)->where('stage',0)->exists()){
            return response(['message'=>'stage passed','status'=>'success'], 200);
        }
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
        // return $data;
        if(!empty($data['error'])){
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }
        if(empty($data)){
            return response(['status' => 'Error','message' => 'Bad Connection!'], 404); 
        }
        $savings_account = $data["response"]["Results"]["0"];
        if(empty($savings_account)){
            return response(['status' => 'Error','message' => 'result not found!'], 404);
        }
        
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
        $results['saving_transactions'] = $pre_data;     
        
        $investmentdate = str_replace('/','-',request()->investment_start_date);
        
        $startdeletingdate = date('d-m-Y',strtotime('+1 month',strtotime($investmentdate)));

        

        foreach($results['saving_transactions'] as $key){
            // return "wow";
            $new_tdate = str_replace('/', '-', $key['transaction_date']);

                // return $startdeletingdate. " --- " .$new_tdate;
                
                if($key['transaction_type_id'] === 9){
                    if(strtotime($new_tdate) > strtotime($startdeletingdate)){
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
            
        }
        $this->updatestage();
       return response(['message'=>'successfully Deleted', 'status'=>'succcess'], 200);
    }
    public function addtransaction(){
        request()->validate([
            'amount' => 'required|numeric',
            'investment_start_date' => 'date',
            'savings_id' => 'required|numeric',
            'id' => 'required'
        ]);
        if(!Investmentstart::where('id',request()->id)->where('stage',1)->exists()){
            return response(['message'=>'stage passed','status'=>'success'], 200);
        }
        date_default_timezone_set('Africa/Lagos');
        $time = date('h:i A');
        // return $time;
        $new_amount = number_format(request()->amount,2);
        $post = [
            'savings_id' => request()->savings_id,
            'transaction_date' => date('d/m/Y', strtotime( str_replace('/', '-',request()->investment_start_date))),
            'transaction_time'   => $time,
            'transaction_type_id' => 1,
            'transaction_amount' => request()->amount,
            'transaction_description' => 'Additional Deposit of '.$new_amount
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        // return $data;
        $this->updatestage();
        return response(['message'=>'added transaction successfully', 'status'=>'succcess'], 200);
    }

    public function calculateThisMonthInterest(){
        request()->validate([
            'amount' => 'required|numeric',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'savings_id' => 'required|numeric',
            'id' => 'required'
        ]);
        if(!Investmentstart::where('id',request()->id)->where('stage',2)->exists()){
            return response(['message'=>'stage passed','status'=>'success'], 200);
        }
        $history=array();
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
        $rate = $irate/100;
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
            $this->updatestage();
            $this->updatenextinterest($interest);
            return response(['next_interest'=> $interest, 'status'=>'success'], 200);
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
        $this->updatestage();
        return response(['next_interest'=> 0,'status'=>'success'], 200); 
    }
public function calculateForStageFour(){
    date_default_timezone_set('Africa/Lagos');
    request()->validate([
        'amount' => 'required|numeric',
        'duration' => 'required|integer|min:6',
        'investment_start_date' => 'date',
        'savings_id' => 'required|numeric',
        'id' => 'required'
    ]);
    if(!Investmentstart::where('id',request()->id)->where('stage',3)->exists()){
        return response(['message'=>'stage passed','status'=>'success'], 200);
    }
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
        $this->updatestage();
        return response(['status' => 'success'], 200);
    }
    public function calculateForStageFive(){
        date_default_timezone_set('Africa/Lagos');
        
        request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'savings_id' => 'required|numeric',
            'id' => 'required'
        ]);
        if(!Investmentstart::where('id',request()->id)->where('stage',4)->exists()){
            return response(['message'=>'stage passed','status'=>'success'], 200);
        }
        $investmentstartdate = request()->investment_start_date;
        $nextinterest = $this->getnextinterest();
        // return $td;
        // $ttt = strtotime(str_replace('/', '-', $td));
        // $tf = str_replace('-', '/', $td);
        // $dt = date('Y-m-t', strtotime('+1 month',$ttt));
        // return $tf;
        
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
            $irate = "3";
        }
 
        if($savings_account["savings_product_id"] == "2135"){
            $irate = "2.5";
        }
 
        if($savings_account["savings_product_id"] == "2157"){
            $irate = "2";
        }
        $rate = $irate/100;
    
        
        

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
        $startdate = date('d-m-Y', strtotime(request()->investment_start_date));
        
        $date1=date_create($startdate);
        $date2=date_create($mat_date);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff;

        $months = $datediff->m;
        if($months === 0){
            $months = $datediff->y * 12;
        }

        for($x=1; $x <= $months; $x++){
            if($x==1){
                $interest = $nextinterest + ($rate * $amt);
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
            
        }
        $this->updatestage();
       return response(['message'=>$data, 'status'=>'succcess'], 200);
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
        
        $m = date('m',strtotime(request()->investment_start_date));
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
    private function filterResource($request){

        $query = Investmentstart::where('stage',6);
        if(!$request->filled('per_page')){
            $per_page = 10;
        }
        $per_page = $request->page_size;
        if ($request->filled('stage')) {
            $query->where('stage', '=', $request->status);   
        }
        if($request->filled('from') && $request->filled('to')){
            // $this->validateList();
            $from = date($request->from);
            $begin = date("Y-m-d",strtotime($from . ' -1 day'));
            $to = date($request->to);
            $end = date("Y-m-d",strtotime($to . ' +1 day'));
            $query->whereBetween('created_at',[$begin,$end]);
        }
        return $query->paginate($per_page);
    }
}
