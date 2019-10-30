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
        'Comments' => 'Varchar(255)'
	);
    
    private static $extensions = ['Payable'];
    
    private static $result_fields = ['FirstName','LastName','Email','Phone','OrderNumber','Comments'];
    
    protected $_cachedResultData;       // instance cache
    
   /**
	 * @config
	 */
	private static $summary_fields = ['OrderNumber','Email','LastName','FirstName','SummaryTotalPaid'];
    
    private static $noneditable_fields = ['FirstName','LastName','Email','Phone','OrderNumber','Comments'];
    
    /**
     * Mapping of DB fields to omnipay gateway parameters
     * @var array 
     */
    private static $gateway_data_map = [
        'FirstName' => 'billingFirstName',
        'LastName' => 'billingLastName',
        'Phone' => 'billingPhone',
        'Email' => 'email',
        'Comments' => 'note'
    ];
    
    
    public function dataForGateway()
    {
        $dataMap = $this->config()->get('gateway_data_map',Config::INHERITED) ?: [];

        $data = [];
        foreach($dataMap as $field => $param) {
            $data[$param] = $this->getField($field);
        }
        return $data;
    }
   
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
        
        $noneditable = $this->config()->get('noneditable_fields');
        if(!empty($noneditable) && is_array($noneditable)) {
            foreach($noneditable as $name) {
                $field = $fields->dataFieldByName($name);
                if($field && method_exists($field,'performReadonlyTransformation')) {
                    $readonlyField = $field->performReadonlyTransformation();
                    $fields->replaceField($name,$readonlyField);
                }
            }
        }
        
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
            'SummaryTotalPaid' => _t('Order.SummaryTotalPaid','Total Paid')
		);
	}
    
    
    public function resultData()
    {

        if(is_null($this->_cachedResultData)) {
            $fields = $this->config()->get('result_fields',Config::UNINHERITED);
            $data = [];
            $labels = $this->translatedLabels();
            foreach($fields as $field) {
                $data[] = [
                    'Field' => $field,
                    'Title' => isset($labels[$field]) ? $labels[$field] : $field,
                    'Data' => $this->$field
                ];
            }
            $this->_cachedResultData = ArrayList::create($data);
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

