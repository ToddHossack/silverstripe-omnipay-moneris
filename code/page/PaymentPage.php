<?php

use SilverStripe\Omnipay\PaymentGatewayController;

class PaymentPage extends Page 
{
    
}


class PaymentPage_Controller extends Page_Controller 
{
    /**
     * Session timeout
     * @var int 
     */
    private static $session_timeout = 3600;  // 1 hour
    

    private static $allowed_actions = [
        'PaymentForm',
        'CompletedForm',
        'StartOverForm',
        'complete',
        'incomplete'
    ];
    
    protected $errors = [];

    /*
	|--------------------------------------------------------------------------
	| Actions
	|--------------------------------------------------------------------------
	*/
    
    public function complete()
    {
        echo __METHOD__;
        return $this->doResponse();
    }
    
    public function incomplete()
    {
        echo __METHOD__;
        return $this->doResponse();
    }
    
    protected function doResponse()
    {
        $viewVars = [
			'Payment' => null
		];
        
        // Find and validate identifier
        $identifier = $this->getIdentifierFromRequest();
        if(!$this->validatePaymentIdentifier($identifier)) {
            return $this->showResponse($viewVars);
        }
       
        // Validate session
        if(!$this->validatePaymentSession($identifier)) {
            return $this->showResponse($viewVars);
        }
   
        /*
         * Find payment
         */
        $payment = $this->findPaymentByIdentifier($identifier);
        if(!$payment) {
            $this->errors[] =  _t('PaymentPage_Controller.PaymentNotFound','Transaction details not found');
        } else {
            $viewVars['Payment'] = $payment;
        }
        $this->addResponseVars($viewVars,$payment);
        return $this->showResponse($viewVars);
    }
    
    /**
     * Override in sub-classes to customise template variables
     * @param array $vars
     * @param \Payment $payment
     */
    protected function addResponseVars(&$vars,$payment)
    {
        
    }
    
    protected function showResponse($vars)
    {
        $vars['errors'] = $this->errors;
        return $this->customise(ArrayData::create($vars));
    }
    
    public function mockgateway()
    {
        //var_dump($this->request->postVars());
        
        return [];
    }
    
    /*
	|--------------------------------------------------------------------------
	| Forms
	|--------------------------------------------------------------------------
	*/
    
    /**
     * Payment Form logic. Provide customised form fields, actions, and required fields
     * in subclasses using paymentFormFields, paymentFormActions, and 
     * paymentFormRequired methods respectively.
     * 
     * @return \Form
     */
    public function PaymentForm()
    {
        /*
         * Fields
         */
        $fields = $this->paymentFormFields(FieldList::create());
        
        /*
         * Actions
         */
        $actions = $this->paymentFormActions(FieldList::create());
        
        /*
         * Validation
         */
        $required = $this->paymentFormRequired();
            
        $form = Form::create($this, 'PaymentForm', $fields, $actions, $required);

        return $form;
    }
    
    protected function paymentFormRequired()
    {
        return RequiredFields::create(['Money']);
    }
    
    protected function paymentFormActions(&$fields)
    {
        $fields->push(FormAction::create("submitPaymentForm")->setTitle("Submit"));
    }
    
    /**
     * Override in subclasses to customise
     * @param type $fields
     */
    protected function paymentFormFields(&$fields)
    {
        $moneyField = MoneyField::create('Money',_t('PaymentPage_Controller.Money','Payment amount'));
        $fields->push($moneyField);
        
    }

    public function submitPaymentForm($data, Form $form)
    {
        
        /*
         * Create payment
         */
        $payment = $this->createPaymentFromPaymentForm($data,$form);
        
        // Omnipay data
        $gatewayData = $order->dataForGateway();
        
        // Add Moneris data
        $gatewayData['rvar'] = [
            'dept' => $order->Department,
            'payfor' => $order->Item
        ];
        
        $gatewayData['cust_id'] = $order->CustomerAccount;
        
        $this->initPaymentSession(get_class($this),$data);
        
        // Use PurchaseService
        $service = ServiceFactory::create()->getService($payment, ServiceFactory::INTENT_PURCHASE);

        // Initiate the payment
        $response = $service->initiate($gatewayData);
        //var_dump($response);
        
        return $response->redirectOrRespond();
        
    }
    
    public function CompletedForm()
    {
        $fields = FieldList::create();
        $actions = FieldList::create(
            FormAction::create("completedFormSubmit")->setTitle("Done")
        );
      
        $form = Form::create($this, 'CompletedForm', $fields, $actions, null);

        return $form;
    }
    
    public function StartOverForm()
    {
        $fields = FieldList::create();
        $actions = FieldList::create(
            FormAction::create("startOverFormSubmit")->setTitle("Start again")
        );
      
        $form = Form::create($this, 'StartOverForm', $fields, $actions, null);

        return $form;
    }
    
    /*
	|--------------------------------------------------------------------------
	| Dev
	|--------------------------------------------------------------------------
	*/
    
