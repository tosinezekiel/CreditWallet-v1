<?php

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
            $x = 1;
            $y = 1;
            $z = 1;
            $empty = [];


            // $result['last_deposit'] =  [];
            for($i = 0;$i < count($pre_data); $i++){
                if($pre_data[$i]['transaction_type_id'] === 1){
                    $results['total_investment'] = $i_amount += $pre_data[$i]['transaction_amount'];
                    $results['last_deposit'] = $pre_data[$i]['transaction_amount'];
                }
                if($pre_data[$i]['transaction_type_id'] === 9){
                    $results['total_interest_earned'] = $earn_amount += $pre_data[$i]['transaction_amount'];
                    array_push($empty,$pre_data[$i]);
                }
                if($pre_data[$i]['transaction_type_id'] === 14){
                    $trf_amount = $trf_amount += $pre_data[$i]['transaction_amount'];
                    $results['next_interest'] = $pre_data[$i]['transaction_amount'];
                }
            }
            
        }

        // return $empty;

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
        }, $empty);
        // return $testarray;
        usort($testarray, function ($a, $b) {
            return strtotime($a['transaction_date']) - strtotime($b['transaction_date']);
        });

        $last_test = end($testarray);
        $results['last_interest'] = $last_test['transaction_amount'];
        
        $results['total_savings'] = $savings_account;
        $results['total_interest_recieving'] = $results['total_interest_earned'] - $trf_amount;
        $results['Status'] = "success";
        return $results;



        ?>