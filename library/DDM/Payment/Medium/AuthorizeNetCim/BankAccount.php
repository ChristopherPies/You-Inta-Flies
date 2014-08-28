<?php

class DDM_Payment_Medium_AuthorizeNetCim_BankAccount 
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
        'bankRoutingNumberMasked' => null,
        'bankAccountNumberMasked' => null,
        'splitTenderId' => null,
        'approvalCode' => null,
        'transId' => null,
    );
    
    protected $dataRemovedKeys = array(
        'creditCardNumberMasked',
        'cardCode',
    );
    
    public function __construct($data = null) {
        parent::__construct($data);
        
        if ($this->getGateway() && !$this->getCustomerPaymentProfileId()
            && !empty($data['accountNumber'])
        ) {
            $profileData = $data;
            $profileData['payment'] = array('bankAccount' => $data);
            $profileData['billTo'] = $data;
            $this->createPaymentProfile($profileData);
        }
    }
    
}
