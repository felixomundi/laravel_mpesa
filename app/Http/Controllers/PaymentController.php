<?php

namespace App\Http\Controllers;

use App\Models\Payments;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

class PaymentController extends Controller
{

    public function index(){

        return "Welcome to daraja home of apis";

    }

    public function token(){
        $consumer_key = "K3cm0dWQaqwNnqIYaNEJMx2WKdkXFkVh";
        $consumer_secret = "CgIsLnhqwKcNM6X9";
        $url ="https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $response = Http::withBasicAuth($consumer_key, $consumer_secret)->get($url);
        return $response["access_token"];
    }


        public function initializeStkPush(){
            $token = $this->token();
            $url = env("MPESA_STK_PUSH_URL");
            $amount = 1;
            $partyA = 254745566505;
            $phoneNumber = "254745566505";
            $account_reference = "Kadesea Agency";
            $transaction_description ="Pay School Fees";

            try{
            $data = [
                'BusinessShortCode' => env("MPESA_SHORTCODE"),
                'Password' => base64_encode(env("MPESA_SHORTCODE").env('MPESA_PASSKEY').date('YmdHis')),
                'Timestamp' => date('YmdHis'),
                'TransactionType' => 'CustomerPayBillOnline',
                'Amount' => $amount, // Set your desired amount here
                'PartyA' => $partyA, // Replace with the customer's phone number
                'PartyB' => env("MPESA_SHORTCODE"),
                'PhoneNumber' => $phoneNumber, // Replace with the customer's phone number
                'CallBackURL' => env('MPESA_CALLBACK_URL'),
                'AccountReference' => $account_reference,
                'TransactionDesc' => $transaction_description,
                'Passkey' => env('MPESA_PASSKEY'),
                ];

                $response = Http::withToken($token)->post($url, $data);
                // return $response;

                $res = json_decode($response);
                $rescode = $res->ResponseCode;
                if($rescode == 0){
                    $merchantId = $res->MerchantRequestID;
                    $checkoutId = $res->CheckoutRequestID;
                    $customerMessage = $res->CustomerMessage;

                    $payment = new Payments();
                    $payment->phone = $phoneNumber;
                    $payment->amount=$amount;
                    $payment->reference =$account_reference;
                    $payment->description = $transaction_description;
                    $payment->MerchantRequestID = $merchantId;
                    $payment->CheckoutRequestID = $checkoutId;
                    $payment->status = "Requested";
                    $payment ->save();
                    return $customerMessage;

                }

            }
            catch(Throwable $e){
                return $e->getMessage();
            }

        }
        public function stkCallBack(){
           $data = file_get_contents('php://input');
           Storage::disk("local")->put("stk_payment.json", $data);
           $response = json_decode($data);
           $resultCode = $response->Body->stkCallback->ResultCode;
           if($resultCode ==0){
            // $mId = $response->Body->stkCallback->MerchantRequestID;
            $cId =$response->Body->stkCallback->CheckoutRequestID;
            $resultDesc = $response->Body->stkCallback->ResultDesc;
            $amount  = $response->Body->stkCallback->CallbackMetadata->Item[0]->Value;
            $MpesaReceiptNumber = $response->Body->stkCallback->CallbackMetadata->Item[1]->Value;
            $TransactionDate = $response->Body->stkCallback->CallbackMetadata->Item[3]->Value;
            $PhoneNumber  = $response->Body->stkCallback->CallbackMetadata->Item[4]->Value;

            $payment = Payments::where("CheckoutRequestID", $cId)->firstOrFail();
            $payment->status = "Paid";
            $payment->TransactionDate =$TransactionDate;
            $payment->ResultDesc =$resultDesc;
            $payment->amount =$amount;
            $payment->MpesaReceiptNumber =$MpesaReceiptNumber;
            $payment->phone =$PhoneNumber;
            $payment->save();
           }else{
            $cId =$response->Body->stkCallback->CheckoutRequestID;
            $resultDesc = $response->Body->stkCallback->ResultDesc;
            $payment = Payments::where("CheckoutRequestID", $cId)->firstOrFail();
            $payment->ResultDesc= $resultDesc;
            $payment->status = "Failed";
            $payment->save();
           }

        }

}
