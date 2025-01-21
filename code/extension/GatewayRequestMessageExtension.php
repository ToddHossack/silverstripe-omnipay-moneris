<?php

use Omnipay\Moneris\Helper;
use Tki\Utility\JsonUtility;

class GatewayRequestMessageExtension extends DataExtension
{
 
    private static $db = array(
        'Parameters' => 'Text'
    );
    
    /**
     * Configurable flag whether to save the gateway request parameters
     * @var bool 
     */
    private static $store_parameters = false;
    
    /**
     * Specifies which parameters should be masked using asterisks
     * Default key should not be present, but this will
     * ensure it is dealt with if present.
     * @var array 
     */
    private static $mask = [
        'api_token'
    ];
    
    /**
     * Specifies which parameters should be obfuscated
     * @var array 
     */
    private static $obfuscate = [
        'billing_details'
    ];
    
    
    public function onBeforeWrite()
    {
        if(!empty($this->owner->Parameters) && is_array($this->owner->Parameters)) {
            // Working copy
            $params = $this->owner->Parameters;
            // Unset by default for security purposes
            $this->owner->Parameters = null;
            
            // Configured to store parameters,
            // so we create a secure version 
            if($this->owner->config()->get('store_parameters')) {
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
                        $this->owner->Parameters = JsonUtility::arrayToJson($params);
                    } catch (\Exception $ex) {
                        $errors[] = $ex->getMessage();
                    }
                }
                // Log errors
                if(!empty($errors)) {
                     SS_Log::log('Could not process GatewayRequestMessage->Parameters for saving: '. implode(' ',$errors), SS_Log::ERR);
                }
            }
        }
        
        
    }
    
}
