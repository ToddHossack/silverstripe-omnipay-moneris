<?php


class Order extends DataObject implements PermissionProvider
{
    /* ---- Static variables ---- */
    private static $db = array(
        // Customer
        'FirstName' => 'Varchar(30)',
        'LastName' => 'Varchar(30)',
        'Email' => 'Varchar(50)',
        'Phone' => 'Varchar(20)',
        // Order
        'OrderNumber' => 'Varchar(34)', // Unique order ID
        'Comments' => 'Varchar(255)',
        // Billing address
        'BillingAddressLine1' => 'Varchar',
		'BillingAddressLine2' => 'Varchar',
        'BillingCity' => 'Varchar',
        'BillingState' => 'Varchar',
        'BillingCountry' => 'Varchar(2)',
        'BillingPostCode' => 'Varchar(16)',
        // Shipping address
        'MailingAddressLine1' => 'Varchar',
		'MailingAddressLine2' => 'Varchar',
        'MailingSuburb' => 'Varchar',
        'MailingCity' => 'Varchar',
        'MailingState' => 'Varchar',
        'MailingCountry' => 'Varchar(2)',
        'MailingPostCode' => 'Varchar(16)'
	);
    
    private static $extensions = ['Payable'];
    
    private static $order_fields = ['OrderNumber','Comments'];
    
    private static $contact_fields = ['FirstName','LastName','Email','Phone'];
    
    private static $mailing_address_fields = ['MailingAddressLine1','MailingAddressLine2','MailingCity','MailingState','MailingPostCode'];
    
    protected $_cachedResultData;       // instance cache
    
     
   /**
	 * @config
	 */
	private static $summary_fields = ['OrderNumber','Email','LastName','FirstName','SummaryTotalPaid'];
    
    private static $editable_fields = [];
    
    private static $default_sort = 'ID DESC';
    
    /* 
	 * -------------------------------------------------------------------------
	 *  Getters / setters
	 * -------------------------------------------------------------------------
	 */
    public function SummaryTotalPaid()
    {
        return DBField::create_field('Currency',$this->TotalPaid());
    }
    
    
    /* 
	 * -------------------------------------------------------------------------
	 *  Admin
	 * -------------------------------------------------------------------------
	 */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
        $editable = (array) $this->config()->get('editable_fields');
        $dbFields = Config::inst()->get(($this->class ? $this->class : get_class($this)), 'db', Config::INHERITED);

        $fields->removeByName('OrderNumber');
        $fields->insertAfter('MailingPostCode',
            TextField::create('OrderNumber', _t('Order.OrderNumber','Order Number'))
        );
        
        $fields->removeByName('Comments');
        $fields->insertAfter('OrderNumber',
            TextField::create('Comments', _t('Order.Comments','Comments'))
        );
        
        if(!empty($dbFields) && is_array($dbFields)) {
            foreach($dbFields as $name => $type) {
                // Skip editable
                if(in_array($name,$editable)) {
                    continue;
                }
                $field = $fields->dataFieldByName($name);
                if($field && method_exists($field,'performReadonlyTransformation')) {
                    $readonlyField = $field->performReadonlyTransformation();
                    $fields->replaceField($name,$readonlyField);
                }
            }
        }
        // Insert headers
        $fields->insertBefore('FirstName',
            HeaderField::create('ContactDetailsHeading', _t('Order.ContactDetailsHeading','Contact Details'),3)
        );
        
        $fields->insertBefore('BillingAddressLine1',
            HeaderField::create('BillingAddressHeading',_t('Order.BillingAddressHeading','Billing Address'),3)
        );
        
        $fields->insertBefore('MailingAddressLine1',
            HeaderField::create('MailingAddressHeading',_t('Order.MailingAddressHeading','Mailing Address'),3)
        );
        
        $fields->insertBefore('OrderNumber',
            HeaderField::create('OrderDetailsHeading', _t('Order.OrderDetailsHeading','Order Details'),3)
        );
        
        
        // Payments
        $fields->removeByName('Payments');
        $paymentsConfig = GridFieldConfig_RecordEditor::create();
        $delete = $paymentsConfig->getComponentByType('GridFieldDeleteAction');
        if($delete) {
            $paymentsConfig->removeComponent($delete);
        }

        $addNew = $paymentsConfig->getComponentByType('GridFieldAddNewButton');
        if($addNew) {
            $paymentsConfig->removeComponent($addNew);
        }
        
