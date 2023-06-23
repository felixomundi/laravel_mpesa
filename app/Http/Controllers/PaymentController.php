<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PaymentController extends Controller
{
    public function token(){
        $consumer_key = "K3cm0dWQaqwNnqIYaNEJMx2WKdkXFkVh";
        $consumer_secret = "CgIsLnhqwKcNM6X9";
        $url ="https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials";
        $response = Http::withBasicAuth($consumer_key, $consumer_secret)->get($url);
        return $response;
    }
}
