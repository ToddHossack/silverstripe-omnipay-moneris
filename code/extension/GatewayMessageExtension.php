<?php

use Omnipay\Moneris\Helper;
use Tki\Utility\JsonUtility;

class GatewayMessageExtension extends DataExtension
{
 
    private static $db = array(
        'Data' => 'Text'
    );
    
    /**
     * Configurable value whether to save the gateway request / response parameters
     * @var bool 
     */
    private static $store_gateway_data = false;
    
    /**
     * Column to store data in. 
     * Specified in subclasses. ie. Parameters for requests, Data for responses / errors.
     * @var string 
     */
    protected $column;
    
    public function onBeforeWrite()
    {
        if(!empty($this->owner->{$this->column}) && is_array($this->owner->{$this->column})) {
            // Working copy
            $params = $this->owner->{$this->column};
            // Unset by default for security purposes
            $this->owner->{$this->column} = null;
            
            // Configured to store parameters,
            // so we create a secure version 
            if($this->owner->config()->get('store_gateway_data')) {
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
                        $this->owner->{$this->column} = JsonUtility::arrayToJson($params);
                    } catch (\Exception $ex) {
                        $errors[] = $ex->getMessage();
                    }
                }
                // Log errors
                if(!empty($errors)) {
                     SS_Log::log('Could not process GatewayRequestMessage->'. $this->column .' for saving: '. implode(' ',$errors), SS_Log::ERR);
                }
            }
        }
        
        
    }
    
}
