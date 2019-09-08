<?php

use SilverStripe\Omnipay\GatewayInfo;

class PaymentGatewayControllerExtension extends Extension
{
    
    public function updatePaymentFromRequest($request, $gateway)
    {
       $identifier = $request->postVar('response_order_id');
       if(empty($identifier)) {
           return [];
       }
       
       return \Payment::get()
            ->filter('Identifier', $identifier)
            ->filter('Identifier:not', "")
            ->first();
       
    }
    
}
