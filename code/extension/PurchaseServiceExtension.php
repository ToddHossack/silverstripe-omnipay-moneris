<?php

use SilverStripe\Omnipay\GatewayInfo;

class PurchaseServiceExtension extends Extension
{
    
    public function onBeforePurchase($gatewayData)
    {
  
    }
    
    public function onAfterPurchase($request)
    {
        // Store params
        $params = GatewayInfo::getConfigSetting('Moneris','parameters');

        if(isset($params['ps_store_id'])) {
            $request->setPsStoreId($params['ps_store_id']);
        }
        
        if(isset($params['hpp_key'])) {
            $request->setHppKey($params['hpp_key']);
        }
        
    }
    
    public function onAfterCompletePurchase($request)
    {
   
    }
    
    
    
    
}
