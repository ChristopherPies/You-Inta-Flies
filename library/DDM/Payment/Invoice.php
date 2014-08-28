<?php

interface DDM_Payment_Invoice {
    
    public function getId();
    public function setId($id);
    
    public function getDescription();
    public function setDescription($description);
    
    public function getLineItems($type = null);
    public function addLineItem(DDM_Payment_Invoice_LineItem $lineitem);

    public function getTotal($type = null);

    public function getPayer();
    public function setPayer(DDM_Payment_Customer $payer);
    
    public function getPayee();
    public function setPayee(DDM_Payment_Customer $payee);
    
    public function getOrderDate($format = null);
    public function setOrderDate($date);
    
}
