<?php

namespace App\Http\Controllers;

use App\Investmentstart;
use Illuminate\Http\Request;

class InvestmentstartController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $istartdata = request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date'
        ]);
        if(request()->filled('referal_code')){
            request()->validate([
                'referal_code' => 'required|string',
            ]);
            $istartdata['referal_code'] = request()->referal_code;
        }
        
        // $imergestart = Investmentstart::create($istartdata);

        
        return response($istartdata);
    }

    public function initiate(){
        request()->validate([
            'amount' => 'required|integer',
            'duration' => 'required|integer|min:6',
            'investment_start_date' => 'date',
            'rate' => 'required|numeric'
        ]);
        $history=array();
        $rate = request()->rate/100;
        $investmentstartdate = request()->investment_start_date;
        $startdateenddate =  date("Y-m-t", strtotime(request()->investment_start_date));
        $date1=date_create($investmentstartdate);
        $date2=date_create($startdateenddate);
        $datediff = date_diff($date1,$date2);
        $actualtenor = $datediff->days;
        $month = date("m",strtotime($investmentstartdate));
        $year = date("Y",strtotime($investmentstartdate));
        $daysinamonthone = cal_days_in_month(CAL_GREGORIAN,$month,$year);
        for($x=1; $x <= request()->duration; $x++){
            $date = date("Y-m-d");
            if($x == 1){
                if($actualtenor <= 5){
                    $month++;
                    $percentone = ($rate * $actualtenor) / $daysinamonthone;
                    $date = date('Y-m-d', strtotime('+'.$month.' months'));
                    $year = date('Y', strtotime($date));
                    $month = date('m', strtotime($date));
                    $newstartdate = date($year.'-'.$month.'-01');
                    $enddate =  date($year.'-'.$month.'-t', strtotime($newstartdate));
                    $percent = $percentone + $rate;
                    $history[] = array(
                        'percent'=>$percent,
                        'duedate'=>$enddate
                    );
                }else{
                    $currentmonth = date("m",strtotime($investmentstartdate));
                    $year = date("Y",strtotime($investmentstartdate));
                    $enddate =  date($year.'-'.$currentmonth.'-t', strtotime($investmentstartdate));
                    $daysinamonth = cal_days_in_month(CAL_GREGORIAN,$currentmonth,$year);
                    $percent = ($rate * $actualtenor) / $daysinamonth;
                    $history[] = array(
                        'percent'=>$percent,
                        'duedate'=>$enddate
                    );
                }
            }else{
                $month++;
                $date = date('Y-m-d', strtotime('+'.$month.' months'));
                $year = date('Y', strtotime($date));
                $month = date('m', strtotime($date));
                $startdate = date($year.'-'.$month.'-01');
                $enddate =  date($year.'-'.$month.'-t', strtotime($startdate));
                $history[] = array(
                    'percent'=>$rate,
                    'duedate'=>$enddate
                );
            }
        }
        return $history;
        
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

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
}
