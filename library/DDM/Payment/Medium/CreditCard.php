<?php

class DDM_Payment_Medium_CreditCard extends DDM_Payment_Medium_Abstract {
    
    protected $data = array(
        'cardNumber' => null,
        'expirationDate' => null,
        'cardCode' => null,
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
