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
            return response(['data'=>$investment,'original'=>$rand], 200);
            // mailing user
            $array_data = array(
                'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
                'to'=> $investment->email,
                'subject'=> "Account created successfully",
                'html'=> "<ul><li>Username: ".$investment->username."</li><li>Password: user".$rand."</li></ul>",
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

    public function savingsDashboard()
    {

        $url = "https://api-main.loandisk.com/3546/4110/saving/borrower/1053836/from/1/count/50";
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
        foreach($savings_account as $value){
            $savings_id = $value['savings_id'];
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
            $results['saving_transaction'] = $pre_data;
            for($i = 0;$i < count($pre_data); $i++){
                if($pre_data[$i]['transaction_type_id'] === 1){
                    $results['total_investment'] = $i_amount += $pre_data[$i]['transaction_amount'];
                }
                if($pre_data[$i]['transaction_type_id'] === 9){
                    $results['total_interest_earned'] = $earn_amount += $pre_data[$i]['transaction_amount'];
                }
                if($pre_data[$i]['transaction_type_id'] === 14){
                    $trf_amount = $trf_amount += $pre_data[$i]['transaction_amount'];
                }
            }
            
        }
        $results['total_savings'] = $savings_account;
        $results['total_interest_recieving'] = $results['total_interest_earned'] - $trf_amount;
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

    public function deleteSavingsTransactionsOfOtherMonths($savings_id){
        date_default_timezone_set('Africa/Lagos');
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
        $results['saving_transactions'] = $pre_data;     
        
        $last_date = date('t-m-Y');
        // return $results;
        foreach($results['saving_transactions'] as $key){
            $new_tdate = str_replace('/', '-', $key['transaction_date']);
                if($key['transaction_type_id'] === 9 && $new_tdate < $last_date){
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
        date_default_timezone_set('Africa/Lagos');
        $time = date('h:i A');
        // return $time;
        $new_amount = $this->strtonum(request()->amount);
        $post = [
            'savings_id' => request()->savings_id,
            'transaction_date' => request()->investment_start_date,
            'transaction_time'   => $time,
            'transaction_type_id' => 1,
            'transaction_amount' => request()->amount,
            'transaction_description' => 'Additional Deposit of '.$new_amount
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
        $data = json_decode(curl_exec($curl), true); 
        $httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        return $data;
    }
    public function calculateThisMonthInterest(){
        request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'rate' => 'required|numeric'
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
        // return $daysinamonthone;
        $precalc = ($actualtenor * $rate) / $daysinamonthone;
        $interest = round($precalc * $amount, 2);
        // return $interest;
        
        $dated = str_replace('-', '/', $investmentstartdate);

        $newfmtdate = date('Y-m-t', strtotime($investmentstartdate));
        
        $txndate = str_replace('-', '/', $newfmtdate);
        // return $txndate;
        return request()->investment_start_date;
        $post = [
            'savings_id' => request()->savings_id,
            'transaction_date' => $dated,
            'transaction_time'   => date('h:i:s A'),
            'transaction_type_id' => 1,
            'transaction_amount' => $interest,
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
        return $sdata;
    }
    public function calculateForStageFour(){
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
        $savings_account = $data['response']['Results']['0'];
        
        // return $savings_account;
        $last_date = strtotime(date('t-m-Y'));
        $mat_date = strtotime(str_replace('/', '-', $savings_account['custom_field_1176']));
        
        
        $diff = abs($last_date - $mat_date); 
        $years = floor($diff / (365*60*60*24));  
        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
        
        if($months < 6){
            $newmonth = $months + request()->duration;
            $date = date('Y/m/d', strtotime('+'.$newmonth.' month'));
            $year = date('Y', strtotime($date));
            $month = date('m', strtotime($date));
            $startdate = date($year.'-'.$month.'-01');
            $new_date =  date($year.'-'.$month.'-t', strtotime($startdate));
            $new_mat_date = strtotime(str_replace('-', '/', $new_date));
            $dto = date('Y-m-d', $new_mat_date);
            $matdate = str_replace('-', '/', $dto);



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
        
        return $mdata;
    }
    public function calculateForStageFive(){
        date_default_timezone_set('Africa/Lagos');
        $td = request()->investment_start_date;
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
        

        $last_date = strtotime(str_replace('/', '-', request()->investment_start_date));
        $mat_date = strtotime(str_replace('/', '-', $savings_account['custom_field_1176']));
        
        $diff = abs($last_date - $mat_date); 
        $years = floor($diff / (365*60*60*24));  
        $months = floor(($diff - $years * 365*60*60*24) / (30*60*60*24)); 
        
        for($x=1; $x <= $months; $x++){
            $ttt = strtotime($td);
            
            $dt = date('Y-m-t', strtotime('+'.$x.' month',$ttt));
            
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
        
        return $sdata;
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
