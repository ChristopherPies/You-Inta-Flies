<?php

class DDM_Payment_Transaction_AuthorizeNetCim_LineItem extends DDM_Payment_DataObject {
    
    protected $data = array(
        'itemId' => null,
        'name' => null,
        'description' => null,
        'quantity' => null,
        'unitPrice' => null,
        'taxable' => null,
    );
    
    protected static $dataIdAlias = 'itemId';
    
    public static function createFromInvoiceLineItemProduct(DDM_Payment_Invoice_LineItem_Product $lineItem) {
        return new self(array(
            'itemId' => ($lineItem->getId() ? $lineItem->getId() : uniqid('item_')),
            'name' => $lineItem->getName(),
            'description' => $lineItem->getDescription(),
            'quantity' => $lineItem->getQuantity(),
            'unitPrice' => ($lineItem->getValue() ? $lineItem->getValue() : '0.00'),
            // 'taxable' => null, // not implemented
        ));
    }
    
    /**
     * Sets the unitPrice rounded to 4 decimal places (authnet max)
     */
    public function setUnitPrice($unitPrice) {
        $this->data['unitPrice'] = round($unitPrice, 4);
        return $this;
    }
    
}
