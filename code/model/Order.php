<?php


class Order extends DataObject
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
	private static $summary_fields = array(
		'OrderNumber',
        'Email',
        'LastName',
        'FirstName'
	);
    
    /**
     * Mapping of DB fields to omnipay gateway parameters
     * @var array 
     */
    private static $gateway_data_map = [
        'FirstName' => 'billingFirstName',
        'LastName' => 'billingLastName',
        'Phone' => 'billingPhone',
        'Email' => 'email'
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
    
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        
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
            'Comments' => _t('Order.Comments','Comments')
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
           
       );
   }
   
  
}