        $paymentsField = GridField::create('Payments', 
            _t('Order.Payments', 'Payments'),
            $this->Payments(),
            $paymentsConfig
        );
        $fields->addFieldToTab('Root.Payments',$paymentsField);
        
        return $fields;
    }
    
    protected function onAfterWrite() {
		if(empty($this->OrderNumber)) {
            $this->OrderNumber = $this->generateOrderNumber($this->ID);
            $this->write();
        }
        parent::onAfterWrite();
	}
    
    public function generateOrderNumber($id)
    {
        return $id .'-'. date('ymdHis');
    }
    
    public function fieldLabels($includerelations = true)
	{
        return array_merge(parent::fieldLabels($includerelations),(array) $this->translatedLabels());
	}
    
    protected function translatedLabels() {
		return array(
			'OrderNumber' => _t('Order.OrderNumber','Order Number'),
            'FirstName' => _t('Order.FirstName','First Name'),
            'LastName' => _t('Order.LastName','Last Name / Business Name'),
            'Email' => _t('Order.Email','Email'),
            'Phone' => _t('Order.Phone','Phone'),
            'Comments' => _t('Order.Comments','Comments'),
            'SummaryTotalPaid' => _t('Order.SummaryTotalPaid','Total Paid'),
            'MailingAddressLine1' => _t('Order.MailingAddressLine1','Address Line 1'),
            'MailingAddressLine2' => _t('Order.MailingAddressLine2','Address Line 2'),
            'MailingSuburb' => _t('Order.MailingSuburb','Suburb'),
            'MailingCity' => _t('Order.MailingCity','Town/City'),
            'MailingState' => _t('Order.MailingState','Province'),
            'MailingCountry' => _t('Order.MailingCountry','Country'),
            'MailingPostCode' => _t('Order.MailingPostCode','Post Code')
		);
	}
    
    
    public function resultData()
    {
        if(is_null($this->_cachedResultData)) {
            $map = [
                'ContactDetails' => 'contact_fields',
                'OrderData' => 'order_fields',
                'MailingAddress' => 'mailing_address_fields',
            ];
            
            $data = [];
            $labels = $this->translatedLabels();
            foreach($map as $label => $key) {
                $fields = $this->config()->get($key,Config::FIRST_SET);
                $list = [];
                if(!empty($fields)) {
                    foreach($fields as $field) {
                        $value = $this->getField($field);
                        // Skip fields without a value
                        if(!is_null($value)) {
                            $list[] = [
                                'Field' => $field,
                                'Title' => isset($labels[$field]) ? $labels[$field] : $field,
                                'Data' => $value
                            ];
                        }
                    }
                }
                $data[$label] = (count($list)) ? ArrayList::create($list) : null;
            }
            
            $this->_cachedResultData = $data;
        }
        return $this->_cachedResultData;
    }
    
    /**
    * 
    * @see DataObject::providePermissions()
    * @return type
    */
   public function providePermissions()
   {
       return array(
           'Order_CREATE' => array(
                'name' => _t('Order.Order_CREATE', 'Create orders'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 220
            ),
            'Order_VIEW' => array(
                'name' => _t('Order.Order_VIEW', 'View orders'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 230
            ),
           'Order_EDIT' => array(
                'name' => _t('Order.Order_EDIT', 'Edit orders'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 235
            ),
            'Order_DELETE' => array(
                'name' => _t('PaymentExtension.Order_DELETE', 'Delete orders'),
                'category' => _t('Payment.PAYMENT_PERMISSIONS', 'Payment actions'),
                'sort' => 240
            ),
        );
   }
   
    public function canCreate($member = null) 
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
        
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
        // Pseudo editing
		return Permission::check('Order_CREATE', 'any', $member);
	}
    
    public function canView($member = null)
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
        
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
	
		return Permission::check('Order_VIEW', 'any', $member);
	}
    
    public function canEdit($member = null) 
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
        
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
        // Pseudo editing
		return Permission::check('Order_EDIT', 'any', $member);
	}
    
    public function canDelete($member = null) 
    {
        $extended = $this->extendedCan(__FUNCTION__, $member);
		if($extended !== null) {
			return $extended;
		}
        
        if(Permission::check('ADMIN', 'any', $member)) {
            return true;
        }
		return Permission::check('Order_DELETE', 'any', $member);
	}
    
}

