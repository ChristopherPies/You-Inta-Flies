<?php

interface DDM_FieldMap_Constraint_Interface extends Zend_Validate_Interface
{
   
    
    
    /**
     * Checks to see if element has associated validators
     * 
     * @return boolean true if validators are defined false otherwise
     */
    public function hasValidators();
    
    
	/**
     * Checks to see if element has associated filters
     * 
     * @return boolean true if filters are defined false otherwise
     */
    public function hasFilters();
    
	/**
     * Is the element required?
     *
     * @return bool
     */
    public function isRequired();
    
    /**
     * Set element value
     *
     * @param  mixed $value
     * @return Zend_Form_Element
     */
    public function setValue($value);
    
    /**
     * Retrieve filtered element value
     *
     * @return mixed
     */
    public function getValue();
    
    
}