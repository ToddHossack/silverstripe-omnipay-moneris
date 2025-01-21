<?php

use Omnipay\Moneris\Helper;
use Tki\Utility\JsonUtility;

class GatewayResponseMessageExtension extends DataExtension
{
 
    private static $db = array(
        'Data' => 'Text'
    );
    
    /**
     * Configurable value whether to save the gateway request / response parameters
     * @var bool 
     */
    private static $store_data = false;
    
    /**
     * Specifies which data should be masked
     * using asterisks
     * @var array 
     */
    private static $mask = [];
    
    /**
     * Specifies which data should be obfuscated
     * @var array 
     */
    private static $obfuscate = [
        'response.request.billing',
        'response.request.cc'
    ];
    
    
    public function onBeforeWrite()
    {
        if(!empty($this->owner->Data) && is_array($this->owner->Data)) {
            // Working copy
            $params = $this->owner->Data;
            // Unset by default for security purposes
            $this->owner->Data = null;
            
            // Configured to store parameters,
            // so we create a secure version 
            if($this->owner->config()->get('store_data')) {
                $errors = [];
                
                // Exclude objects
                array_walk_recursive($params, function(&$v) {
                    if(is_object($v)) {
                        $v = '(removed)';
                    }
                });
                
                // Mask parameters
                $masked = $this->owner->config()->get('mask');
                if(!empty($masked)) {
                    $resultErrors = Helper::data_modify_multiple($params,$masked,'\Omnipay\Moneris\Helper::maskValue');
                    if(is_array($resultErrors) && !empty($resultErrors)) {
                        $errors = array_merge($errors,$resultErrors);
                    }
                }
                // Obfuscate parameters 
                $obfuscated = $this->owner->config()->get('obfuscate');
                if(!empty($obfuscated)) {
                    $resultErrors = Helper::data_modify_multiple($params,$obfuscated,'\Omnipay\Moneris\Helper::obfuscateValueWithTypes');
                    if(is_array($resultErrors) && !empty($resultErrors)) {
                        $errors = array_merge($errors,$resultErrors);
                    }
                } 
               
                // Only store data if no errors
                if(!$errors) {
                    try {
                        $this->owner->Data = JsonUtility::arrayToJson($params);
                    } catch (\Exception $ex) {
                        $errors[] = $ex->getMessage();
                    }
                }
                // Log errors
                if(!empty($errors)) {
                     SS_Log::log('Could not process GatewayResponseMessage->Data for saving: '. implode(' ',$errors), SS_Log::ERR);
                }
            }
        }
    }
    
}
