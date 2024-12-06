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
        $context = singleton($this->modelClass)->getDefaultSearchContext();
        $fields = $context->getSearchFields();
        
        if($this->modelClass == 'Payment') {
            $gateways = GatewayInfo::getSupportedGateways();
            if(count($gateways) === 1) {
                $fields->removeByName('Gateway');
            }
            //$fields->removeByName('Money');
            //$fields->insertAfter(NumberField::create('Money'),'Status');
            
        }
        elseif($this->modelClass == 'Order') {
            
        }
        // Namespace fields, for easier detection if a search is present
		foreach($context->getFields() as $field) $field->setName(sprintf('q[%s]', $field->getName()));
		foreach($context->getFilters() as $filter) $filter->setFullName(sprintf('q[%s]', $filter->getFullName()));
        
        $this->extend('updateSearchContext', $context);
        
		return $context;
	}
    
    public function getList() 
    {
        $list = parent::getList();
        
		$params = $this->getRequest()->requestVar('q');

		return $list;
	}
    
    
}
