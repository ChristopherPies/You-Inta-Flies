<?php

interface DDM_Payment_Invoice_LineItem {
    
    public function __construct($options = null);

    public function setOptions(array $options = null);

    public function toString($format = null);
    public function __toString();
    
    public function getTotal();
    
    public function getId();
    public function setId($id);
    
    public function getName();
    public function setName($name);
    
    public function getDescription();
    public function setDescription($description);
    
    public function getValue();
    public function setValue($value);
    
}
