<?php

class DDM_Payment_Transaction_AuthorizeNetCim extends DDM_Payment_DataObject {
    
    protected $data = array(
        'amount' => null,
        'tax' => null,
        'shipping' => null,
        'duty' => null,
        'lineItems' => null,
        'customerProfileId' => null,
        'customerPaymentProfileId' => null,
        'customerShippingAddressId' => null,
        'creditCardNumberMasked' => null,
        'bankRoutingNumberMasked' => null,
        'bankAccountNumberMasked' => null,
        'order' => null,
        'taxExempt' => null,
        'recurringBilling' => null,
        'cardCode' => null,
        'splitTenderId' => null,
        'approvalCode' => null,
        'transId' => null,
    );
    
    protected static $dataIdAlias = 'transId';
    
    protected static $dataClassMap = array(
        'tax' => 'DDM_Payment_Transaction_AuthorizeNetCim_Amount',
        'shipping' => 'DDM_Payment_Transaction_AuthorizeNetCim_Amount',
        'duty' => 'DDM_Payment_Transaction_AuthorizeNetCim_Amount',
        'lineItems[]' => 'DDM_Payment_Transaction_AuthorizeNetCim_LineItem',
        'order' => 'DDM_Payment_Transaction_AuthorizeNetCim_Order',
    );
    
    /**
     * Creates a new instance with data from a medium and invoice
     * 
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     * 
     * @return self
     */
    public static function createfromMediumAndInvoice(
        DDM_Payment_Medium_AuthorizeNetCim $medium,
        DDM_Payment_Invoice $invoice
    ) {
        $instance = new self();
        $instance->setDataFromMedium($medium);
        $instance->setDataFromInvoice($invoice);
        return $instance;
    }
    
    /**
     * Sets the data that can be extracted from a medium
     * 
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium 
     */
    public function setDataFromMedium(DDM_Payment_Medium_AuthorizeNetCim $medium) {
        $this->setData($medium->getData(array(
            'customerProfileId',
            'customerPaymentProfileId',
            'customerShippingAddressId',
            'creditCardNumberMasked',
            'bankRoutingNumberMasked',
            'bankAccountNumberMasked',
            'cardCode',
            'splitTenderId',
            'approvalCode',
            'transId',
        )));
    }
    
    /**
     * Sets the data that can be extracted from an invoice
     * 
     * @param DDM_Payment_Invoice $invoice 
     */
    public function setDataFromInvoice(DDM_Payment_Invoice $invoice) {
        $tax = null;
        $shipping = null;
        $lineItems = array();
        $order = null;
        
        foreach ($invoice->getLineItems('tax') as $index => $item) {
            if ($tax) {
                $tax->setAmount($tax->getAmount() + $item->getTotal());
            } else {
                $tax = DDM_Payment_Transaction_AuthorizeNetCim_Amount::createFromInvoiceLineItem($item);
            }
        }
        foreach ($invoice->getLineItems('shipping') as $index => $item) {
            if ($shipping) {
                $shipping->setAmount($shipping->getAmount() + $item->getTotal());
            } else {
                $shipping = DDM_Payment_Transaction_AuthorizeNetCim_Amount::createFromInvoiceLineItem($item);
            }
        }
        foreach ($invoice->getLineItems('product') as $index => $item) {
            if (!$item->getId()) {
                $item->setId('item_' . ($index+1));
            }
            $lineItems[] = DDM_Payment_Transaction_AuthorizeNetCim_LineItem::createFromInvoiceLineItemProduct($item);
        }
        
        if ($invoice->getId() || $invoice->getDescription() || $invoice->getPurchaseOrderId()) {
           $order = DDM_Payment_Transaction_AuthorizeNetCim_Order::createFromInvoice($invoice);
        }
        
        $this->setData(array(
            'amount' => $invoice->getTotal(),
            'tax' => $tax,
            'shipping' => $shipping,
            'lineItems' => (count($lineItems) > 0 ? $lineItems : null),
            'order' => $order,
            //'duty' => null, // not implemented
            //'taxExempt' => null, // not implemented
            //'recurringBilling', // not implemented
        ));
    }
    
    /**
     * Sets the amount rounded to 4 decimal places (authnet max)
     */
    public function setAmount($amount) {
        $this->data['amount'] = round($amount, 4);
        return $this;
    }
    
    /**
     * Gets the data needed for an auth only transaction
     * 
     * @return array
     */
    public function getAuthData() {
        return $this->getData(array(
            'amount',
            'tax',
            'shipping',
            'duty',
            'lineItems',
            'customerProfileId',
            'customerPaymentProfileId',
            'customerShippingAddressId',
            'order',
            'taxExempt',
            'recurringBilling',
            'cardCode',
            'splitTenderId',
        ), true);
    }
    
    /**
     * Gets the data needed for an auth and capture transaction
     * 
     * @return array
     */
    public function getAuthCaptureData() {
        return $this->getAuthData();
    }
    
    /**
     * Gets the data needed for a capture only transaction
     * 
     * @return array
     */
    public function getCaptureData() {
        $data = $this->getAuthData();
        $data['approvalCode'] = $this->getData('approvalCode');
        return $data;
    }
    
    /**
     * Gets the data needed for a prior auth capture transaction
     * 
     * @return array
     */
    public function getPriorAuthCaptureData() {
        return $this->getData(array(
            'amount',
            'tax',
            'shipping',
            'duty',
            'lineItems',
            'customerProfileId',
            'customerPaymentProfileId',
            'customerShippingAddressId',
            'transId',
        ), true);
    }
    
    /**
     * Gets the data needed for a refund transaction
     * 
     * @return array
     */
    public function getRefundData() {
        return $this->getData(array(
            'amount',
            'tax',
            'shipping',
            'duty',
            'lineItems',
            'customerProfileId',
            'customerPaymentProfileId',
            'customerShippingAddressId',
            'creditCardNumberMasked',
            'bankRoutingNumberMasked',
            'bankAccountNumberMasked',
            'order',
            'transId',
        ), true);
    }
    
    /**
     * Gets the data needed for a void transaction
     * 
     * @return array
     */
    public function getVoidData() {
        return $this->getData(array(
            'customerProfileId',
            'customerPaymentProfileId',
            'customerShippingAddressId',
            'transId',
        ), true);
    }
    
}
