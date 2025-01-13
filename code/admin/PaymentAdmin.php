<?php

use SilverStripe\Omnipay\GatewayInfo;

/**
 * Management of payments
 */
class PaymentAdmin extends ModelAdmin
{
	
	private static $url_segment = 'payments';

	/**
	 * @todo Allow entries to be linked/unlinked to directories, to be true many_many.
	 * Currently it only allows deletes, so operates more like has_many 
	 * @var array 
	 */
    private static $managed_models = ['Order','Payment'];

    private static $menu_title = 'Payments';
	
    public $showSearchForm = true;
    
    public $showImportForm = false;
    
    /**
	 * Initialize the model admin interface.
	 */
	public function init() {
		parent::init();

	}
    
    public function getSearchContext()
    {
        $context = parent::getSearchContext();
        $fields = $context->getSearchFields();
        
        // Remove "Gateway" filter if only one gateway supported
        if($this->modelClass == 'Payment') {
            $gateways = GatewayInfo::getSupportedGateways();
            if(count($gateways) === 1) {
                $fields->removeByName('q[Gateway]');
            }
        }
        
        // Add date field, if "Created" configured as searchable field
        if(array_key_exists('Created',(array)$context->getFilters())) {
            $fields->push(DateField::create('q[Start]','Start'));
            $fields->push(DateField::create('q[End]','End'));
        }
        
		return $context;
	}
    
    public function getList() 
    {
        $list = parent::getList();
        $params = $this->getRequest()->requestVar('q');
        
        $filters = [];
        if(!empty($params['Start'])) {
            $filters['Created:GreaterThanOrEqual'] = $params['Start'];
        }
        if(!empty($params['End'])) {
            $context = $this->getSearchContext();
            $fields = $context->getSearchFields();
            $endField = $fields->fieldByName('q[End]');           
            if($endField) {
                $endField->setValue($params['End']);
                $format = $endField->getConfig('datavalueformat');
                $dateObj = new Zend_Date($endField->dataValue(), $endField->getConfig('datavalueformat'), $endField->getLocale());
                 // Set to end of day to ensure inclusive of datetime
                $dateObj->add(1439,Zend_Date::MINUTE);
                $filters['Created:LessThanOrEqual'] = $dateObj->toString(Zend_Date::RFC_3339);
            }
        }
        $list = $list->filter($filters);
        return $list;
	}
    
    
}
