<?php

use Omnipay\Moneris\Helper;
use Tki\Utility\JsonUtility;

class GatewayRequestMessageExtension extends GatewayMessageExtension
{
 
    private static $db = array(
        'Parameters' => 'Text'
    );
    
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
    
    protected $column = 'Parameters';
    
    
}
