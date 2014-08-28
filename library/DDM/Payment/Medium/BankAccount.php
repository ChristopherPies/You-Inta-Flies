<?php

class DDM_Payment_Medium_BankAccount extends DDM_Payment_Medium_Abstract {
    
    protected $data = array(
        'accountType' => null,
        'routingNumber' => null,
        'accountNumber' => null,
        'nameOnAccount' => null,
        'echeckType' => null,
        'bankName' => null,
        'firstName' => null,
        'lastName' => null,
        'company' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'zip' => null,
        'country' => null,
        'phoneNumber' => null,
        'faxNumber' => null,
    );
    
}
