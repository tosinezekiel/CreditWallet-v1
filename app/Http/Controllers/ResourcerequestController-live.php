<?php

namespace App\Http\Controllers;
use App\Resourcerequest;
use App\Adminlogin;
use Illuminate\Http\Request;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;

class ResourcerequestController extends Controller
{

    public function index(Request $request){
        
        $data = $this->filterResource($request);
        return response(['data' => $data,'status' => 'success'], 200); 
    }
    public function awaiting(Request $request){
        
        $data = Resourcerequest::where('status',0)->get();
        return response(['data' => $data,'status' => 'success'], 200); 
    }
    
    public function approved(Request $request){
        
        $data = Resourcerequest::where('status',1)->get();
        return response(['data' => $data,'status' => 'success'], 200); 
    }
    
    public function rejected(Request $request){
        
        $data = Resourcerequest::where('status',2)->get();
        return response(['data' => $data,'status' => 'success'], 200); 
    }
    
    public function canceled(Request $request){
        
        $data = Resourcerequest::where('status',3)->get();
        return response(['data' => $data,'status' => 'success'], 200); 
    }
    public function store(Request $request){
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
       
        $this->validateStore();

        $creator = Adminlogin::where('authid', $uuid->authid)->first();
        $resourcerequest = $creator->resourcerequest()->create([
            'category' => $request->get('category'),
            'type' => $request->get('type'),
            'status'=> 0
        ]);
        return response(['message'=>'resource request successfully created', 'status' => 'success'], 200); 
    }

    public function show(Resourcerequest $resourcerequest){
        return response(['data' => $resourcerequest->load('creator'), 'status' => 'success'], 200); 
    }


    public function reject(Request $request){
        request()->validate([
            "reason" => "required",
            "id" => "required"
        ]);
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];

        $now = date("Y-m-d H:i:s");
        $this->validateMessage();
        $resourcerequest = Resourcerequest::whereId(request()->id)->first();
        $resourcerequest->update(['status'=>2, 'final_approved_by' => $uuid->authid, 'final_approved_date' => $now ]);
        $to = $resourcerequest->creator->email;
        $toname = $resourcerequest->creator->firstname;
        $subject = "Testing Resource Request Rejected";
        $replyto = $resourcerequest->creator->email;
        $html = $this->ResourceRequestEmailForRejection($toname, $request->reason, $uuid->email, $uuid->department);
        
        $array_data = array(
            'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
            'to'=> $toname.'<'.$to.'>',
            'subject'=> $subject,
            'html'=> $html,
            'h:Reply-To'=> $replyto
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

    public function cancel(Resourcerequest $resourcerequest){
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];

        $now = date("Y-m-d H:i:s");
        $resourcerequest->update(['status'=>3,'final_approved_by' => $uuid->authid, 'final_approved_date' => $now]);
        return response(['message' => "resource request canceled successfully", 'status' => 'success'], 200);
    }

    public function approve(Request $request){
        $apy = $this->getTokensPayload();
        $uuid = $apy['uuid'];
        
        $request->validate([
            'amount' => 'required',
            'id' => 'required'
        ]);

        $now = date("Y-m-d H:i:s");
        $resourcerequest = Resourcerequest::where('id',$request->id)->first();
        $resourcerequest->update(['status'=>1, 'final_approved_by' => $uuid->authid, 'final_approved_date' => $now, 'amount'=>$request->amount]);
        $to = $resourcerequest->creator->email;
        $toname = $resourcerequest->creator->firstname;
        $subject = "Testing Resource Request Approved";
        $html = $this->ResourceRequestEmail($toname, "Your resource request has been approved", $uuid->email, $uuid->department);
        $replyto = $uuid->email;
        $array_data = array(
            'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
            'to'=> $toname.'<'.$to.'>',
            'subject'=> $subject,
            'html'=> $html,
            'h:Reply-To'=> $replyto
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
            $results['message'] = "resource request approved successfully";
            $results['status'] = "success";
            return $results;
    }
    

public function ResourceRequestEmail ($name, $message, $email, $department) {
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
            <p>".$message.".</p>
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

    public function ResourceRequestEmailForRejection($name, $reasons, $email, $department) {

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
            <li>".$reasons."</li>
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

    private function filterResource($request){

        $query = Resourcerequest::with('creator');

        if ($request->filled('status')) {
            $query->where('status', '=', $request->status);   
        }
        if ($request->filled('title') ) {
            $query->where('title', '=', $request->title);
            
        }
        if ($request->filled('description') ) {
            $query->where('description', '=', $request->description);
        }
        if($request->filled('authid')){
            $query->creator()->where('authid', '=', $request->authid);
        }
        if($request->filled('type')){
            $query->where('type', '=', $request->type);
        }
        if($request->filled('from') && $request->filled('to')){
            $this->validateList();
            $from = date($request->from);
            $begin = date("Y-m-d",strtotime($from . ' -1 day'));
            $to = date($request->to);
            $end = date("Y-m-d",strtotime($to . ' +1 day'));
            $query->whereBetween('created_at',[$begin,$end]);
        }
        return $query->paginate($request->page_size);
    }

    public function borrow(Request $request){
        // return $request->email;
        $url = "https://api-main.loandisk.com/3546/4110/borrower/borrower_email/".$request->email;
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
                return response($data['response']);       
            }else{
                $response['status'] = "error";
                $response['data'] = $data;
                $response['message'] = "Something went wrong, please try again but if problem persist, please contact our customer support team on support@creditwallet.ng";
                echo json_encode($response);
            }
        // return $data; 
        }
    private function checkTokens(){
        if(! $token = JWTAuth::getToken()){
            return response(['message' => 'unauthenticated', 'status' => 'error'], 401);
        }
    }
    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }

    // private function tokenExpired($expiry){
    //     date_default_timezone_set('Africa/Lagos');
    //     $currDate = date('Y-m-d H:i:s');
    //     $expiry = strtotime($expiry);
    //     $cdate = strtotime($currDate);
    //     if($cdate > $expiry){
    //         return response(['message' => 'expired token','status' => 'error'], 401);
    //     }
    // }
    private function tokenExpired(){
        if(empty(JWTAuth::parseToken()->check())){
            return response(['message' => 'expired token','status' => 'error'], 401);
        }
    }

    private function validateStore()
    {
       return request()->validate([
            'category'  => 'required|string',
            'type'  => 'required'
        ]);
    }
    private function validateMessage()
    {
        return request()->validate([
            'reason'  => 'required|string',
        ]);
    }
    private function validateApprovalMessage()
    {
        return request()->validate([
            'message'  => 'required|string',
        ]);
    }

    private function validateList()
    {
        return request()->validate([
            'from'  => 'date',
            'to'   => 'date'
        ]);
    }
}
