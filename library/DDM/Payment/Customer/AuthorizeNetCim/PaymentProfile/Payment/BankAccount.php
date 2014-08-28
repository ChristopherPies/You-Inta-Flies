<?php

class DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_BankAccount 
    extends DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_Abstract
{
    
    protected $data = array(
        'accountType' => null,
        'routingNumber' => null,
        'accountNumber' => null,
        'nameOnAccount' => null,
        'echeckType' => null,
        'bankName' => null,
    );
    
    protected $dataMasks = array(
        'routingNumber' => -4,
        'accountNumber' => -4,
    );
    
    public function toString() {
        return (string) $this->getMaskedData('accountNumber');
    }

}
