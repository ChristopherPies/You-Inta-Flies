<?php

abstract class DDM_Payment_Customer_Abstract
    extends DDM_Payment_DataObject
    implements DDM_Payment_Customer
{
    protected $data = array(
        'id' => null,
        'defaultBillingAddress' => null,
        'defaultShippingAddress' => null,
    );
    
/*********************************************************
 * Virtual method implementations for interface adherence 
 *********************************************************/
    
    public function getId() {
        return parent::getId();
    }

    public function setId($id) {
        return parent::setId($id);
    }

    public function getDefaultBillingAddress() {
        return $this->__call(__FUNCTION__, func_get_args());
    }
    
    public function setDefaultBillingAddress(DDM_Payment_Customer_Address $address) {
        return $this->__call(__FUNCTION__, func_get_args());
    }
    
    public function getDefaultShippingAddress() {
        return $this->__call(__FUNCTION__, func_get_args());
    }
    
    public function setDefaultShippingAddress(DDM_Payment_Customer_Address $address) {
        return $this->__call(__FUNCTION__, func_get_args());
    }
    
}
