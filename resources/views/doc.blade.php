<!DOCTYPE HTML>

<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/> 
        <style type="text/css" media="all">
            body{
                font-size: small;
                font-family: 'Times New Roman', Times, serif;
            }
            .grey{
                color:rgb(63, 63, 63);
            }
            .header{
                width:100%;
                background: #ccc;
                height:30px;
                padding:0;
            }
            .header p{
                letter-spacing: 3px;
                font-weight: 700;
                font-size:18px;
                line-height: 30px;
            }
            table { 
                border-collapse: collapse; 
                font-size:13px;
            }
            th:nth-child(n) { 
                background: #ccc;
            }
            td:nth-child(n) { 
                border:none;
            }
            .left{
                float:left;
            }
            .right{
                float:right;
            }
            .right-to-left{
                font-size: 13px;
                font-weight: 700;
                width:100%;
            }
            .simple-header{
                font-size:15px;
                font-weight: 700;
                letter-spacing: 4px;
                margin-top:20px;
            }
            .clear{
                clear:both;
            }
        </style>
    </head>
    <body class="">
        <center>
            <img src="{{public_path('images/creditwallet.png')}}" alt="" width="150px" height="150px">
            <div class="header">
                <p>SAVINGS ACCOUNT STATEMENT</p>
            </div>
            <div class="right-to-left">
                <span class="left">Credit Wallet</span>
                <span class="right">Date: 27/05/2020 <br>{{request()->name}}</span>
            </div>
            <div class="clear"></div>
            <div class="simple-header">SAVINGS ACCOUNT</div>
            <Table width="100%" border="1" cellpadding="5px">
                <tr>
                    <th>
                        Account
                        Number 
                    </th>
                    <th>
                        Interest Rate Per
                        Annum
                    </th>
                    <th>
                        Savings Available
                        Balance
                    </th>
                    <th>
                        Savings Ledger
                        Balance
                    </th>
                </tr>
                
                <tr>
                    <td align="center">{{ $savings_account['savings_account_number'] }}</td>
                    <td align="center">{{ $savings_account['rate_per_annum'] }}</td>
                    <td align="center">{{ number_format($savings_account['savings_balance'],2)}}</td>
                    <td align="center">{{ number_format($savings_account['savings_balance'],2)}}</td>
                </tr>
            </Table>
            <div class="simple-header">TRANSACTIONS</div>
            <table width="100%" border="1" cellpadding="5px">
                <tr>
                    <th>Date</th>
                    <th>Transaction</th>
                    <th>Description</th>
                    <th>Debit</th>
                    <th>Credit</th>
                    <th>Balance</th>
                </tr>
                @php $amount = 0; @endphp
                @foreach($testarray as $txn)
                    <tr>
                        <td align="left">{{ str_replace('-','/',$txn['transaction_date']) }}<br>
                            {{$txn['transaction_time']}}</td>
                        <td align="left">
                            @if($txn['transaction_type_id'] === 1)
                                Deposit
                            @elseif($txn['transaction_type_id'] === 9)
                                Interest
                            @elseif($txn['transaction_type_id'] === 14)
                                Transfer Out
                            @endif
                        </td>
                        <td align="left">{{$txn['transaction_description']}}</td>
                        <td align="left">
                            @if($txn['transaction_type_id'] === 14)
                                {{number_format($txn['transaction_amount'],2)}}
                            @endif
                        </td>
                        <td align="left">
                            @if($txn['transaction_type_id'] === 1 || $txn['transaction_type_id'] === 9)
                                {{number_format($txn['transaction_amount'],2)}}
                            @endif
                        </td>
                        <td align="left">
                            @php
                                if($txn['transaction_type_id'] === 1 || $txn['transaction_type_id'] === 9){
                                    $amount = $amount + $txn['transaction_amount'];
                                    echo number_format($amount,2);
                                }else if($txn['transaction_type_id'] === 14){
                                    $amount = $amount - $txn['transaction_amount'];
                                    echo number_format($amount,2);
                                }
                            @endphp
                        </td>
                    </tr>
                @endforeach
            </table>
        </center>
    </body>
</html>