    public function MockGatewayForm()
    {
        $fields = $this->mockGatewayFields();
       
        $actions = new FieldList(
            FormAction::create('MockGatewayFormSubmit')->setTitle("Submit")
        );

        $actionUrl = PaymentGatewayController::getStaticEndpointUrl('Moneris','complete');

        $form = Form::create($this, 'MockGatewayForm', $fields, $actions, null);
        $form->setFormAction($actionUrl);
        $form->loadDataFrom($this->mockGatewayFormData());
        return $form;
    }
    
    protected function mockGatewayFormData()
    {
        return [
            'response_order_id' => $this->request->postVar('order_id'),
            'charge_total' => $this->request->postVar('charge_total'),
            'cardholder' => implode(' ',[
                $this->request->postVar('bill_first_name'),$this->request->postVar('bill_last_name')
            ]),
            'time_stamp' => date('H:i:s'),
            'date_stamp' => date('Y-m-d')
        ];
    }
    
    protected function mockGatewayFields()
    {
        return FieldList::create(
            TextField::create('response_order_id','Response Order ID', null, 50),
            TextField::create('charge_total','Payment Amount', null, 25),
            ReadonlyField::create('trans_name', 'Transaction Type', 'purchase', 30),
            TextField::create('date_stamp', 'Date',null,30),
            TextField::create('time_stamp', 'Time',null,30),
            TextField::create('cardholder', 'Cardholder' ,null,30),
            DropdownField::create('card', 'Card' ,[
                'M' => 'Mastercard',
                'V' => 'Visa',
            ]),
            DropdownField::create('response_code', 'Response Code' ,[
                '' => 'Transaction not sent for authorisation',
                40 => 'Transaction approved',
                60 => 'Transaction declined'
            ],40),
            DropdownField::create('result', 'Result' ,[
                '' => 'No result code',
                0 => 'Declined or incomplete',
                1 => 'Approved'
            ],1),
            DropdownField::create('message', 'Result' ,[
                'APPROVED' => 'APPROVED',
                'DECLINED' => 'DECLINED',
                'CANCELLED' => 'CANCELLED'
            ],1),
            NumericField::create('iso_code', 'ISO Code' ,null,2),
            TextField::create('bank_transaction_id', 'Reference Number' ,null,18),
            TextField::create('bank_approval_code', 'Authorization Code' ,null,30),
            TextField::create('transactionKey', 'Transaction Key' ,null,100)
        );
    }
    
    /*
	|--------------------------------------------------------------------------
	| Validation
	|--------------------------------------------------------------------------
	*/
 
    protected function validatePaymentIdentifier($identifier)
    {
        // Validate order ID
		if(empty($identifier) || !preg_match('/^(([1-9][0-9]*)-[0-9]{12,12})(-[a-z0-9]+)?$/',$identifier)) {
			$this->errors[] =  _t('PaymentPage_Controller.InvalidIdentifier','Invalid payment identifier');
			return false;
		}
		return true;
    }
    
    protected function validateResponseCode($code)
    {
		// Validate order ID
		if(empty($code) || !is_numeric($code)) {
            $this->errors[] =  _t('PaymentPage_Controller.InvalidResponseCode','Invalid response code');
			return false;
		}
		return true;
	}
    
    
    /*
	|--------------------------------------------------------------------------
	| Security / permissions
	|--------------------------------------------------------------------------
	*/	
	
    protected function initPaymentSession($name,$data=[])
    {
        $sessionTimeout = (int) $this->config()->get('session_timeout');
        
        $sessionData = [
            //'payment_identifier' => 
            'payment_submit_time' => ($sessionTimeout) ? time() + $sessionTimeout : 0
        ];
        
        Session::set($name,$sessionData);
    }
	
    /**
     * Checks whether user's payment session has expired
     * @param type $order
     * @return type
     */
	protected function validatePaymentSession()
    {
        $sessionTimeout = (int) $this->config()->get('session_timeout');
        // Zero means no time out
        if(!$sessionTimeout) {
            return true;
        }
        // Compare time against expiry time
        $tstamp = (int) $this->sessionGet(get_class($this),'payment_submit_time');
        
        if(!$tstamp) {
            $this->errors[] =  _t('PaymentPage_Controller.SessionInvalid','Session invalid');
            return false;
        }
        $expired = (time() - $tstamp) > $sessionTimeout;
        
        if($expired) {
            $this->errors[] =  _t('PaymentPage_Controller.SessionExpired','Session expired');
            return false;
        }
        
        return true;
	}
    
    
    protected function sessionGet($key,$path,$default=null)
    {
        $sessionStorage = Session::get($key);
        return ArrayUtility::data_get($sessionStorage,$path,$default);
    }
    
     /*
	|--------------------------------------------------------------------------
	| Helpers
	|--------------------------------------------------------------------------
	*/	
    
    protected function getIdentifierFromRequest()
    {
        $requestIdentifier = $this->request->param('ID');
        preg_match('/^(([1-9][0-9]*)-[0-9]{12,12})(-[a-z0-9]+)?$/',$requestIdentifier,$matches);
        return isset($matches[1]) ? $matches[1] : null;
    }
    
    protected function findPaymentByIdentifier($identifier)
    {
        return \Payment::get()
            ->filter('Identifier', $identifier)
            ->filter('Identifier:not', "")
            ->first();
    }
}