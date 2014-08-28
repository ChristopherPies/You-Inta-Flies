<?php

class DDM_Payment_Transaction_AuthorizeNetCim_Order extends DDM_Payment_DataObject {
    
    protected $data = array(
        'invoiceNumber' => null,
        'description' => null,
        'purchaseOrderNumber' => null,
    );
    
    protected static $dataIdAlias = 'invoiceNumber';
    
    public static function createFromInvoice(DDM_Payment_Invoice $invoice) {
        $purchaseOrderId = method_exists($invoice, 'getPurchaseOrderId')
                         ? $invoice->getPurchaseOrderId()
                         : null;
        return new self(array(
            'invoiceNumber' => $invoice->getId(),
            'description' => $invoice->getDescription(),
            'purchaseOrderNumber' => $purchaseOrderId,
        ));
    }
    
}
