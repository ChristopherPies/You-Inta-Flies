<?php

class DDM_Payment_Medium_AuthorizeNetCim extends DDM_Payment_Medium_Abstract {
    
    /**
     * Class data
     * 
     * @var array
     */
    protected $data = array(
        'customerProfileId' => null,
        'customerPaymentProfileId' => null,
        'customerShippingAddressId' => null,
        'creditCardNumberMasked' => null,
        'bankRoutingNumberMasked' => null,
        'bankAccountNumberMasked' => null,
        'cardCode' => null,
        'splitTenderId' => null,
        'approvalCode' => null,
        'transId' => null,
    );
    
    /**
     * The gateway proxy to DDM_Payment_Gateway_AuthorizeNetCim
     * 
     * @var DDM_Payment_Gateway_Proxy
     */
    protected $gateway;
    
    /**
     * Constructor
     * 
     * @param array $data 
     */
    public function __construct(array $data = null) {
        parent::__construct($data);        
        if (!$this->getCustomerProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'customerProfileId is required'
            );
        }
        
        $this->gateway = new DDM_Payment_Gateway_Proxy();
        
        // set options that are not part of data array
        if (isset($data['gateway'])) {
            $this->setGateway($data['gateway']);
        }
        
        if ($this->getGateway() && !$this->getCustomerShippingAddressId()
            && !empty($data['shippingAddress'])
        ) {
            $this->createShippingAddress($data['shippingAddress']);
        }
    }
    
    public function __sleep() {
        $props = array('data');
        
        // only serialize the gateway proxy if a gateway is set
        // otherwise, it can be reconstructed.
        if ($this->getGateway()) {
            $props[] = 'gateway';
        }
        
        return $props;
    }
    
    public function __wakeup() {
        if (!$this->gateway) {
            $this->gateway = new DDM_Payment_Gateway_Proxy();
        }
    }
    
    /**
     * Static factory method that creates an AuthorizeNetCim medium or a more
     * specific subclass, depending on the provided data.
     * 
     * @param array $data
     * 
     * @return DDM_Payment_Medium_AuthorizeNetCim
     */
    public static function createFromPaymentData(array $data = null) {
        if ($data) {
            if (!empty($data['cardNumber']) || !empty($data['cardCode']) 
                || !empty($data['creditCardNumberMasked'])
            ) {
                return new DDM_Payment_Medium_AuthorizeNetCim_CreditCard($data);
            } if (!empty($data['accountNumber']) 
                  || !empty($data['bankAccountNumberMasked'])
            ) {
                return new DDM_Payment_Medium_AuthorizeNetCim_BankAccount($data);
            }
        }
        
        return new self($data);
    }
    
    public function getGateway() {
        return $this->gateway->getGateway();
    }

    public function setGateway(DDM_Payment_Gateway_AuthorizeNetCim $gateway = null) {
        $this->gateway->setGateway($gateway);
    }
    
    protected function createPaymentProfile($data) {
        $profile = new DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile($data);
        $profileId = $this->gateway->createCustomerPaymentProfile(
            $this->getCustomerProfileId(), $profile
            
        );
        $this->data['customerPaymentProfileId'] = $profileId;
        
        return $profileId;
    }
    
    protected function createShippingAddress($data) {
        $address = new DDM_Payment_Customer_AuthorizeNetCim_Address($data);        
        $addressId = $this->gateway->createCustomerShippingAddress(
            $this->getCustomerProfileId(), $address
        );
        $this->data['customerShippingAddressId'] = $addressId;
        
        return $addressId;
    }
    
}
