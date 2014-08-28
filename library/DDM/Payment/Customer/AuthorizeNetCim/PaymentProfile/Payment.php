<?php

class DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment
    extends DDM_Payment_DataObject
{
    
    const TYPE_UNKNOWN = 'unknown';
    const TYPE_CREDITCARD = 'creditCard';
    const TYPE_BANKACCOUNT = 'bankAccount';
    
    protected $data = array(
        'creditCard' => null,
        'bankAccount' => null,
    );
    
    protected static $dataClassMap = array(
        'creditCard' => 'DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_CreditCard',
        'bankAccount' => 'DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_BankAccount',
    );
    
    public function __construct(array $data = null) {
        // don't allow both creditCard and bankAccount
        if (!empty($data['creditCard']) && !empty($data['bankAccount'])) {
            unset($data['bankAccount']);
        }
        
        parent::__construct($data);
    }
    
    /**
     * Gets the payment type
     * 
     * @return string
     */
    public function getType() {
        if ($this->data['creditCard']) {
            return self::TYPE_CREDITCARD;
        } else if ($this->data['bankAccount']) {
            return self::TYPE_BANKACCOUNT;
        }
        return self::TYPE_UNKNOWN;
    }
    
    /**
     * Gets all the object data with child (creditCard, bankAccount)
     * data masks applied. This works like getData().
     * 
     * @param null|array|string $key
     * @param string $maskChar
     * @param int $maskLength
     * 
     * @return array 
     */
    public function getMaskedData($key = null, $maskChar = 'X', $maskLength = 4) {        
        if (!$key) {
            $key = array_keys($this->data);
        }
        if (is_array($key)) {
            $data = array();
            foreach ($key as $k) {
                if (array_key_exists($k, $this->data)) {
                    $data[$k] = ($this->data[$k])
                              ? $this->data[$k]->getMaskedData(null, $maskChar, $maskLength)
                              : $this->data[$k];
                }
            }
            return $data;
        } else {
            $object = $this->data[$key]; // allow this to throw error if not found
            return ($object) ? $object->getMaskedData(null, $maskChar, $maskLength) : null;
        }
    }
    
    /**
     * Returns a string representation of the creditCard or bankAccount
     * 
     * @return string
     */
    public function toString() {
        $type = $this->getType();
        if ($type === self::TYPE_CREDITCARD) {
            return $this->getCreditCard()->toString();
        } 
        if ($type === self::TYPE_BANKACCOUNT) {
            return $this->getBankAccount()->toString();
        }
        
        return '';
    }
    
    public function __toString() {
        return $this->toString();
    }
    
}
