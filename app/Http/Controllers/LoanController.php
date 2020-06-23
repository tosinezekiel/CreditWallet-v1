<?php

namespace App\Http\Controllers;

use App\Loanapproval;
use App\Loan;
use App\Loancomment;
use Illuminate\Http\Request;
use App\Rspidlog;
use App\Loanlog;
use App\Markrefcode;
use App\Organizationcodes;
use App\Verificationmay2020;
use DateTime;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

class LoanController extends Controller
{
  public function checkmethod(){
      $length = $this->customCheckString(request()->name);
      return $length;
  }
    public function calculateRepayment(Request $request){
        request()->validate([
            'amount' => 'required|numeric',
            'tenor' => 'required|numeric'
        ]);
        $result = [];
        // $request = json_decode(request()->getBoalldy());
        $repaymentplan  = array();
        $startdate = date("Y-m-d");
        $expectedenddate = $actualtenor = $interestperday = $insurance = $disbursementfees = $interest = $monthlyrepayment = $amount =  0;
        $date = date('Y-m-d', strtotime('+'.$request->tenor.' months'));
        $endyear = date('Y', strtotime($date));
        $endmonth = date('m', strtotime($date));
        $startdate = date("d-m-Y");
        $expectedenddate = date('d-m-Y', strtotime($this->last_day($endmonth, $endyear). ' + 10 days'));
        // return $this->last_day($endmonth, $endyear);
        $date1=date_create($startdate);
        $date2=date_create($expectedenddate);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff->days;
        $interestperday = env('INTEREST_PER_DAY') * $actualtenor;
     
        $insurance = env('INSURANCE') * $request->amount;
     
        $disbursementfees = env('DISBURSEMENT_FEE');
     
        $interest = ($interestperday * $request->amount);
     
        $monthlyrepayment = ($interest + $request->amount + $insurance + $disbursementfees) / $request->tenor;
        
     
        $expectedenddate = date("Y-m-d", strtotime($expectedenddate));
        $startdate = date("Y-m-d", strtotime($startdate));
        $result['status'] = "success";
        $result['monthlyrepayment'] = $monthlyrepayment;
        $result['expectedenddate'] = $expectedenddate;
        $result['actualtenor'] = $actualtenor;
        $result['startdate'] = $startdate;
        // echo json_encode($response);
        return response(['data'=> $result,'status'=>'success']);
    }

    public function apply(Request $request){
        $referralcode = Markrefcode::where('code',$request->refferalcode)->first();
        if(empty($referralcode)){
            $marketer = $referralcode["marketer"];
        }else{
            $marketer = "Credit Wallet";
        }
        $dob = date("d/m/Y", strtotime($request->dob));  
        $giroreference = date("YmdHis").rand(111111,999999).rand(111111,999999).rand(111111,999999);

        $insert_fields = array('title'=>$request->title, 'contactphone'=>$request->telephone, 'gender'=>$request->gender, 
        'telephone'=>$request->telephone, 'firstname'=>$request->firstname, 'lastname'=>$request->lastname, 'email'=>$request->email, 
        'house_address'=>$request->house_address, 'city'=>$request->city, 'state'=>$request->state, 
        'place_of_work'=>$request->place_of_work, 'loan_amount'=>$request->loan_amount,'tenor'=>$request->tenor, 
        'salary_bank_name'=>$request->salary_bank_name, 'salary_bank_account'=>$request->salary_bank_account, 
        'ippisnumber'=>trim($request->ippisnumber), 'created_at'=>date("Y-m-d H:i:s"), 'updated_at'=>date("Y-m-d H:i:s"),'monthly_repayment'=>$request->monthly_repayment,
        'dob'=>$dob,'giroreference'=>$giroreference,'marketer'=>$marketer);
        
        $initiate = Loan::create($insert_fields);
        
        $insert_fields = array('loan_id'=>$initiate->id);
      
        $initiate = Loanapproval::create($insert_fields);
        $ippisnumber = trim($request->ippisnumber);
        
        $records = Loanlog::where('ippisnumber',$ippisnumber)->count();

        if($records == 0){
            $records = Rspidlog::where('rsplinkedphonenumber',$request->telephone)->count();
            if($records == 0){
                return response(['status' => 'success', 'message' => 'Loan application form successfully submitted!','id'=>$initiate->id], 200);
            }else{
                $this->sendNewLoanApplicationEmail($request->email,$request->firstname);
                $this->AlertEmail($request->firstname." ".$request->lastname,$request->ippisnumber,$request->place_of_work,$request->loan_amount);
                return response(['status' => 'success', 'message' => 'Loan application form successfully submitted!','id'=>$initiate->id, 'returnstatus' => false], 200);
            }
        }else{
            $this->sendNewLoanApplicationEmail($request->email,$request->firstname);
            return response(['status' => 'success', 'message' => 'Loan application form successfully submitted!','id'=>$initiate->id, 'returnstatus' => false], 200);
        }
    }

