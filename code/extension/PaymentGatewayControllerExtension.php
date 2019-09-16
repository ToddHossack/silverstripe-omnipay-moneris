<?php

use SilverStripe\Omnipay\GatewayInfo;

class PaymentGatewayControllerExtension extends Extension
{
    
    public function updatePaymentFromRequest($request, $gateway)
    {
        $identifier = trim((string)$request->postVar('response_order_id'));
        if(empty($identifier)) {
            return $this->owner->httpError(400, _t('PaymentExtension.NoResponseOrderId', 'Invalid or missing order number.'));
        }
        try {
            $this->checkGatewayReferer($gateway);
        } catch (\Exception $ex) {
            return $this->owner->httpError(400, $ex->getMessage());
        }

        return \Payment::get()
            ->filter('Identifier', $identifier)
            ->filter('Identifier:not', "")
            ->first();
    }
    
    /**
     * Compares referrer host to gateway endpoint host and throws an exception if there's a mismatch.
     * Not implemented for dev environment, only 'test' and 'live'.
     * @param string $gatewayName
     * @throws Exception
     */
    public function checkGatewayReferer($gatewayName)
    {
        if(Director::isDev()) {
            return;
        }
        if($gatewayName === 'Moneris') {
            $params = GatewayInfo::getConfigSetting('Moneris','parameters');
            $gateway = \Injector::inst()->get('Omnipay\Common\GatewayFactory')->create($gatewayName);
            $gateway->initialize($params);
            $request = $gateway->completePurchase();
            $endpointHost = parse_url($request->getEndpoint(),PHP_URL_HOST);
            $referrerHost = parse_url($_SERVER['HTTP_REFERER'],PHP_URL_HOST);
            if(strcasecmp($referrerHost,$endpointHost) !== 0) {
                throw new Exception(_t('PaymentGatewayControllerExtension.BadReferrer', 'The referring host is invalid'));
            }
        }
        
    }
    
}
