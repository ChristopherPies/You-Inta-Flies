<?php 

abstract class DDM_Payment_Invoice_Abstract implements DDM_Payment_Invoice {
    
    protected $id = null;
    protected $purchaseOrderId = null;
    protected $description = null;
    protected $payer = null;
    protected $payee = null;
    protected $lineItems = array();
    protected $orderDate = null;

    public function __construct($id = null, array $lineItems = null) {
        $this->id = $id;
        $this->lineItems = (array) $lineItems;
    }
    
    public function getLineItems($type = null) {
        if ($type) {
            if (strpos($type, 'DDM_Payment_Invoice_LineItem_') !== 0) {
                $type = 'DDM_Payment_Invoice_LineItem_' . ucfirst($type);
            }
            $items = array();
            foreach ($this->lineItems as $item) {
                if ($item instanceof $type) {
                    $items[] = $item;
                }
            }
            return $items;
        }
        
        return $this->lineItems;
    }

    public function addLineItem(DDM_Payment_Invoice_LineItem $lineItem) {
        $this->lineItems[] = $lineItem;
    }

    public function getTotal($type = null) {
        $total = 0;
        foreach ($this->getLineItems($type) as $lineItem) {
            $total += $lineItem->getTotal();
        }
        return $total;
    }

    public function getId() {
        return $this->id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function getPurchaseOrderId() {
        return $this->purchaseOrderId;
    }

    public function setPurchaseOrderId($purchaseOrderId) {
        $this->purchaseOrderId = $purchaseOrderId;
    }
    
    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }
    
    public function getPayer() {
        return $this->payer;
    }

    public function setPayer(DDM_Payment_Customer $payer) {
        $this->payer = $payer;
    }

    public function getPayee() {
        return $this->payee;
    }

    public function setPayee(DDM_Payment_Customer $payee) {
        $this->payee = $payee;
    }
    
    public function getOrderDate($format = null) {
        if ($format) {
            return date($format, $this->orderDate);
        }
        return $this->orderDate;
    }

    public function setOrderDate($date) {
        if ($date && is_string($date)) {
            $date = strtotime($date);
        }
        $this->orderDate = $date;
    }
    
}
