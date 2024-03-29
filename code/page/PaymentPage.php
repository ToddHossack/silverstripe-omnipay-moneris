<?php

use SilverStripe\Omnipay\PaymentGatewayController;
use SilverStripe\Omnipay\Service\ServiceFactory;

use Tki\Utility\ArrayUtility;
use Tki\Utility\NumberUtility;


class PaymentPage extends Page 
{
    private static $db = [
		'MerchantName' => 'Varchar(100)',
        'MerchantEmail' => 'Varchar(100)',
        'MerchantPhone' => 'Varchar(30)',
        'MerchantWebsite' => 'Varchar(100)',
        'MerchantPhysicalAddress' => 'Text',
        'MerchantPostalAddress' => 'Text',
        'FormDisabled' => 'Boolean',
        'FormDisabledMessage' => 'HTMLText'
	];

    private static $casting = [
        'MerchantPhysicalAddressHTML' => 'HTMLText',
        'MerchantPostalAddressHTML' => 'HTMLText',
    ];
    
    public function getMerchantPhysicalAddressHTML()
    {
        return nl2br(strip_tags($this->MerchantPhysicalAddress));
    }
    
    public function getMerchantPostalAddressHTML()
    {
        return nl2br(strip_tags($this->MerchantPostalAddress));
    }
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $fields->findOrMakeTab('Root.Maintenance',_t('PaymentPage.MaintenanceTab','Maintenance'));

        $fields->addFieldsToTab('Root.Maintenance',[
            CheckboxField::create(
                'FormDisabled',
                _t('PaymentPage.FormDisabled','Disable the Payment Form')
            ),
            HtmlEditorField::create(
                'FormDisabledMessage',
                _t('PaymentPage.FormDisabledMessage','Form Disabled Message')
            )
            ]
        );
        
        $fields->findOrMakeTab('Root.Merchant',_t('PaymentPage.MerchantTab','Merchant Details'));
        
        $fields->addFieldsToTab('Root.Merchant',[
            TextField::create('MerchantName',_t('PaymentPage.MerchantName','Merchant Name'),null,100),
            TextField::create('MerchantEmail',_t('PaymentPage.MerchantEmail','Email'),null,100),
            TextField::create('MerchantPhone',_t('PaymentPage.MerchantName','Phone'),null,30),
            TextField::create('MerchantWebsite',_t('PaymentPage.MerchantWebsite','Website'),null,100),
            TextareaField::create('MerchantPhysicalAddress',_t('PaymentPage.MerchantPhysicalAddress','Physical Address')),
            TextareaField::create('MerchantPostalAddress',_t('PaymentPage.MerchantPostalAddress','Postal Address')),
        ]);
        
        return $fields;
    }
    
    
    
}


class PaymentPage_Controller extends Page_Controller 
{
    /**
     * Session timeout
     * @var int 
     */
    private static $session_timeout = 300;  // 1 hour
    

    private static $allowed_actions = [
        'PaymentForm',
        //'CompletedForm',
        //'StartOverForm',
        'result',
    ];
    
    protected $errors = [];
    
    protected $order;
    
    private static $order_class = 'Order';
    
    public function init() 
    {
		parent::init();
        if($this->request->getVar('start')) {
            $this->clearPaymentSession(get_class($this));
        }
    }
    /*
	|--------------------------------------------------------------------------
	| Actions
	|--------------------------------------------------------------------------
	*/
    
    
    protected function result()
    {
        $viewVars = [
            'Title' => 'Payment Result',
            'Result' => null,
			'PaymentData' => null,
            'OrderData' => null
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
            $this->addError(_t('PaymentPage_Controller.PaymentNotFound','Transaction details not found'));
        } else {
            $viewVars['Payment'] = $payment;
            $viewVars['OrderData'] = $this->resultOrderData($payment);
            $viewVars['Result'] = ArrayData::create($this->resultFromPaymentStatus($payment));
            $viewVars['LastMessage'] = $payment->LastMessage();
        }
        
        $this->addResponseVars($viewVars);
        
        return $this->showResponse($viewVars);
    }
    
    /**
     * Override in sub-classes to customise template variables
     * @param array $vars
     */
    protected function addResponseVars(&$vars)
    {
        
    }
    
    protected function resultOrderData($payment)
    {
        $order = $payment->Order();
        return ($order) ? $order->resultData() : null;
    }
    
    protected function showResponse($vars)
    {
        $vars['PaymentErrors'] = $this->paymentErrors;
        return $this->customise(ArrayData::create($vars));
    }
    
