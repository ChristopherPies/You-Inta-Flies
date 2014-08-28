<?php

class DDM_Payment_Medium_AuthorizeNetCim_CreditCard
    extends DDM_Payment_Medium_AuthorizeNetCim
{
    
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
        'cardCode' => null,
        'splitTenderId' => null,
        'approvalCode' => null,
        'transId' => null,
    );
    
    public function __construct($data = null) {
        parent::__construct($data);
        
        if ($this->getGateway() && !$this->getCustomerPaymentProfileId()
            && !empty($data['cardNumber'])
        ) {
            $profileData = $data;
            $profileData['payment'] = array('creditCard' => $data);
            $profileData['billTo'] = $data;
            $this->createPaymentProfile($profileData);
        }
    }
    
}
