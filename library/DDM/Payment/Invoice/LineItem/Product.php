<?php

class DDM_Payment_Invoice_LineItem_Product extends DDM_Payment_Invoice_LineItem_Abstract {
    
    /**
     * Gets the quantity
     * 
     * @return int 
     */
    public function getQuantity() {
        return $this->quantity;
    }
    
    /**
     * Sets the quantity (minimum of 1)
     * 
     * @param int $quantity 
     */
    public function setQuantity($quantity) {
        $this->quantity = max(array(1, (int)$quantity));
    }
    
}
