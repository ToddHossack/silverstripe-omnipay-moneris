<?php


class PaymentExtension extends DataExtension 
{
 
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
    
    
}