    public function mockgateway()
    {
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
        if($this->dataRecord->FormDisabled) {
            if(!empty($this->dataRecord->FormDisabledMessage)) {
                return $this->dataRecord->dbObject('FormDisabledMessage');
            } else {
                return _t('PaymentPage.FormDisabledMessage','Sorry, the payment form is temporarily unavailable. Please try again in a few hours.');
            }
            
        }
        /*
         * Fields
         */
        $fields = $this->paymentFormFields();
        
        /*
         * Actions
         */
        $actions = $this->paymentFormActions();
        
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
    
    protected function paymentFormActions()
    {
        return FieldList::create(
            FormAction::create("submitPaymentForm")->setTitle("Submit")
        );
    }
    
    /**
     * Override in subclasses to customise
     * @param type $fields
     */
    protected function paymentFormFields()
    {
        return FieldList::create(
            MoneyField::create('Money',_t('PaymentPage_Controller.Money','Payment amount'))
        );
    }

    public function submitPaymentForm($data, Form $form)
    {
        // Create payment
        $payment = $this->createPurchasePayment($data,$form);

        // Init session
        $this->initPaymentSession($payment);
        
        // Gather gateway data
        $gatewayData = $this->gatewayDataForPurchase($payment,$data,$form);
        
        // Use PurchaseService
        $service = ServiceFactory::create()->getService($payment, ServiceFactory::INTENT_PURCHASE);

        // Initiate the gateway purchase
        $response = $service->initiate($gatewayData);

        if(Director::isDev()) {
            $response->getOmnipayResponse()->getRequest()->setTestEndpoint(\Controller::join_links($this->Link(),'mockgateway'));
        }

        return $response->redirectOrRespond();
    }
    
    /**
     * Override in subclasses to customise.
     * @param type $data
     * @param type $form
     */
    protected function createPurchasePayment($data,$form)
    {
        // Create order
        $orderClass = $this->config()->get('order_class',Config::UNINHERITED);
        $this->order = $orderClass::create();
        $form->saveInto($this->order,[
            'FirstName',
            'LastName',
            'Email',
            'Phone',
            'Comments',
            'MailingAddressLine1',
            'MailingAddressLine2',
            'MailingSuburb',
            'MailingCity',
            'MailingState',
            'MailingCountry',
            'MailingPostCode'
        ]);
        $this->order->write();
        
        // Create payment
        $amount = ArrayUtility::data_get($data,'Money.Amount');
        $currency = ArrayUtility::data_get($data,'Money.Currency');
        $formattedAmt = NumberUtility::format_currency($amount,false,'.','');

        $payment = Payment::create()->init('Moneris', $formattedAmt, $currency);
        $payment->OrderID = $this->order->ID;
        $payment->Identifier = $this->order->OrderNumber;
        $payment->SuccessUrl = \Controller::join_links($this->Link(),'result',$payment->Identifier);
        $payment->FailureUrl = \Controller::join_links($this->Link(),'result',$payment->Identifier);

        return $payment;
    }
    
    /**
     * Override in subclasses to gather white listed data for gateway. 
     * @param \Payment $payment
     * @param array $data
     * @param \Form $form
     */
    protected function gatewayDataForPurchase($payment,$data,$form)
    {
        return $this->order->dataForGateway();
    }
    
    /*
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
     * 
     */
    
    public function StartLink()
    {
        return \Controller::join_links($this->Link(),'?start=1');
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
			$this->addError(_t('PaymentPage_Controller.InvalidIdentifier','Invalid payment identifier'));
			return false;
		}
		return true;
    }
    
    protected function validateResponseCode($code)
    {
		// Validate order ID
		if(empty($code) || !is_numeric($code)) {
            $this->addError(_t('PaymentPage_Controller.InvalidResponseCode','Invalid response code'));
			return false;
		}
		return true;
	}
    
    
    /*
	|--------------------------------------------------------------------------
	| Security / permissions
	|--------------------------------------------------------------------------
	*/	
	
    protected function initPaymentSession($payment)
    {
        $sessionTimeout = (int) $this->config()->get('session_timeout');
        
        $sessionData = [
            'payment_identifier' => $payment->Identifier,
            'payment_submit_time' => ($sessionTimeout) ? time() + $sessionTimeout : 0
        ];
        
        Session::set(get_class($this),$sessionData);
    }
	
    protected function clearPaymentSession($name)
    {
        Session::clear(get_class($this));
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
            $this->addError(_t('PaymentPage_Controller.SessionInvalid','Session invalid'));
            return false;
        }
        $expired = (time() - $tstamp) > $sessionTimeout;
        
        if($expired) {
            $this->addError(_t('PaymentPage_Controller.SessionExpired','Sorry, your session has expired.'));
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
    
    protected function resultFromPaymentStatus($payment)
    {
        if(!$payment) {
            return [
                'Code' => null,
                'Title' => _t('PaymentPage.Result_None','No result'),
                'Type' => _t('PaymentPage.ResultType_Error','No result'),
            ];
        }

        switch($payment->Status) {
            case 'Created':
            case 'PendingAuthorization':
            case 'Authorized':
            case 'PendingCreateCard':
            case 'CardCreated':
            case 'PendingPurchase':
            case 'PendingCapture':
                return [
                    'Code' => 'Incomplete',
                    'Title' => _t('PaymentPage.Result_Incomplete','Payment Incomplete'),
                    'Type' =>  _t('PaymentPage.ResultType_Notice','Notice'),
                ];
                break;
            case 'Captured':
                return [
                    'Code' => 'Completed',
                    'Title' => _t('PaymentPage.Result_Completed','Payment Completed'),
                    'Type' =>  _t('PaymentPage.ResultType_Receipt','Receipt'),
                ];
                break;
            case 'PendingRefund':
            case 'Refunded':
            case 'PendingVoid':
            case 'Void':
                $status = strtoupper($payment->Status);
                return [
                    'Code' => $payment->Status,
                    'Title' => _t('Payment.STATUS_'.$status, $payment->Status),
                    'Type' =>  _t('PaymentPage.ResultType_Notice','Notice')
                ];
                break;
        }
    }
    
    protected function addError($msg)
    {
        if(is_null($this->paymentErrors)) {
            $this->paymentErrors = ArrayList::create();
        }
        $this->paymentErrors->push(ArrayData::create([
            'Error' => $msg
        ]));
    }
    
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