<?php

namespace App\Http\Controllers\Gateway\Calbank;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\Deposit;
class CashCollectionUniversalController extends Controller
{
    //
public function CreateIvoiceAndPayment($order_code,$pickup_data,$user_data,
$Amount,$new_contact,$discount,$paymentgateway){
$payment_number=$new_contact??"0".$user_data->mobile;
// return $payment_number;
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://calpayapi.caleservice.net/api/calpay',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'{
    "requestType": "CreateInvoice",
    "merchant": {
        "emailOrMobileNumber": "CALPAYOFFICIAL@CALBANK.NET",
        "apikey": "LIVE-rjT6GlZzdOCesUUBFlnYhtWvapOgJh51",
        "type": "EMAIL",
        "env": "LIVE",
        "destinationaccount": "",
        "sbmerchantid": ""
    },
    "payment":{
        "accounttype":"'.$paymentgateway.'",
        "accountnumber":"'.$payment_number.'",
        "mode":""
    },
    "orderItems": [
        {
            "unitPrice": "'.$Amount.'",
            "itemName": "'.$pickup_data->name.'",
            "quantity": "'.$pickup_data->price_per_kg.'",
            "itemCode": "'.$pickup_data->waste_type.'",
            "discountAmount":"'.$discount.'",
            "subTotal": "'.$Amount.'"
        }
      
    ],
    "order": {
        "customerAddressCity": "ACCRA",
        "otherData": "TEST",
        "datacompleteurl": "https://calbank.net/",
        "sendInvoice": "FALSE",
        "description": "'.'Borlarborla '.$pickup_data->name.'",
        "tax": 0,
        "customerName": "Borlarborlar",
        "customerCountry": "GHA",
        "datacancelurl": "https://calbank.net/",
         "totalAmount":"'.$Amount.'",
        "shipping": 0,
        "customerContact": "0548951998",
        "trasactionCardMode": "PURCHASE",
        "customerEmail": "DIVINENANA1@GMAIL.COM",
        "payOption": "ALL",
         "approveurl": "https://calbank.net",
        "currency": "GHS",
        "orderCode": "'.$order_code.'",
        "callbackurl": "https://calbank.net",
        "fullDiscountAmount": "'.$discount.'"
    }
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'x-auth: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtY29kZSI6Ik1DSC00NDE3Njc4NyIsImlkIjoiMTgiLCJpYXQiOjE1ODQ0NzA1ODV9.wMeo6m9FC5hT4OY08edKBQZR0ykXCSpwM1BLGobeJmM',
    'Cookie: cookiesession1=678B2877FDD458AD021A13C859EFC476'
  ),
));

$response = curl_exec($curl);

// return $response;

if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
    curl_close($curl);

    return response()->json([
        'status_code' => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        'status' => 'error',
        'message' => $error_msg
    ]);
}

curl_close($curl);

$res=json_decode($response)->return;
return response()->json([
    'status_code' => HttpFoundationResponse::HTTP_OK,
    'status' => 'success',
    'message' => json_decode($res)
]);
}

public function ConfirmPayment($tokenID){
$curl = curl_init();
curl_setopt_array($curl, array(
  CURLOPT_URL => 'https://calpayapi.caleservice.net/api/calpay',
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_ENCODING => '',
  CURLOPT_MAXREDIRS => 10,
  CURLOPT_TIMEOUT => 0,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
  CURLOPT_CUSTOMREQUEST => 'POST',
  CURLOPT_POSTFIELDS =>'
    {
    "requestType": "GetInvoiceDetails",
    "paymentToken":"'.$tokenID.'",
  "merchant": {
        "emailOrMobileNumber": "CALPAYOFFICIAL@CALBANK.NET",
        "apikey": "LIVE-rjT6GlZzdOCesUUBFlnYhtWvapOgJh51",
        "type": "EMAIL",
        "env": "LIVE",
        "destinationaccount": "",
        "sbmerchantid": ""
    }
}',
  CURLOPT_HTTPHEADER => array(
    'Content-Type: application/json',
    'x-auth: eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJtY29kZSI6Ik1DSC00NDE3Njc4NyIsImlkIjoiMTgiLCJpYXQiOjE1ODQ0NzA1ODV9.wMeo6m9FC5hT4OY08edKBQZR0ykXCSpwM1BLGobeJmM',
    'paymenttoken: 10000059',
    'Cookie: cookiesession1=678B2877FDD458AD021A13C859EFC476'
  ),
));
$response = curl_exec($curl);
if (curl_errno($curl)) {
    $error_msg = curl_error($curl);
    curl_close($curl);
    return response()->json([
        'status_code' => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        'status' => 'error',
        'message' => $error_msg
    ]);
}

curl_close($curl);
$res = json_decode($response, true);

// Your API only has ["return"], not ["message"]["return"]
if (isset($res['return'])) {
    $parsed = json_decode($res['return'], true);

    if($parsed['RESULT'][0]["TRNID"]==''&&$parsed['RESULT'][0]["FINALSTATUS"]!='SUCCESS'){
        return response()->json([
        'status_code' => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        'status' => 'error',
        'message' => 'Authorization pending',
        'state' => $parsed['RESULT'][0]["FINALSTATUS"]!='SUCCESS'
    ]);
    }

    $FinalisedPayment=$this->FinalisedPayment($tokenID,$parsed['RESULT'][0]["FINALSTATUS"],$parsed['RESULT'][0]["TRNID"]);
    return  $FinalisedPayment;
    return response()->json([
        'status_code' => HttpFoundationResponse::HTTP_OK,
        'status' => 'success',
        'message' => $parsed['MESSAGE'] ?? 'Unknown',
        'payment_url' => $parsed['RESULT'][0]['APIPAYREDIRECTURL'] ?? null,
        'order_id' => $parsed['RESULT'][0]['ORDERID'] ?? null,
        'raw' =>  $parsed['RESULT'],
        "TRNID"=>$parsed['RESULT'][0]["TRNID"],
        "FINALSTATUS"=>$parsed['RESULT'][0]["FINALSTATUS"]
    ]);
} else {

    return response()->json([
        'status_code' => HttpFoundationResponse::HTTP_INTERNAL_SERVER_ERROR,
        'status' => 'error',
        'message' => 'Missing return key in response',
        'raw' => $res
    ]);
}


}


public function FinalisedPayment($tokenID,$status,$tran_id){
    $Deposit=Deposit::where("payment_token",$tokenID)->first();
    $update=$Deposit->update([
        "TRN_ID"=>$tran_id,
        'status'=>$status=="SUCCESS"?1:2
    ]);
    $PaymentController=app(PaymentController::class);
    return $PaymentController->userDataUpdate($Deposit,null);
}
}
