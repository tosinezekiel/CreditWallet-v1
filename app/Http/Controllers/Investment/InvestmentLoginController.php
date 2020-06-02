<?php

namespace App\Http\Controllers\Investment;
use App\Investment;
use Carbon\Carbon;
use JWTAuth;
use Tymon\JWTAuth\Facades\JWTFactory;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class InvestmentLoginController extends Controller
{
    public function login(Request $request){
        
        // $this->checkEmailOnLoandDisk();
        $this->validateUsernameAndPassword();
        
        if(!Investment::where('email',$request->email)->exists()){
            return response(['message'=>'invalid credential', 'Status'=>'error'], 401);
        }
        if(!$this->verifyUser(request()->email,request()->password)){
            return response(['message'=>'invalid credentials', 'Status'=>'error'], 401);
        }
        $hashedpassword = $this->getHashedPassword(request()->email);
        $investment = Investment::where('email',request()->email)->where('password',$hashedpassword)->first();
        // check email on loan disk;
        if($this->checkEmailOnLoandDisk($investment->email)){
            return response(['message'=>'user email not found on loanDisk', 'Status'=>'error'], 404);
        }
        $customClaims = $this->createCustomClaims($investment);

        $factory = JWTFactory::customClaims([
            'sub'   => env('APP_KEY'),
            'uuid' =>  $customClaims
        ]);

        $payload = $factory->make();
        $token = JWTAuth::encode($payload);

        return response(['data' => $investment, 'status' => 'success', 'token' => "{$token}"], 200);

    }
    public function forgotPassword(){
        //validate email address
        $this->validateEmailAddress();
        //check for email in db
        if(!Investment::where('email',request()->email)->exists()){
            return response(['message' => 'we cannnot find the supplied email address', 'status' => 'error'], 404);
        }
        $investment = Investment::where('email',request()->email)->first();
        //get hash code
        $code = $this->getRandomString();
        $email_code = Hash::make($code);
        $email_link = "localhost/creditwallet/url.php?email=".request()->email."&&token=".$email_code;

        //update investment table
        $investment->update(['forgot_token'=>$email_code]);
        // mailing user
            $array_data = array(
                'from'=> 'Credit Wallet Finance<finance@mail.creditwallet.ng>',
                'to'=> $investment->email,
                'subject'=> "Reset Password",
                'html'=> "<p>kindly click the link below to reset password</p><p>".$email_link."</p>",
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
                $results['Message'] = "password reset link has been sent";
                return $results;
    }

    public function logout(){
        JWTAuth::invalidate(JWTAuth::getToken());
        return response()->json([
            'status' => 'success',
            'message' => 'logout'
        ], 200);
    }
    public function VerifyForgotPasswordToken(Request $request){
        $this->validateEmailAddress();
        if(!Investment::where('email',request()->email)->exists()){
            return response(['message' => 'email address not found', 'status' => 'error'], 404);
        }
        if(!Investment::where('email',request()->email)->where('forget_password_token',request()->email_code)->exists()){
            return response(['message' => 'invalid token', 'status' => 'error'], 404);
        }
        return response(['message' => 'verified successfully', 'status' => 'success'], 200);
    }

    public function resetPassword(){
        $this->validateEmailAddress();
        if(!Investment::where('email',request()->email)->exists()){
            return response(['message' => 'email address not found', 'status' => 'error'], 404);
        }
        $investment = Investment::where('email',request()->email)->first();
        $new_password = Hash::make(request()->password);
        $investment->update(['password' => $new_password]);
        return response(['message' => 'password reset successful', 'status' => 'success'], 200);
    }

    

    private function getTokensPayload(){
        $token = JWTAuth::getToken(); 
        return JWTAuth::getPayload($token);
    }
    public function checkEmailOnLoandDisk($email){
        // return false;
        // return "hey";
        //retrieving from loan disk using email;
        $url = "https://api-main.loandisk.com/3546/4110/borrower/borrower_email/".$email;
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
            return true;
        }
        return false;
    }
    public function getHashedPassword($username){
        $investment = Investment::whereEmail($username)->first();
        return $investment->password;
    }
    private function createCustomClaims($data){
        date_default_timezone_set('Africa/Lagos');
        $now = Carbon::now();

        $customClaims = $data;
        $customClaims['now'] = $now->format('Y-m-d H:i:s');
        $customClaims['expiry'] = $now->addHour(6)->format('Y-m-d H:i:s');
        return $customClaims;
    }
    public function verifyUser($username, $password){
        $hashedpassword = $this->getHashedPassword($username);
        if(!Hash::check($password, $hashedpassword)){
            return false;
        }
        return true;
        // return Investment::where('email',$username)->where('password',$hashedpassword)->exists();
    }
    private function validateEmailAddress()
    {
        return request()->validate([
            'email'  => 'required|email',
        ]);
    }

    private function validateUsernameAndPassword()
    {
        return request()->validate([
            'email'  => 'required|string',
            'password'  => 'required'
        ]);
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
