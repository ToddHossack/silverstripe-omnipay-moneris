<?php


class PaymentExtension extends DataExtension implements PermissionProvider
{
 
    private static $db = array(
        'GatewayTicket' => 'Varchar(100)',
    );
    
    public function TranslatedStatus()
    {
        $status = strtoupper((string) $this->owner->Status);
        return _t('Payment.STATUS_'.$status,$this->owner->Status);
    }
    
    /**
     * Finds the last message for the payment, excluding verification requests / responses
     * @return \PaymentMessage
     */
    public function LastMessage()
    {
        return $this->owner->Messages()
            ->exclude([
                'ClassName:PartialMatch' => 'Verification'
            ])
            ->sort('ID','DESC')
            ->first();
    }
    
    /**
     * Finds the last error message for the payment
     * @return \PaymentMessage
     */
    public function LastError()
    {
        return $this->owner->Messages()
            ->filter([
                'ClassName:PartialMatch' => 'Error'
            ])
            ->sort('ID','DESC')
            ->first();
    }
    
    /**
     * Provide payment related permissions. The permissions are:
     * * `REFUND_PAYMENTS` can refund payments
     * * `CAPTURE_PAYMENTS` can capture payments
     * * `VOID_PAYMENTS` can void payments
     * @inheritdoc
     * @return array
     */
    public function providePermissions()
    {
        return array(
            'Payment_VIEW' => array(
                'name' => _t('PaymentExtension.Payment_VIEW', 'View payments'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 210
            ),
            'Payment_EDIT' => array(
                'name' => _t('PaymentExtension.Payment_EDIT', 'Edit payments'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 215
            ),
            'Payment_DELETE' => array(
                'name' => _t('PaymentExtension.Payment_DELETE', 'Delete payments'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 220
            ),
        );
    }
    
    
    public function canView($member = null)
    {
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
	
		return Permission::check('Payment_VIEW', 'any', $member);
	}
    
    public function canEdit($member = null) 
    {
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
        // Pseudo editing
		return Permission::check('Payment_EDIT', 'any', $member);
	}
    
    public function canDelete($member = null) 
    {
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
		return Permission::check('Payment_DELETE', 'any', $member);
	}
    
}
