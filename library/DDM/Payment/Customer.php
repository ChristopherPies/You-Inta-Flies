<?php

interface DDM_Payment_Customer {
    
    public function getId();
    public function setId($id);
    
    public function getDefaultBillingAddress();
    public function setDefaultBillingAddress(DDM_Payment_Customer_Address $address);
    
    public function getDefaultShippingAddress();
    public function setDefaultShippingAddress(DDM_Payment_Customer_Address $address);
    
}
