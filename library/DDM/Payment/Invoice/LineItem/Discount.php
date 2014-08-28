<?php

class DDM_Payment_Invoice_LineItem_Discount extends DDM_Payment_Invoice_LineItem_Abstract {
    
    /**
     * Sets the value, making sure it's negative
     * 
     * @param float $value
     */
    public function setValue($value) {
        parent::setValue(($value <= 0) ? $value : -$value);
    }
    
}
