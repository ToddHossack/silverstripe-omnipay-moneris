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
    private static $managed_models = ['Payment','Order'];

    private static $menu_title = 'Payments';
	
    public $showSearchForm = true;
    
    public $showImportForm = false;
    
    /**
	 * Initialize the model admin interface.
	 */
	public function init() {
		parent::init();

		//Requirements::css(TKINEWS_DIR . '/css/tkinews-admin.css');
	}
    
    /**@todo - Make menu icon 
    private static $menu_icon = "";
	*/
	/*
    public function getEditForm($id = null, $fields = null) {
		$list = $this->getList();
		$exportButton = new GridFieldExportButton('buttons-before-left');
		$exportButton->setExportColumns($this->getExportFields());
		$listField = GridField::create(
			$this->sanitiseClassName($this->modelClass),
			false,
			$list,
			$fieldConfig = GridFieldConfig_RecordEditor::create($this->stat('page_length'))
				->addComponent($exportButton)
				->removeComponentsByType('GridFieldFilterHeader')
				->addComponents(new GridFieldPrintButton('buttons-before-left'))
		);

		// Validation
		if(singleton($this->modelClass)->hasMethod('getCMSValidator')) {
			$detailValidator = singleton($this->modelClass)->getCMSValidator();
			$listField->getConfig()->getComponentByType('GridFieldDetailForm')->setValidator($detailValidator);
		}
        
        $formClass = $this->determineFormClass();
        
		$form = $formClass::create(
			$this,
			'EditForm',
			new FieldList($listField),
			new FieldList()
		)->setHTMLID('Form_EditForm');
		$form->setResponseNegotiator($this->getResponseNegotiator());
		$form->addExtraClass('cms-edit-form cms-panel-padded center');
		$form->setTemplate($this->getTemplatesWithSuffix('_EditForm'));
		$editFormAction = Controller::join_links($this->Link($this->sanitiseClassName($this->modelClass)), 'EditForm');
		$form->setFormAction($editFormAction);
		$form->setAttribute('data-pjax-fragment', 'CurrentForm');

		$this->extend('updateEditForm', $form);

		return $form;
	}
    */
    
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
		//if($this->modelClass === 'NewsArticle' && !empty($params['NewsCategory'])) {
		//	$list = $list->filter(['Categories.ID' => $params['NewsCategory']]);
		//}
        
		return $list;
	}
    
    
}