    public function editloanapplication(){
        request()->validate([
          'id' => 'required',
          'loanid' => 'required'
        ]);

        $arr = [
          'title' => request()->title,
          'gender' => request()->gender,
          'firstname' => request()->firstname,
          'lastname' => request()->lastname,
          'email' => request()->email,
          'telephone' => request()->telephone,
          'ippisnumber' => request()->ippisnumber,
          'house_address' => request()->house_address,
          'city' => request()->city,
          'state' => request()->state,
          'loanid' => request()->loanid,
          'date_of_disbursement' => request()->date_of_disbursement,
          'salary_bank_name' => request()->salary_bank_name,
          'salary_bank_account' => request()->salary_bank_account,
          'prefered_bankname' => request()->prefered_bankname,
          'prefered_accountname' => request()->prefered_accountname
        ];

        $loan = Loan::where('id',request()->id)->first();
        if(!$loan->update($arr)){
          return response(['status'=>'error','message'=>'something went wrong'], 422);
        }
        return response(['status'=>'success','message'=>'load record updated succesfully'], 200);
    }

    public function index(){
      request()->validate([
          'page_size' => 'required'
      ]);
      $query = Loan::query();
      $total_record = $query->count();
      $total_sum = $query->sum('loan_amount');
      if(request()->filled('status')){
          if(request()->status !== null){
              $query->where('status',request()->status);
          }
      }
      if(!empty(request()->searchtext)){
          $query->where('firstname', 'LIKE', '%'.request()->searchtext.'%')
          ->orWhere('lastname', 'LIKE', '%'.request()->searchtext.'%')
          ->orWhere('email', 'LIKE', '%'.request()->searchtext.'%')
          ->orWhere('telephone', 'LIKE', '%'.request()->searchtext.'%')
          ->orWhere('loanid', 'LIKE', '%'.request()->searchtext.'%')
          ->orWhere('ippisnumber', 'LIKE', '%'.request()->searchtext.'%');   
      }
      $results = $query->paginate(request()->page_size);
      return response(['data'=>$results,'total_count'=>$total_record, 'total_sum'=> $total_sum], 200); 
    }

    public function cancel(){
      request()->validate([
        'id' => 'required',
        'reason' => 'required'
      ]);
      $loan = Loan::where('id', request()->id)->first();
      $loan->update(['status'=>8,'reason'=>request()->reason]);
    }

