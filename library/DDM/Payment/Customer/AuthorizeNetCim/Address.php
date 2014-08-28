<?php

/**
 * Stores address information in a format needed by Authorize.net's CIM API
 */

class DDM_Payment_Customer_AuthorizeNetCim_Address extends DDM_Payment_Customer_Address
{

    /**
     * Array of data accepted for this address type
     *
     * @var array
     */
    protected $data = array(
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
        'customerAddressId' => null,
    );

    /**
     * The unique id in the $this->data
     *
     * @var string
     */
    protected static $dataIdAlias = 'customerAddressId';
}
