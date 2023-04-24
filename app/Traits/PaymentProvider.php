<?php

namespace App\Traits;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;
/**
 * 
 */
Trait PaymentProvider
{
    private $objPaymentResponse;

    protected function paymentRequest($arrRequestData){

        try{
            $arrRequestData =   json_encode($arrRequestData);
            $strPostUrl     =   config('services.payment_provider_url');
            // request headers
            $arrHeaders     =   [
                'Content-Type' => 'application/json',
                'Content-Length' => strlen($arrRequestData),
            ];
            // create guzzle client
            $objClient  =   new Client(['headers'=>$arrHeaders]);
            $this->objPaymentResponse = $objClient->request('post',$strPostUrl,['body'=> $arrRequestData]);
        }
        catch(\Exception $e){
            // return error message 
            return $e->getMessage();
        }

        return true;
    }
    
}
