<?php

class DDM_Payment_Transaction_AuthorizeNetCim_Amount extends DDM_Payment_DataObject {
    
    protected $data = array(
        'amount' => null,
        'name' => null,
        'description' => null,
    );
    
    public static function createFromInvoiceLineItem(DDM_Payment_Invoice_LineItem $lineItem) {
        return new self(array(
            'amount' => round($lineItem->getTotal(), 2),
            'name' => $lineItem->getName(),
            'description' => $lineItem->getDescription(),
        ));
    }
    
    /**
     * Sets the amount rounded to 4 decimal places (authnet max)
     */
    public function setAmount($amount) {
        $this->data['amount'] = round($amount, 2);
        return $this;
    }
    
}
