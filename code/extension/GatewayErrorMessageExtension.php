<?php

use Omnipay\Moneris\Helper;
use Tki\Utility\JsonUtility;

class GatewayErrorMessageExtension extends GatewayMessageExtension
{
 
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
    
    protected $column = 'Data';
    
}