    public function reject(){
        request()->validate([
          'id' => 'required',
          'reason' => 'required'
        ]);
        $loan = Loan::where('id', request()->id)->first();
        $loan->update(['status'=>1,'reason'=>request()->reason]);

        $to = "alert@creditwallet.ng";
        $subject = 'Loan Rejected';
        $from = 'Credit Wallet <support@creditwallet.ng>';

        $message = '<html>
        <body style = "background-color: #f2f2f2">
            <div style = "background-color: white; border-radius: 5px; border-top:4px solid #f0ad4e;display: block;margin-top:20px; padding : 30px;">
                <h1 text-align: center;"><strong><span style = "color:#1B4E63">Credit</span><span style = "color:#f0ad4e">Wallet</span></strong></h1>
                <p>Dear '.$loan->firstname.',</p>
                <p>Thank you for your recent application. </p>
 
                <p>We regret to inform you that we are unable to approve your application at this juncture as our system has not given us the "green light" to proceed.</p>
 
                <p>Our system uses score based algorithms to approve or disapprove loan applications.</p>
 
                <p>Some possible reasons for the rejection include:
 
                        <ul>
                        <li>Employer not on our approved list. (We only lend to Federal Government workers or other workers whose salaries are processed via Remita platform)</li>
                        <li>Insufficient net income that meets our criteria</li>
                        <li>Insufficient or wrong information provided in the loan application</li>
                        <li>Identity verification issues</li>
                        </ul>
 
                </p>
            <p>Regards,</p>
            <p><strong>Credit Wallet Team</strong></p>
 
            
            </div>
        
        </body>
    </html>';
        $array_data = array(
          'from'=> $from,
          'to'=> $to,
          'subject'=> $subject,
          'html'=> $message,
          'h:Reply-To'=> $from
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
          $results['status'] = "success";
          return $results;
    }

    public function generateofferletter(){
      $response = array();
      request()->validate([
        'id' => 'required'
      ]);
      $id = request()->id;
      $loan = Loan::where('id',request()->id)->first();

        if($loan) {
            if($loan->status == "3"){
                return response(['status'=>'error','message'=>'You cannot generate offer letter for this loan, please contact the operation team'], 422);
            }

            if($loan->status == "10"){
                return response(['status'=>'error','message'=>'You cannot generate offer letter for this loan, please contact the operation team'], 422);
            }

            if($loan->status == "10"){
                return response(['status'=>'error','message'=>'You cannot generate offer letter for this loan, please contact the operation team'], 422);

            }
            $place_of_work = trim($loan->place_of_work);
            // $organizationalcode = $db->getOneRecord("SELECT * FROM organizationalcodes WHERE mdaname = '$place_of_work'");
            $organizationalcode = Organizationcodes::where('mdaname',$place_of_work)->first();
            $showorganizationalcode = true;
            if(!$organizationalcode){
                return response(['status'=>'error','message'=>'MDA name does not have organization code attached to it!'], 404);
            }
            // return $loan; 
            $newloan = $loan;
            return $newloan;
            $loan = $loan;
            if($newloan->ippisnumber == "null"){
                $existingMDA = Verificationmay2020::where()->first('organization',$place_of_work)->first();
                if($existingMDA){
                    return response(['status'=>'error','message'=>'Customer Place  of Work exist on the IPPIS Platform!'], 400);
                }
                $existingrecord = Rspidlog::all();
                $existingippisnumbers = array();
                foreach ($existingrecord as $value) {
                    $existingippisnumber = $value->ippisnumber;
                    if(startsWith($existingippisnumber,"R")){
                        $existingippisnumbers[] = ltrim($existingippisnumber, 'R');
                    }   
                }
                $existingippisnumbers = end($existingippisnumbers);
                $newippisnumber = "R00".($existingippisnumbers + 1);
                // $update_fields = array('ippisnumber'=>$newippisnumber);
                // $condition = array('id'=>$id);

                $updateippisnumber = Loan::where('id',$id)->first();
                $updateippisnumber->update(['ippisnumber'=>$newippisnumber]);

                $insert_fields = array('ippisnumber'=>$newippisnumber, 'rsplinkedphonenumber'=>$loan->telephone, 'firstname'=>$loan->firstname, 'lastname'=>$loan->lastname);
                // $db_fields = array('ippisnumber','rsplinkedphonenumber','firstname','lastname');
                // $initiate = $db->insertIntoTable($insert_fields, $db_fields, 'rspidlog');
                $initiate = Rspidlog::create($insert_fields);

            }
            
            if(empty($newloan->ippisnumber)){
                /*$response['status'] = "error";
                $response['message'] = "No IPPIS Number in this loan record. Please provide a unique IPPIS for this loan application!";
                echo json_encode($response);
                  exit();*/
                $existingMDA = Verificationmay2020::where('organization',$place_of_work)->first();
                if($existingMDA){
                    return response(['status'=>'error','message'=>'Customer Place  of Work exist on the IPPIS Platform!'], 400);
                }

                  $existingrecord = Rspidlog::all();
                  $existingippisnumbers = array();
                  foreach ($existingrecord as $value) {
                      $existingippisnumber = $value->ippisnumber;
                      if(startsWith($existingippisnumber,"R")){
                          $existingippisnumbers[] = ltrim($existingippisnumber, 'R');
                      } 
                  }
                  $existingippisnumbers = end($existingippisnumbers);
                  $newippisnumber = "R00".($existingippisnumbers + 1);
                  
                  $updateippisnumber = Loan::where('id',$id)->first();
                  $updateippisnumber->update(['ippisnumber'=>$newippisnumber]);

                  $insert_fields = array('ippisnumber'=>$newippisnumber, 'rsplinkedphonenumber'=>$loan->telephone, 'firstname'=>$loan->firstname, 'lastname'=>$loan->lastname);
                  $initiate = Rspidlog::create($insert_fields);
            }
            $repaymentplan  = array();
            $startdate = date("Y-m-d");
            $expectedenddate = $actualtenor = $interestperday = $insurance = $disbursementfees = $interest = $monthlyrepayment = $amount =  0;
            $date = date('Y-m-d', strtotime('+'.request()->tenor.' months'));
            $endyear = date('Y', strtotime($date));
            $endmonth = date('m', strtotime($date));

            $startdate = date("d-m-Y");
            $expectedenddate = date('d-m-Y', strtotime($this->last_day($endmonth, $endyear). ' + 10 days'));
            $date1=date_create($startdate);
            $date2=date_create($expectedenddate);
            $datediff = date_diff($date1,$date2);
            $actualtenor = $datediff->days;
            $interestperday = 0.0025 * $actualtenor;
            $insurance = 0.03 * request()->amount;
            $disbursementfees = 1500;
            $interest = ($interestperday * request()->amount);
            $creditcode = rand(1111,9999);
            
            $monthlyrepayment = ($interest + request()->amount + $insurance + $disbursementfees) / request()->tenor;
            $update_fields = array('status'=>'2', 'offerletter'=>1, 'processingtime'=>date("Y-m-d H:i:s"), 'updated_at'=>date("Y-m-d H:i:s"),'tenor'=>request()->tenor,'monthly_repayment'=>round($monthlyrepayment,2),'place_of_work'=>request()->place_of_work,'loan_amount'=>request()->amount,'creditcode'=>$creditcode,'marketer'=>request()->marketer,'insurance'=>$insurance, 'disbursementfee'=>$disbursementfees);
            
            $updloan = Loan::where('id',$id)->first();
            $initiate = $updloan->update($update_fields);
            
            if(request()->loantype == "1"){
                $path = $this->generateRemita(request()->id);
                $response['remitaresult'] = $path;
                $message = $this->composeOfferLetterDD($loan->email,request()->amount,request()->tenor,request()->id,$creditcode,$loan->firstname,$loan->lastname,$path);
                $data = $this->sendmailbymailgun($loan->email,"Credit Wallet","Loan Approved",$message,"support@creditwallet.ng");

            }else{
                $message = $this->composeOfferLetter($loan->email,request()->amount,request()->tenor,request()->id,$creditcode,$loan->firstname,$loan->lastname);
                $data = $this->sendmailbymailgun($loan->email,"Credit Wallet","Loan Approved",$message,"support@creditwallet.ng");
            }
            
            return response(['status'=>'success','message'=>'Offer Letter generated!'], 200);
        }
        else{
            return response(['status'=>'error','message'=>'Loan already rejected or has been approved!'], 400);
        }

      
  }

  public function addcomment(){
      request()->validate([
        'id' => 'required',
        'comment' => 'required'
      ]);
      $apy = $this->getTokensPayload();
      $uuid = $apy['uuid'];

      $datecreated = date('Y-m-d H:i:s');
      $loan = Loan::where('id',request()->id)->first();
      $loancomment = Loancomment::create(['authid'=>$uuid->authid, 'comment'=> request()->comment, 'datecreated'=>$datecreated, 'loanid'=>$loan->id]);
      return response(['message'=>'comment added successfully','status'=>'success'], 200);
  }


  private function getTokensPayload(){
    $token = JWTAuth::getToken(); 
    return JWTAuth::getPayload($token);
}
  public function composeOfferLetter($email,$amount,$tenor,$id,$code,$firstname,$lastname) {
    
     
    // Compose a simple HTML email message
    $message = '<html>

    <head>
        <style media=\"all\" type=\"text/css\">
                .myButton {
    -moz-box-shadow:inset 0px 1px 0px 0px #cf866c;
    -webkit-box-shadow:inset 0px 1px 0px 0px #cf866c;
    box-shadow:inset 0px 1px 0px 0px #cf866c;
    background-color:#f46a29;
    -moz-border-radius:3px;
    -webkit-border-radius:3px;
    border-radius:3px;
    border:1px solid #f46a29;
    display:inline-block;
    cursor:pointer;
    color:#ffffff;
    font-family:Arial;
    font-size:14px;
    padding:15px 30px;
    text-decoration:none;
    text-shadow:0px 1px 0px #854629;
}
.myButton:hover {
    background-color:#0f45a2;
}
.myButton:active {
    position:relative;
    top:1px;
}

                </style>
    </head>
        <body style = "background-color: #f2f2f2">
            <div style = "background-color: white; border-radius: 5px; border-top:4px solid #f0ad4e;display: block;margin-top:20px; padding : 30px;">
                <h1 text-align: center;"><strong><span style = "color:#1B4E63">Credit</span><span style = "color:#f0ad4e">Wallet</span></strong></h1>
                <p>Dear '.$firstname.' '.$lastname.',</p>
                <p>Congratulations! Your Credit Wallet Application has been approved for the sum of '.number_format($amount,2).' payable over a period of '.$tenor.' months.</p>

                <p style ="color:red">Please note that the loan application process to disbursement is done electronically.</p>

                <p>Kindly use this Authorization Code - <strong>('.$code.')</strong> and follow the link below (click on the "Continue Application" button below) to view /accept the loan offer terms and conditions to complete the application process.</p>
                
                <p><a href = "https://apply.creditwallet.ng/#/offerletter/'.$id.'" class="myButton">Continue Application</a></p>
    
                <p>If you need any assistance, please call 07085698828. Also, Please note that you will not be contacted if there are significant discrepancies in the documents recieved</p>
                </ul>

                </p>
                <p>Regards,</p>
            <p><strong>Credit Wallet Team</strong></p>

            
            </div>
        
        </body>
    </html>';
    
    return $message;
        
}

  
public function composeOfferLetterDD($email,$amount,$tenor,$id,$code,$firstname,$lastname,$remitaLink) {
    
     
  // Compose a simple HTML email message
  $message = '<html>

  <head>
      <style media=\"all\" type=\"text/css\">
              .myButton {
  -moz-box-shadow:inset 0px 1px 0px 0px #cf866c;
  -webkit-box-shadow:inset 0px 1px 0px 0px #cf866c;
  box-shadow:inset 0px 1px 0px 0px #cf866c;
  background-color:#f46a29;
  -moz-border-radius:3px;
  -webkit-border-radius:3px;
  border-radius:3px;
  border:1px solid #f46a29;
  display:inline-block;
  cursor:pointer;
  color:#ffffff;
  font-family:Arial;
  font-size:14px;
  padding:15px 30px;
  text-decoration:none;
  text-shadow:0px 1px 0px #854629;
}
.myButton:hover {
  background-color:#0f45a2;
}
.myButton:active {
  position:relative;
  top:1px;
}

              </style>
  </head>
      <body style = "background-color: #f2f2f2">
          <div style = "background-color: white; border-radius: 5px; border-top:4px solid #f0ad4e;display: block;margin-top:20px; padding : 30px;">
              <h1 text-align: center;"><strong><span style = "color:#1B4E63">Credit</span><span style = "color:#f0ad4e">Wallet</span></strong></h1>
              <p>Dear '.$firstname.' '.$lastname.',</p>
              <p>Congratulations! Your Credit Wallet Application has been approved for the sum of '.number_format($amount,2).' payable over a period of '.$tenor.' months.</p>

              <p style ="color:red">Please note that the loan application process to disbursement is done electronically.</p>

              <p>Kindly use this Authorization Code - <strong>('.$code.')</strong> and follow the link below (click on the "Continue Application" button below) to view /accept the loan offer terms and conditions to complete the application process. Also,  click on "Download Direct Debit Form" to download the Remita Direct Debit and take to your nearest bank branch for activation</p>
              
              <p><a href = "https://apply.creditwallet.ng/#/offerletter/'.$id.'" class="myButton">Continue Application</a></p>

              <p><a href = "'.$remitaLink.'" class="myButton">Download Direct Debit Form</a></p>
              <p>If you need any assistance, please call 07085698828. Also, Please note that you will not be contacted if there are significant discrepancies in the documents recieved</p>
              </ul>

              </p>
              <p>Regards,</p>
          <p><strong>Credit Wallet Team</strong></p>

          
          </div>
      
      </body>
  </html>';
  
  return $message;
      
}
public function generateRemita($id){
  
 
  $row = Loan::where('id',$id)->first();
  $days = 730;
  
  $startDate = time();
  $duedate = date("Y-m-d h:i:s");
  $duedate2 = date("Y-m-d h:i:s", strtotime('+'.$days.' days', $startDate));
  $merchantId = "2878598615";
  $serviceTypeId = "2878576470";
  $api_key = "UFJJTkNFUFMxMjM0fFBSSU5DRVBT";
  $requestId = md5(date("Y-m-d h:i:sa"));
  $hash = hash('sha512', $merchantId.$serviceTypeId.$requestId.$row["monthly_repayment"].$api_key);
  $dt = new \DateTime($duedate);
  $startDate = $dt->format('d/m/Y');
  $dt2 = new \DateTime($duedate2);
  $endDate = $dt2->format('d/m/Y');
  $parameter = array(
        "merchantId" => $merchantId,
        "serviceTypeId" => $serviceTypeId,
        "hash" => $hash,
        "payerName" => $row->firstname." ".$row->lastname,
        "payerPhone" => $row->telephone,
        "payerEmail" => $row->email,
        "payerBankCode" => $row->salary_bank_name,
        "payerAccount" => $row->salary_bank_account,
        "requestId" => $requestId,
        "amount" => $row->monthly_repayment,
        "startDate" => $startDate,
        "endDate" => $endDate,
        "mandateType" => "DD",
        "maxNoOfDebits" => "10"
  );

  $requestIdfinal = $requestId;

  $curl = curl_init();
  $url = "https://login.remita.net/remita/exapp/api/v1/send/api/echannelsvc/echannel/mandate/setup";
  curl_setopt_array($curl, array(

   CURLOPT_URL => $url,

   CURLOPT_RETURNTRANSFER => true,

   CURLOPT_MAXREDIRS => 10,

   CURLOPT_TIMEOUT => 300,

   CURLOPT_CUSTOMREQUEST => "POST",

   CURLOPT_POSTFIELDS => json_encode($parameter),

   CURLOPT_HTTPHEADER => array(

     "accept: application/json",

     "cache-control: no-cache",

     "content-type: application/json"

   ),

  ));
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);

 

$data = json_decode(curl_exec($curl), true); 


$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

$err = curl_error($curl);
if($err){
    return $err;
}else{
    if($data["statuscode"] == "040")
    {
        
      $hash = hash('sha512', $merchantId.$api_key.$requestId);
      $mandateId = $data["mandateId"];
      $form = "https://login.remita.net/remita/ecomm/mandate/form/".$merchantId."/".$hash."/".$mandateId."/".$requestId."/rest.reg";
      
      $update_fields = array('rrr'=>$mandateId, 'ddstartdate'=>$startDate, 'ddenddate'=>$endDate,'requestid'=>$requestId);
      // $condition = array('id'=>$id);
      // $initiate = $db->updateTable($update_fields, 'loanapplications', $condition);
      $updrecord = Loan::where('id',$id)->first();
      $updrecord->update($update_fields);
      return $form;
    }else{
        return $data;
    }
   
}

}


