<?php

class DDM_FieldMap_Constraint extends Zend_Form_Element implements DDM_FieldMap_Constraint_Interface
{

    protected $_disableLoadDefaultDecorators = true;
    protected $_translatorDisabled = true;
    
    /**
     * Checks to see if element has associated validators
     * 
     * @return boolean true if validators are defined false otherwise
     */
    public function hasValidators()
    {
        return ($this->isRequired() || ! empty($this->_validators));
    }
    
	/**
     * Checks to see if element has associated filters
     * 
     * @return boolean true if filters are defined false otherwise
     */
    public function hasFilters()
    {
        return (! empty($this->_filters));
    }
    
    /**
     * Add a filter to the element. 
     * 
     * Extention allows options to be set on already instantiated filter.
     *
     * @param  string|Zend_Filter_Interface $filter
     * @return Zend_Form_Element
     * 
     * (non-PHPdoc)
     * @see Zend_Form_Element::addFilter()
     */
    public function addFilter($filter, $options = array())
    {
        
        if ($filter instanceof Zend_Filter_Interface) {
            $name = get_class($filter);
            
            if (! empty($options) && method_exists($filter, 'setOptions')) {
                $filter->setOptions($options);
            }
        } elseif (is_string($filter)) {
            $name = $filter;
            $filter = array(
                'filter'  => $filter,
                'options' => $options,
            );
            $this->_filters[$name] = $filter;
        } else {
            require_once 'Zend/Form/Exception.php';
            throw new Zend_Form_Exception('Invalid filter provided to addFilter; must be string or Zend_Filter_Interface');
        }

        $this->_filters[$name] = $filter;

        return $this;
    }
    
	/**
     * Add validator to validation chain
     * 
     * Extention allows options to be set on already instantiated validator.
     * 
     * Note: will overwrite existing validators if they are of the same class.
     *
     * @param  string|Zend_Validate_Interface $validator
     * @param  bool $breakChainOnFailure
     * @param  array $options
     * @return Zend_Form_Element
     * @throws Zend_Form_Exception if invalid validator type
     *
     * (non-PHPdoc)
     * @see Zend_Form_Element::addValidator()
     */
    public function addValidator($validator, $breakChainOnFailure = false, $options = array())
    {
        if ($validator instanceof Zend_Validate_Interface) {
            $name = get_class($validator);

            if (!isset($validator->zfBreakChainOnFailure)) {
                $validator->zfBreakChainOnFailure = $breakChainOnFailure;
            }
            
            if (! empty($options) && method_exists($validator, 'setOptions')) {
                $validator->setOptions($options);
            }
            
        } elseif (is_string($validator)) {
            $name      = $validator;
            $validator = array(
                'validator' => $validator,
                'breakChainOnFailure' => $breakChainOnFailure,
                'options'             => $options,
            );
        } else {
            require_once 'Zend/Form/Exception.php';
            throw new Zend_Form_Exception('Invalid validator provided to addValidator; must be string or Zend_Validate_Interface');
        }


        $this->_validators[$name] = $validator;

        return $this;
    }
    
    
}