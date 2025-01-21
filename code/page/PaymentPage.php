<?php

use SilverStripe\Omnipay\PaymentGatewayController;
use SilverStripe\Omnipay\Service\ServiceFactory;
use Omnipay\Moneris\Message\PreloadRequest;
use Omnipay\Moneris\Message\ReceiptRequest;
use Omnipay\Moneris\Config as MonerisConfig;
use Omnipay\Moneris\Helper;

use SilverStripe\Omnipay\GatewayInfo;

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
        'FormDisabledMessage' => 'HTMLText',
        'AdminEmailRecipients' => 'Varchar(255)',
        'EmailTitle' => 'Varchar(100)',
        'SendCustomerEmail' => 'Boolean'
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
        
        // Maintenance tab
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
        
        // Merchant Details tab
        $fields->findOrMakeTab('Root.Merchant',_t('PaymentPage.MerchantTab','Merchant Details'));
        
        $fields->addFieldsToTab('Root.Merchant',[
            TextField::create('MerchantName',_t('PaymentPage.MerchantName','Merchant Name'),null,100),
            TextField::create('MerchantEmail',_t('PaymentPage.MerchantEmail','Email'),null,100),
            TextField::create('MerchantPhone',_t('PaymentPage.MerchantName','Phone'),null,30),
            TextField::create('MerchantWebsite',_t('PaymentPage.MerchantWebsite','Website'),null,100),
            TextareaField::create('MerchantPhysicalAddress',_t('PaymentPage.MerchantPhysicalAddress','Physical Address')),
            TextareaField::create('MerchantPostalAddress',_t('PaymentPage.MerchantPostalAddress','Postal Address')),
        ]);
        
        $fields->findOrMakeTab('Root.Merchant',_t('PaymentPage.MerchantTab','Merchant Details'));
        
        $fields->findOrMakeTab('Root.Email',_t('PaymentPage.EmailTab','Email'));
        
        $fields->addFieldsToTab('Root.Email',[
            TextField::create('AdminEmailRecipients',_t('PaymentPage.AdminEmailRecipients','Admin Recipient(s) For Receipts'),null,255)
                ->setDescription(_t('PaymentPage.AdminEmailRecipientsDesc','Use comma separated values for multiple recipients.')),
            TextField::create('EmailTitle',_t('PaymentPage.EmailTitle','Email Title'),null,100),
            CheckboxField::create('SendCustomerEmail',_t('PaymentPage.SendCustomerEmail','Send Receipt To Customer')),
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
    private static $session_timeout = 600;  // 10 minutes

    private static $allowed_actions = [
        'PaymentForm',
        'pay',
        'cancel',
        'result'
    ];
    
    protected $errors = [];
    
    protected $order;
    
    private static $order_class = 'Order';
    
    private static $fieldsToParameterMap = [
        // Order
        'FirstName' => 'contact_details.first_name',
        'LastName' => 'contact_details.last_name',
        'Email' => 'contact_details.email',
        'Phone' => 'contact_details.phone',
        'OrderNumber' => 'contact_details.order_no',
        'BillingAddressLine1' => 'billing_details.address_1',
		'BillingAddressLine2' => 'billing_details.address_2',
        'BillingCity' => 'billing_details.city',
        'BillingState' => 'billing_details.province',
        'BillingCountry' => 'billing_details.country',
        'BillingPostCode' => 'billing_details.postal_code',
        'MailingAddressLine1' => 'shipping_details.address_1',
		'MailingAddressLine2' => 'shipping_details.address_2',
        'MailingCity' => 'shipping_details.city',
        'MailingState' => 'shipping_details.province',
        'MailingCountry' => 'shipping_details.country',
        'MailingPostCode' => 'shipping_details.postal_code',
        // Payment
        'Money[Amount]' => 'txn_total'
    ];
    
    public function init() 
    {
		parent::init();
        if($this->request->getVar('start')) {
            $this->clearPaymentSession(get_class($this));
        } else {
            // Get current errors from session
            $this->paymentErrors = ArrayList::create($this->sessionGet(get_class($this),'paymentErrors',[]));
        }
    }
   
    /*
	|--------------------------------------------------------------------------
	| Form
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
        $validator = $this->paymentFormValidator();
        $rules = $this->paymentFormValidatorRules();
        if(!empty($rules) && ($validator instanceof PaymentFormValidator)) {
            $validator->setRules($rules);
        }
        
        $form = Form::create($this, 'PaymentForm', $fields, $actions, $validator);

        return $form;
    }
    
    protected function paymentFormValidator()
    {
        return PaymentFormValidator::create(['LastName','Email','Money[Amount]']);
    }
    
    protected function paymentFormValidatorRules()
    {
        // Get preload request parameter config
        $parameterCfg = PreloadRequest::condensedParameterConfig();
        // Field to parameter map
        $fieldsMap = $this->config()->get('fieldsToParameterMap');
        // Make rules based on parameter config
        $rules = [];
        if(is_array($fieldsMap)) {
            foreach($fieldsMap as $field => $path) {
                $rules[$field] = ArrayUtility::data_get($parameterCfg,$path,[]);
            }
        }
        return $rules;
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
        $fields = FieldList::create(array_merge(
            $this->contactDetailsFormFields(),
            $this->billingAddressFormFields()
        ));
        
        $fields->push(CheckboxField::create('ShippingAddressDifferent',_t('Order.ShippingAddressDifferent','Mailing address is different from billing address.')));
        
        // Shipping address
        foreach($this->shippingAddressFormFields() as $field) {
            $fields->push($field);
        };
        
        $fields->push(MoneyField::create('Money',_t('PaymentPage_Controller.Money','Payment amount')));
        
        return $fields;
    }
    
    protected function contactDetailsFormFields()
    {
        return [
            HeaderField::create('ContactDetailsHeading', _t('Order.ContactDetailsHeading','Contact Details'),3),
            TextField::create('FirstName', _t('Order.FirstName','First Name'), null, 30),
            TextField::create('LastName', _t('Order.LastName','Last Name / Business Name'), null, 30)->addExtraClass('requiredField'),
            EmailField::create('Email', _t('Order.Email','Email'),null,30)->addExtraClass('requiredField'),
            TextField::create('Phone', _t('Order.Phone','Phone'),null,20)->addExtraClass('requiredField')
        ];
    }
    
    protected function billingAddressFormFields()
    {
        return [
            HeaderField::create('BillingAddressHeading',_t('Order.BillingAddressHeading','Billing Address'),3),
			TextField::create('BillingAddressLine1',_t('Order.BillingAddressLine1','Address line 1'),null,50)->addExtraClass('requiredField'),
			TextField::create('BillingAddressLine2',_t('Order.BillingAddressLine2','Address line 2'),null,50),
			TextField::create('BillingCity',_t('Order.BillingCity','City'),null,50)->addExtraClass('requiredField'),
			DropdownField::create('BillingState',_t('Order.BillingState','Province'),$this->provinceList())->setEmptyString('Select province...'),
            DropdownField::create('BillingCountry',_t('Order.BillingCountry','Country'),$this->countryList())->setEmptyString('Select country...'),
			TextField::create('BillingPostCode',_t('Order.BillingPostCode','Postal Code'),null,20)->addExtraClass('requiredField')
        ];
    }
    
    protected function shippingAddressFormFields()
    {
        return FieldGroup::create([
            HeaderField::create('MailingAddressHeading',_t('Order.MailingAddressHeading','Mailing address'),3),
			TextField::create('MailingAddressLine1',_t('Order.MailingAddressLine1','Address line 1'),null,50),
			TextField::create('MailingAddressLine2',_t('Order.MailingAddressLine2','Address line 2'),null,50),
			TextField::create('MailingCity',_t('Order.MailingCity','City'),null,50),
			DropdownField::create('MailingState','Province',$this->provinceList())->setEmptyString('Select province...'),
            DropdownField::create('MailingCountry',_t('Order.MailingCountry','Country'),$this->countryList())->setEmptyString('Select country...'),
			TextField::create('MailingPostCode',_t('Order.MailingPostCode','Postal Code'),null,20)
        ])->setName('MailingAddressFields');
    }
    
    protected function countryList()
    {
        return [
            'CA' => 'Canada',	
            //'NZ' => 'New Zealand'
        ];
    }
    
    protected function provinceList()
    {
        return [
            'AB' => 'Alberta',	
            'BC' => 'British Columbia',
            'MB' => 'Manitoba',
            'NB' => 'New Brunswick',
            'NL' => 'Newfoundland and Labrador',
            'NT' => 'Northwest Territories',
            'NS' => 'Nova Scotia',
            'NU' => 'Nunavut',
            'ON' => 'Ontario',	
            'PE' => 'Prince Edward Island',
            'QC' => 'Quebec',
            'SK' => 'Saskatchewan',
            'YT' => 'Yukon'
        ];
    }
    
    /**
     * Handles submission of payment form
     * @param array $data
     * @param Form $form
     * @return \SS_HTTPResponse
     */
    public function submitPaymentForm($data, Form $form)
    {
        // Init payment errors
        $this->paymentErrors = ArrayList::create();
        
        // Config
        $orderClass = $this->config()->get('order_class',Config::UNINHERITED);
        $persistBilling = Config::inst()->get($orderClass, 'persist_billing_address',Config::INHERITED);
        $billingAddressFields = Config::inst()->get($orderClass, 'billing_address_fields',Config::FIRST_SET);
        
        // Create order and payment
        $this->order = $orderClass::create();
        $payment = $this->createPurchasePayment($data,$form,$persistBilling,$billingAddressFields);

        // Gather gateway data
        $gatewayData = $this->gatewayDataForPurchase($payment,$form,$persistBilling,$billingAddressFields);

        // Init session
        $sessionData = $this->initSession([
            'payment_identifier' => $payment->Identifier,
            'billing_details' => $gatewayData['billing_details']
        ]);
        
        /*
         * Gateway request
         */
        // Mock
        $params = GatewayInfo::getParameters('Moneris');
        if(isset($params['mock']) && $params['mock'] === true) {
            $payment->GatewayTicket = 'MockGatewayTicket1234567890987654321012345';
            PreloadRequest::setMockResponseData($this->getMockPreloadResponseData($payment));
        }
        
        /*
         * Gateway request / response
         */
        // Check for errors
        try {
            // Use PurchaseService
            $service = ServiceFactory::create()->getService($payment, ServiceFactory::INTENT_PURCHASE);

            // Initiate the gateway purchase
            $response = $service->initiate($gatewayData);
            $this->checkResponseForErrors($response,$payment);
            // Get response data
            $omnipayResponse = ($response) ? $response->getOmnipayResponse() : null;
            /*if($_SERVER['REMOTE_ADDR'] === '') {
                Helper::debug($omnipayResponse ? $omnipayResponse->getData() : null); exit;
            }*/
            $this->checkResponseForErrors($omnipayResponse);
            
            // Save gateway ticket
            if($omnipayResponse) {
                $responseData = $omnipayResponse->getData();
                $payment->GatewayTicket = ArrayUtility::data_get($responseData,'response.ticket');
                $payment->write();
            }
            
        } catch (\Exception $ex) {
            $this->addError($ex->getMessage());
        }
        
        // Save errors to session for redirect
        if($this->paymentErrors->count()) {
            $sessionData['paymentErrors'] = $this->paymentErrors->toArray();
        }

        Session::set(get_class($this),$sessionData);
        
        return $this->redirect(\Controller::join_links($this->Link(),'pay'));
    }
    
    protected function billingDetails($payment,$form,$persistBilling,$billingAddressFields)
    {
        $data = [];
        if(empty($billingAddressFields) || !is_array($billingAddressFields)) {
            return $data;
        }
        
        $fieldsMap = $this->config()->get('fieldsToParameterMap');
        $fieldList = $form->Fields();
        
        foreach($billingAddressFields as $fieldName) {
            $parameter = isset($fieldsMap[$fieldName]) ? str_replace('billing_details.','',$fieldsMap[$fieldName]) : null;
            if(!$parameter) {
                continue;
            }
            // Persisted - get from payment
            if($persistBilling) {
                $data[$parameter] = $payment->{$fieldName};
            }
            // Not persisted - get from form
            else {
                $dataField = $fieldList->dataFieldByName($fieldName);
                if($dataField) {
                    $data[$parameter] = $dataField->Value();
                }
            }
        }
        
        return $data;
    }
    
    protected function useBillingAddressAsMailingAddress($form) 
    {
        $shippingAddressDifferentField = $form->Fields()->fieldByName('ShippingAddressDifferent');
        return !($shippingAddressDifferentField && intval($shippingAddressDifferentField->Value()));
    }
    
    protected function checkResponseForErrors($response,$payment=null,$checkServiceResponse=true)
    {
        if(!$response) {
            $this->addError(_t('PaymentPage_Controller.NoGatewayResponse','No response from gateway.'));
        }
        // Error message
        elseif($response instanceof \Omnipay\Moneris\Message\AbstractResponse) {
            $error = $response->getError();
            if(!empty($error)) {
                $this->addError($error);
            }
        }
        // Error flag - try to find last error message related to payment
        elseif($response instanceof \SilverStripe\Omnipay\Service\ServiceResponse && $checkServiceResponse) {
            if($response->isError()) {
                $lastError = $payment->LastError();
                if($lastError) {
                    $this->addError($lastError->Message ?: $lastError->ClassName);
                }
            }
        }
    }
    
    /*
	|--------------------------------------------------------------------------
	| Actions
	|--------------------------------------------------------------------------
	*/
    
    public function pay()
    {
        $viewVars = [
            'Title' => 'Make Payment',
            'Result' => null,
			'PaymentData' => null,
            'Ticket' => null
		];
        
        /*
         * Find payment
         */
        $payment = $this->findPaymentUsingSession();
        if(!$payment) {
            return $this->showResponse($viewVars);
        }

        /* 
         * View vars
         */
        // Get ticket
        $viewVars['GatewayTicket'] = $payment->GatewayTicket;
        if(empty($payment->GatewayTicket)) {
            $this->addError(_t('PaymentPage_Controller.GatewayTicketNotFound','Ticket for payment gateway not found.'));
            return $this->showResponse($viewVars);
        }
        
        // Set environment mode
        $params = GatewayInfo::getParameters('Moneris');
        $env = isset($params['environment']) ? $params['environment'] : 'qa';
        $viewVars['GatewayMode'] = $env;
        
        // URLs
        $viewVars['FormUrl'] = \Director::absoluteURL($this->Link());
        $viewVars['ResultUrl'] = \Director::absoluteURL(\Controller::join_links($this->Link(),'result'));
        $viewVars['CancelUrl'] = \Director::absoluteURL(\Controller::join_links($this->Link(),'cancel'));
        
        // JS response codes
        $viewVars['ResponseCodes'] = json_encode(MonerisConfig::getCallbackResponseCodes(),JSON_HEX_APOS | JSON_HEX_QUOT);
        
        // JS Src
        $jsSrc = MonerisConfig::getJsSrcByEnvironment($env);
        if(!$jsSrc) {
            $this->addError(_t('PaymentPage_Controller.JsUrlNotFound','URL for gateway javascript not found.'));
        } else {
            if(!$this->paymentErrors->count()) {
                Requirements::clear();
                Requirements::set_write_js_to_body(false);
                Requirements::javascript($jsSrc);
                // Mock
                if(isset($params['mock']) && $params['mock'] === true) {
                    $viewVars['MockResponse'] = $this->getMockPayResponseData($payment);
                }
            }
        }

        return $this->showResponse($viewVars);
    }
    
    public function cancel()
    {
        $viewVars = [
            'Title' => 'Payment Result',
            'Result' => null,
			'PaymentData' => null,
            'ContactDetails' => null,
            'OrderData' => null
		];
        
        /*
         * Find payment
         */
        $payment = $this->findPaymentUsingSession();
        
        if(!$payment) {
            return $this->showResponse($viewVars);
        }
        
        /*
         * Cancel payment
         */
        // Use PurchaseService
        $service = ServiceFactory::create()->getService($payment, ServiceFactory::INTENT_PURCHASE);
        $response = $service->cancel();
        
        /*
         * View variables
         */
        try {
            $this->addResultViewVars($viewVars,$payment);
            
            $this->extend('cancelViewVars', $viewVars, $payment);
            
        } catch (\Exception $ex) {
            $this->addError($ex->getMessage());
        }
        
        return $this->showResponse($viewVars);
    }
    
    
    public function result()
    {
        $viewVars = [
            'Title' => 'Payment Result',
            'Result' => null,
			'PaymentData' => null,
            'OrderData' => null
		];
        
        /*
         * Find payment
         */
        $payment = $this->findPaymentUsingSession();
        
        if(!$payment) {
            return $this->showResponse($viewVars);
        }
      
        /*
         * Gateway request
         */
        $pending = ($payment->Status === 'PendingPurchase');
        $successful = null;
        
        try {
            // Use PurchaseService
            $service = ServiceFactory::create()->getService($payment, ServiceFactory::INTENT_PURCHASE);

            // Mock
            $params = GatewayInfo::getParameters('Moneris');
            if(isset($params['mock']) && $params['mock'] === true) {
                ReceiptRequest::setMockResponseData($this->getMockReceiptResponseData($payment));
            }
            
            // Receipt request (using complete method in service)
            $response = $service->complete([
                'ticket' => $payment->GatewayTicket
            ],true);

            /*
             * Handle gateway response
             */
            // Check for errors
            $this->checkResponseForErrors($response,$payment,false);
            $omnipayResponse = $response->getOmnipayResponse();
            if($pending) {
                $this->checkResponseForErrors($omnipayResponse,$payment,false);
            }
            if($omnipayResponse) {
                $successful = $omnipayResponse->isSuccessful();
            }
            
        } catch (\Exception $ex) {
            $this->addError($ex->getMessage());
        }
        
        /*
         * View variables
         */
        $this->addResultViewVars($viewVars,$payment);
        
        $this->extend('resultViewVars', $viewVars, $payment);
        
        /*
         * Send email
         */
        if($pending && $successful) {
            $this->sendReceipt($viewVars,$payment);
        }
        
        return $this->showResponse($viewVars);
    }
    
    protected function sendReceipt($viewVars,$payment)
    {
        $viewVars = array_merge($viewVars,[
            'MerchantName' => $this->dataRecord->MerchantName,
            'MerchantEmail' => $this->dataRecord->MerchantEmail,
            'MerchantPhone' => $this->dataRecord->MerchantPhone,
            'MerchantWebsite' => $this->dataRecord->MerchantWebsite,
            'MerchantPhysicalAddressHTML' => $this->dataRecord->MerchantPhysicalAddressHTML,
            'MerchantPostalAddressHTML' => $this->dataRecord->MerchantPostalAddressHTML,
            'PaymentUrl' => str_replace('https://','',$this->dataRecord->AbsoluteLink())
        ]);
        
        // Email title / subject
        $viewVars['EmailTitle'] = trim($this->dataRecord->EmailTitle);
        if(empty($viewVars['EmailTitle'])) {
            $viewVars['EmailTitle'] = 'Payment Receipt';
        }
        $viewVars['EmailTitle'] .= ' (#'. $payment->Identifier .')';
        
        $emailVars = ArrayData::create($viewVars);
        
        // From
        $from = Config::inst()->get('Email', 'admin_email');
        /*
         * Send admin email
         */
        if(!empty($this->dataRecord->AdminEmailRecipients)) {
            $email = new Email();
            $email
                ->setFrom($from)
                ->setTo($this->dataRecord->AdminEmailRecipients)
                ->setSubject($viewVars['EmailTitle'])
                ->setTemplate('PaymentResultEmail')
                ->populateTemplate($emailVars);

            $email->send();
        }
        
        /*
         * Send customer email
         */
        if(!empty($this->dataRecord->SendCustomerEmail)) {
            if($this->order && !empty($this->order->Email)) {
                $email = new Email();
                $email
                    ->setFrom($from)
                    ->setTo($this->order->Email)
                    ->setSubject($viewVars['EmailTitle'])
                    ->setTemplate('PaymentResultEmail')
                    ->populateTemplate($emailVars);

                $email->send();
            }
        }
    }
    
    /**
     * Adds base payment / order view variables
     * @param array $vars
     */
    protected function addResultViewVars(&$viewVars,$payment)
    {
        $this->order = $payment->Order();
        
        $viewVars['Payment'] = $payment;
        $viewVars['Result'] = ArrayData::create($this->resultFromPaymentStatus($payment));
        $viewVars['LastMessage'] = $payment->LastMessage();
        
        $resultData = ($this->order) ? $this->order->resultData() : null;
        
        if(!empty($resultData)) {
            $viewVars['ContactDetails'] = $resultData['ContactDetails'];
            $viewVars['OrderData'] = $resultData['OrderData'];
            $viewVars['MailingAddress'] = $resultData['MailingAddress'];
        }
    }
    
    
    protected function showResponse($vars)
    {
        $vars['PaymentErrors'] = $this->paymentErrors;
        return $this->customise(ArrayData::create($vars));
    }
    
    /*
	|--------------------------------------------------------------------------
	| Order / payment
	|--------------------------------------------------------------------------
	*/
    
    /**
     * Creates order and payment
     * @param array $data
     * @param Form $form
     * @return Payment
     */
    protected function createPurchasePayment($data,Form $form,$persistBilling,$billingAddressFields)
    {
        // Base fields to save
        $fieldNames = [
            'FirstName',
            'LastName',
            'Email',
            'Phone',
            'Comments',
            'MailingAddressLine1',
            'MailingAddressLine2',
            'MailingCity',
            'MailingState',
            'MailingCountry',
            'MailingPostCode'
        ];
        
        // Persist billing address if configured
        if($persistBilling && !empty($billingAddressFields) && is_array($billingAddressFields)) {
            $fieldNames = array_merge($fieldNames,$billingAddressFields);
        }
        
        $form->saveInto($this->order,$fieldNames);
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
    protected function gatewayDataForPurchase($payment,$form,$persistBilling,$billingAddressFields)
    {
        
        $data = [
            // Order data
            'order_no' => $this->order->getField('OrderNumber'),
            'contact_details' => [
                'first_name' => $this->order->getField('FirstName'),
                'last_name' => $this->order->getField('LastName'),
                'email' => $this->order->getField('Email'),
                'phone' => $this->order->getField('Phone')
            ],
            'billing_details' => $this->billingDetails($payment,$form,$persistBilling,$billingAddressFields),
            'shipping_details' => [
                'address_1' => $this->order->getField('MailingAddressLine1'),
                'address_2' => $this->order->getField('MailingAddressLine2'),
                'city' => $this->order->getField('MailingCity'),
                'province' => $this->order->getField('MailingState'),
                'country' => $this->order->getField('MailingCountry'),
                'postal_code' => $this->order->getField('MailingPostCode')
            ],
             // Payment data
            'txn_total' => Helper::formatFloat((float)$payment->getAmount())
        ];
        
        return $data;
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
	| Session handling
	|--------------------------------------------------------------------------
	*/	
	
    protected function initSession($data)
    {
        $sessionData = (array) $data;
        
        $sessionTimeout = (int) $this->config()->get('session_timeout');
        $sessionData['payment_submit_time'] = ($sessionTimeout) ? time() + $sessionTimeout : 0;
     
        Session::set(get_class($this),$sessionData);
        
        return $sessionData;
    }
	
    protected function clearPaymentSession($name)
    {
        Session::clear(get_class($this));
    }
    
    /**
     * Checks whether user's payment session has expired
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
                return [
                    'Code' => $payment->Status,
                    'Title' => _t('Payment.STATUS_'.strtoupper($payment->Status), $payment->Status),
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
        if(is_array($msg)) {
            return $this->addErrorArray($msg);
        }
        $this->paymentErrors->push(ArrayData::create([
            'Error' => $msg
        ]));
    }
    
    protected function addErrorArray($arr)
    {
        if(is_array($arr)) {
            foreach($arr as $field => $v) {
                $msg = [];
                if(is_string($field) && strlen($field)) {
                    $msg = $field .': ';
                }
                if(is_string($v)) {
                    $msg .= $v;
                }
                elseif(is_array($v)) {
                    $msg .= implode("\n",$v);
                }
                $this->paymentErrors->push(ArrayData::create([
                    'Error' => $msg
                ]));
            }
        }
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
    
    protected function findPaymentUsingSession() 
    {
        // Validate session
        if(!$this->validatePaymentSession()) {
            return null;
        }

        // Find and validate identifier
        $identifier = $this->sessionGet(get_class($this),'payment_identifier');

        if(!$this->validatePaymentIdentifier($identifier)) {
            return null;
        }
        
        // Find payment
        $payment = $this->findPaymentByIdentifier($identifier);
        
        if(!$payment) {
            $this->addError(_t('PaymentPage_Controller.PaymentNotFound','Transaction details not found'));
            return null;
        }
        
        return $payment;
    } 
    
    /*
	|--------------------------------------------------------------------------
	| Dev
	|--------------------------------------------------------------------------
	*/
    
    protected function getMockPreloadResponseData($payment)
    {
        $data = [
			"response" => [
                "success" => "”true”",
                "ticket" => "$payment->GatewayTicket"
            ]
		];
        return $data;
    }
    
    protected function getMockPayResponseData($payment)
    {
        $response = [
			"handler" => "page_loaded",
			"ticket" => $payment->GatewayTicket,
			"response_code" => "001"
		];
        return json_encode($response,JSON_HEX_APOS | JSON_HEX_QUOT);
    }
    
    protected function getMockReceiptResponseData($payment)
    {
        $order = $this->order ?: $payment->Order();
        $data = [
            'response' => [
                'success' => 'true',
                'request' => [
                    'txn_total' => "'". $payment->getAmount() ."'",
                    'cart' => [
                        'items' => [
                            '0' => [
                                'product_code' => 'Misc',
                                'description' => 'Comments: Mock Test'
                            ]
                        ],
                        'subtotal' => "'". $payment->getAmount() ."'",
                    ],
                    'cust_info' => [
                        'first_name' => ($order) ? $order->getField('FirstName') : '',
                        'last_name' => ($order) ? $order->getField('LastName') : '',
                        'phone' => ($order) ? $order->getField('Phone') : '',
                        'email' => ($order) ? $order->getField('Email') : '',
                    ],
                    'shipping' => [
                        'address_1' => ($order) ? $order->getField('MailingAddressLine1') : '',
                        'address_2' => ($order) ? $order->getField('MailingAddressLine2') : '',
                        'city' => ($order) ? $order->getField('MailingCity') : '',
                        'country' => ($order) ? $order->getField('MailingCountry') : '',
                        'province' => ($order) ? $order->getField('MailingState') : '',
                        'postal_code' => ($order) ? $order->getField('MailingPostCode') : '',
                    ],
                    'billing' => null,
                    'shipping_amount' => '0.00',
                    'cc_total' => "'". $payment->getAmount() ."'",
                    'pay_by_token' => '0',
                    'cc' => [
                        'first6last4' => '4242424242',
                        'expiry' => '1124',
                        'cardholder' => ($order) ? implode(' ',[$order->getField('FirstName'),$order->getField('LastName')]) : '',
                    ],
                    'ticket' => $payment->GatewayTicket,
                    'cust_id' => null,
                    'dynamic_descriptor' => null,
                    'order_no' => $payment->Identifier,
                    'eci' => 7
                ],

                'receipt' => [
                    'result' => 'd',
                    'order_no' => $payment->Identifier,
                    'cc' => [
                        'fraud' => [
                            'cvd' => [
                                'decision_origin' => 'Moneris',
                                'result' => 1,
                                'condition' => 0,
                                'status' => 'success',
                                'code' => '1M',
                                'details' => null
                            ],
                            'avs' => [
                                'decision_origin' => 'Moneris',
                                'result' => 3,
                                'condition' => 0,
                                'status' => 'disabled',
                                'code' => null,
                                'details' => null
                            ],
                            '3d_secure' => [
                                'decision_origin' => 'Moneris',
                                'result' => 3,
                                'condition' => 1,
                                'status' => 'disabled',
                                'code' => null,
                                'details' => null
                            ],
                            'kount' => [
                                'decision_origin' => 'Moneris',
                                'result' => 2,
                                'condition' => 1,
                                'status' => 'failed_mandatory',
                                'code' => null,
                                'details' => [
                                    'responseCode' => '987',
                                    'message' => 'Invalid transaction',
                                    'receiptID' => null,
                                    'result' => null,
                                    'score' => null,
                                    'transactionID' => null
                                ]
                            ]
                        ],
                        'card_type' => 'V',
                        'transaction_date_time' => '2024-10-23 12:17:05'
                    ]
                ]
            ]
        ];
        return $data;
    }
    
}