    private function last_day($month = '', $year = '') 
    {
       if (empty($month)) 
       {
          $month = date('m');
       }
        
       if (empty($year)) 
       {
          $year = date('Y');
       }
        
       $result = strtotime("{$year}-{$month}-01");
       $result = strtotime('-1 second', strtotime('+1 month', $result));
     
       return date('Y-m-d', $result);
    }
    private function AlertEmail($customername,$ippisnumber,$mda,$amount) {
        $to = "alert@creditwallet.ng";
        $subject = 'Loan Application - New';
        $from = 'Credit Wallet <support@creditwallet.ng>';
         
        // // Compose a simple HTML email message
        $message = '<html>
            <body style = "background-color: #f2f2f2">
                <div style = "background-color: white; border-radius: 5px; border-top:4px solid #f0ad4e;display: block;margin-top:20px; padding : 30px;">
                    <h1 text-align: center;"><strong><span style = "color:#1B4E63">Credit</span><span style = "color:#f0ad4e">Wallet</span></strong></h1>
                    <p>Dear Team,</p>
                    <p>You have a new application. Below is the customer details</p>
                    <p><strong>Name </strong> - '.$customername.' </p>
                    <p><strong>IPPIS Number </strong> - '.$ippisnumber.' </p>
                    <p><strong>MDA </strong> - '.$mda.' </p>
                    <p><strong>Loan Amount </strong> - '.$amount.' </p>
                <p>Regards,</p>
                <p><strong>Credit Wallet Team</strong></p>
     
                
                </div>
            
            </body>
        </html>';
         
        $array_data = array(
          'from'=> $from,
          'to'=> $to,
          'subject'=> $subject,
          'html'=> $message,
          'h:Reply-To'=> $from
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
          $results['status'] = "success";
          return $results;
          
    }
    



    private function sendNewLoanApplicationEmail($email,$firstname) {
    
        $to = $email;
        $subject = 'Credit Wallet - New Loan Application';
        $from = 'Credit Wallet <support@creditwallet.ng> ';
         
        // Compose a simple HTML email message
        $message = "
                    <!doctype html>
                    <html>
                    <head>
                    <meta name=\"viewport\" content=\"width=device-width\">
                    <meta http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
                    <title>$subject</title>
                    <style media=\"all\" type=\"text/css\">
                    @media only screen and (max-width: 620px) {
                      table[class=body] h1,
                      table[class=body] h2,
                      table[class=body] h3,
                      table[class=body] h4 {
                        font-weight: 600 !important;
                      }
                      table[class=body] h1 {
                        font-size: 22px !important;
                      }
                      table[class=body] h2 {
                        font-size: 18px !important;
                      }
                      table[class=body] h3 {
                        font-size: 16px !important;
                      }
                      table[class=body] .content,
                      table[class=body] .wrapper {
                        padding: 10px !important;
                      }
                      table[class=body] .container {
                        padding: 0 !important;
                        width: 100% !important;
                      }
                      table[class=body] .btn table,
                      table[class=body] .btn a {
                        width: 100% !important;
                      }
                    }
                    </style>
                    </head>
    
                    <body style=\"margin: 0; font-family: 'Helvetica Neue', 'Helvetica', Helvetica, Arial, sans-serif; font-size: 14px; height: 100% !important; line-height: 1.6em; -webkit-font-smoothing: antialiased; padding: 0; -ms-text-size-adjust: 100%; -webkit-text-size-adjust: 100%; width: 100% !important; background-color: #f6f6f6;\">
    
                    <table class=\"body\" style=\"box-sizing: border-box; border-collapse: separate !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background-color: #f6f6f6;\" width=\"100%\" bgcolor=\"#f6f6f6\">
                        <tr>
                            <td style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top;\" valign=\"top\"></td>
                            <td class=\"container\" style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top; display: block; Margin: 0 auto !important; max-width: 580px; padding: 10px; width: 580px;\" width=\"580\" valign=\"top\">
                                <div class=\"content\" style=\"box-sizing: border-box; display: block; margin: 0 auto; max-width: 580px; padding: 10px;\">
                    <span class=\"preheader\" style=\"color: transparent; display: none; height: 0; max-height: 0; max-width: 0; opacity: 0; overflow: hidden; mso-hide: all; visibility: hidden; width: 0;\"></span>
                    <div class=\"header\" style=\"box-sizing: border-box; margin-bottom: 30px; margin-top: 20px; width: 100%;\">
                      <table style=\"box-sizing: border-box; border-collapse: separate !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;\" width=\"100%\">
                        <tr>
                          <td class=\"align-center\" style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top; text-align: center;\" valign=\"top\" align=\"center\">
                            <a href=\"https://creditwallet.ng\" style=\"box-sizing: border-box; color: #348eda; text-decoration: underline;\"><img src=\"https://creditwallet.ng/api/public/uploads/logo.png\" height=\"50\" alt=\"Credit Wallet\" style=\"-ms-interpolation-mode: bicubic; max-width: 100%;\"></a>
                          </td>
                        </tr>
                      </table>
                    </div>
                    <table class=\"main\" style=\"box-sizing: border-box; border-collapse: separate !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; background: #ffffff; border: 1px solid #e9e9e9; border-radius: 3px;\" width=\"100%\">
                      <tr>
                        <td class=\"wrapper\" style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top; padding: 30px;\" valign=\"top\">
                          <table style=\"box-sizing: border-box; border-collapse: separate !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%;\" width=\"100%\">
                            <tr>
                              <td style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top;\" valign=\"top\">
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\">Dear <b>$firstname</b>,</p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\">Thank you for submitting your loan application.</p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\">It may take up to 72 hours for us to process your loan application and get back to you.</p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\">Please note that you will not be contacted if you do not currently work for an eligible organisation or if there are significant discrepancies in your application.</p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\">Please do feel free to contact us on <strong>support@creditwallet.ng</strong> if you do not receive a response from us after 72 hours of your appliction.</p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\"></p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\"><strong>Kind Regards,<br/>Customer Success Team</strong></p>
    
                                    <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; font-weight: normal; margin: 0; margin-bottom: 15px;\"></p>
                                </td>
                            </tr>
                          </table>
                        </td>
                      </tr>
                    </table>
                    <div class=\"footer\" style=\"box-sizing: border-box; clear: both; width: 100%;\">
                      <table style=\"box-sizing: border-box; border-collapse: separate !important; mso-table-lspace: 0pt; mso-table-rspace: 0pt; width: 100%; color: #999999; font-size: 12px;\" width=\"100%\">
                        <tr style=\"color: #999999; font-size: 12px;\">
                          <td class=\"align-center\" style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; vertical-align: top; font-size: 12px; color: #999999; text-align: center; padding: 20px 0;\" valign=\"top\" align=\"center\">
                            <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-weight: normal; margin: 0; margin-bottom: 15px; color: #999999; font-size: 12px; text-align: center;\">Questions? Email: support@creditwallet.ng</p>
                            <p style=\"font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-weight: normal; margin: 0; margin-bottom: 15px; color: #999999; font-size: 12px;\">Don't want to receive these emails? <a href=\"\" style=\"box-sizing: border-box; text-decoration: underline; color: #999999; font-size: 12px;\"><unsubscribe style=\"color: #999999; font-size: 12px;\">Unsubscribe</unsubscribe></a>.</p>
                          </td>
                        </tr>
                      </table>
                    </div>
                    </div>
                            </td>
                            <td style=\"box-sizing: border-box; font-family: 'Helvetica Neue', Helvetica, Arial, 'Lucida Grande', sans-serif; font-size: 14px; vertical-align: top;\" valign=\"top\"></td>
                        </tr>
                    </table>
    
                    </body>
                    </html>
    
                ";
         
                $array_data = array(
                  'from'=> $from,
                  'to'=> $to,
                  'subject'=> $subject,
                  'html'=> $message,
                  'h:Reply-To'=> $from
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
                  $results['status'] = "success";
                  return $results;
            
            
    }
    


}
