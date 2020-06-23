<!DOCTYPE HTML>

<html>
    <head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
        <style type="text/css" media="all">
            body{
                font-size: 9;
                font-family: 'Times New Roman', Times, serif;
            }
            .grey{
                color:rgb(63, 63, 63);
            }
            .header-bar{
                width:100%;
                height: 30px;
                background-color: #F1955C;
                margin-left: 250px;
                color:#fff;
            }
            .header-text{
                font-size: 13px;
                line-height: 30px;
                float:right;
                padding-right:10px;
                color:#fff
            }
            .top-image{
                width:100%;
                height: 30px;
                margin-top:15px;
                margin-left: 250px;
                margin-bottom: 50px;

                /* background-color: #F1955C; */
            }
            .header-bar-test{
                
                height: 30px;
                background-color: #F1955C;
                color:#fff;
            }
            .clear{
                clear:both;
            }
            .top-header{
                height: 30px;
                background:  #F1955C;
            }
        </style>
    </head>
    <body class="">
        <div class="top-header">
            <span class="header-text">PRINCEPS CREDIT SYSTEMS LIMITED</span>
        </div>
        <div class="top-image">
            <div style="float:right;">
                <span><i>aka Credit Wallet</i></span>
                <img src="{{public_path('images/creditwallet.png')}}" width="50px" height="50px"/>   
            </div>
        </div>
        <center>
        <table width="100%" border="0">
           <tr>
               <td>
                    <span style="float:right;">
                        Princeps Credit Systems Limited <br>
                        Pentagon Plaza,  <br>
                        2nd Floor (Wing D),  <br>
                        23 Opebi Rd, <br> 
                        Ikeja, Lagos <br>
                        Nigeria <br>
                        <time>{{ date('d/m/Y') }}</time> 
                    </span>
                
                </td>
           </tr>
        </table>
        <div class="clear"></div>
       <table width="100%" border="0">
           <tr>
            <td colspan="2">
             <header>
                 <span>Dear Mr. {{ $fullname }}, </span>
             </header>   
             </td>
            </tr>
            <tr>
                <td colspan="2">
                 <header>
                     <center><span style="text-align:center;"><strong><u>Deposit Investment</u> </strong></span></center>
                 </header>   
                 </td>
            </tr>
            <tr>
                <td colspan="2">
                 <header>
                     <span> 
                     This is to confirm the rollover of your deposit investment of N{{ $amount }} with Princeps Credit Systems Limited for another {{$duration}}.
                        <br><br>Please see updated terms below:  
                    </span>
                 </header>   
                 </td>
            </tr>
       
        <tr>
            <td width="30%">
             <strong>DEPOSIT AMOUNT: </strong>
            </td>
            <td width="80%">
             N{{ $totaldeposit }} 
             </td>
        </tr>
        <tr>
         <td width="30%">
            <strong>INTEREST RATE:  </strong>
         </td>
         <td width="80%">
             {{$rate}}% Flat per month (i.e. {{$per_annum}}% per annum) 
          </td>
         </tr>
         <tr>
             <td width="30%">
                <strong>INTEREST AMOUNT: </strong>
             </td>
             <td width="80%">
                 N{{$successiveinterest}} per month (prorated)  
              </td>
         </tr>
         <tr>
             <td width="30%">
                <strong>DURATION:  </strong>
             </td>
             <td width="80%">
                 {{$duration}}
             </td>
         </tr>
         <tr>
            <td width="30%">
               <strong>ROLL-OVER START DATE: :  </strong>
            </td>
            <td width="80%">
                {{$rolloverstartdate}} 
            </td>
        </tr>
        <tr>
            <td width="30%">
               <strong>MATURITY DATE:    </strong>
            </td>
            <td width="80%">
                {{$mat_date}}
            </td>
        </tr>
    </table>
    <table width="100%" border="0" style="margin-top:20px;">
        <tr>
            <td>
                 <p>
                    Please see attached statement showing the schedule of your investment and interest payments due to you on
                     monthly basis up to {{$mat_date}}.   
                 </p>
            
            
                <p>
                Furthermore, you’ll receive monthly interest payments of N{{$successiveinterest}} at the end of each successive month.   
               </p>
            
          
                <p>
                    Please note that we’ll require 3 months’ notice should you wish to liquidate your investment earlier than {{$mat_date}}.   
               </p>
               <p>
                Thank you for choosing Princeps Credit Systems Limited
               </p>
               <p>
                Kind regards,  
               </p>
               <p>
                <img src="{{public_path('images/sign_doc.jpg')}}"  height="100px"/>   
               </p>
             </td>
        </tr>
        <tr>
            <td>
                <header>
                    <span>
                        Peter Atuma  <br>
                        Chief Executive Officer <br>
                        Princeps Credit Systems Limited <br><br>
                        <span class="grey">PRINCEPS CREDIT SYSTEMS LIMITED</span> Pentagon Plaza, 2nd Floor (Wing D), 23 Opebi Rd, Ikeja, Lagos, Nigeria 
                    </span>
                </header>   
             </td>
        </tr>
    </table>
    </center>
    </body>
</html>