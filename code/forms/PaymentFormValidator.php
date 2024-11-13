<?php

use Omnipay\Moneris\Helper;

/**
 * Required Fields allows you to set which fields need to be present before
 * submitting the form. Submit an array of arguments or each field as a separate
 * argument.
 *
 * Validation is performed on a field by field basis through
 * {@link FormField::validate}.
 *
 * @package forms
 * @subpackage validators
 */
class PaymentFormValidator extends RequiredFields {

	protected $required;
	
    protected $rules = [];
    
    
    public function getRules()
    {
        return $this->rules;
    }
    
    public function setRules($rules)
    {
        $this->rules = $rules;
    }
    
	/**
	 * Allows validation of fields via specification of a php function for
	 * validation which is executed after the form is submitted.
	 *
	 * @param array $data
	 *
	 * @return boolean
	 */
	public function php($data)
    {
		$valid = parent::php($data);
       
		if(!empty($this->rules)) {
            
            $fields = $this->form->Fields();
            
			foreach($this->rules as $fieldName => $cfg) {
                $formField = $fields->dataFieldByName($fieldName);
                
                // Skip if no field or configuration
				if(!$formField || empty($cfg)) {
					continue;
				}
                
                $fieldTitle = strip_tags(
                    '"' . ($formField->Title() ? $formField->Title() : $fieldName) . '"'
                );
                
                try {
                    // Check length
                    Helper::validateLimit($fieldTitle,$formField->Value(),$cfg);
                    
                    // Check min
                    Helper::validateMin($fieldTitle,$formField->Value(),$cfg);
                    
                    // Check max
                    Helper::validateMax($fieldTitle,$formField->Value(),$cfg);
                    
                    // Check invalid chars
                    Helper::validateCharacters($fieldTitle,$formField->Value(),$cfg,false);
                    
                } catch (\Exception $ex) {
                    $this->validationError(
						$fieldName,
						$ex->getMessage(),
						'validation'
					);
                    
                    $valid = false;
                }
			}
		}

		return $valid;
	}
    
}