<?php

class DDM_Payment_Customer_AuthorizeNetCim
    extends DDM_Payment_Customer_Abstract
    implements DDM_Payment_Customer_Profile {

    protected $data = array(
        'merchantCustomerId' => null,
        'description' => null,
        'email' => null,
        'paymentProfiles' => null,
        'shipToList' => null,
        'customerProfileId' => null,
    );

    protected static $dataClassMap = array(
        'paymentProfiles[]' => 'DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile',
        'shipToList[]' => 'DDM_Payment_Customer_AuthorizeNetCim_Address',
    );

    protected static $dataIdAlias = 'customerProfileId';

    /**
     * Whether we've connected to Authorize.net to fetch live customer data
     *
     * @var boolean
     */
    protected $isConnected = false;

    /**
     * The gateway proxy to DDM_Payment_Gateway_AuthorizeNetCim
     *
     * @var DDM_Payment_Gateway_Proxy
     */
    protected $gateway;

    /**
     * @var DDM_Payment_Customer_Address
     */
    protected $defaultBillingAddress;

    /**
     * @var DDM_Payment_Customer_Address
     */
    protected $defaultShippingAddress;

    /**
     * Constructor
     *
     * @param array $data
     */
    public function __construct(array $data = array()) {
        parent::__construct($data);

        $this->gateway = new DDM_Payment_Gateway_Proxy();

        // set options that are not part of data array
        if (isset($data['gateway'])) {
            $this->setGateway($data['gateway']);
        }

        // if a gateway was provided, and we have enough identifying data,
        // automatically get the user profile, creating it first if needed.
        if ($this->getGateway() && ($this->getCustomerProfileId()
            || $this->getMerchantCustomerId() || $this->getEmail())
        ) {
            if (!$this->getCustomerProfileId()) {
                $this->createProfile($data);
            }
            $this->getProfile();
        }
    }

    public function isConnected() {
        return $this->isConnected;
    }

    public function getGateway() {
        return $this->gateway->getGateway();
    }

    public function setGateway(DDM_Payment_Gateway_AuthorizeNetCim $gateway) {
        $this->gateway->setGateway($gateway);
    }

    public function getDefaultBillingAddress() {
        if ($this->defaultBillingAddress === null) {
            foreach($this->getPaymentProfiles() as $profile) {
                if ($profile->getBillTo()) {
                    $this->defaultBillingAddress = $profile->getBillTo();
                    break;
                }
            }
        }
        return $this->defaultBillingAddress;
    }

    public function setDefaultBillingAddress(DDM_Payment_Customer_Address $address) {
        $this->defaultBillingAddress = $address;
    }

    public function getDefaultShippingAddress() {
        if ($this->defaultShippingAddress === null) {
            $addresses = $this->getShippingAddresses();
            if (!empty($addresses)) {
                $this->defaultShippingAddress = reset($addresses);
            }
        }
        return $this->defaultShippingAddress;
    }

    public function setDefaultShippingAddress(DDM_Payment_Customer_Address $address) {
        $this->defaultShippingAddress = $address;
    }

    protected function requireCustomerProfileId($action = null) {
        if (!$this->getCustomerProfileId()) {
            $action = $action ?: 'do that';
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'The customer\'s customerProfileId is required to ' . $action . '.'
            );
        }
    }

    /**
     * Alias for getCustomerProfileId()
     */
    public function getProfileId() {
        return $this->getCustomerProfileId();
    }

    /**
     * Alias for setCustomerProfileId()
     */
    public function setProfileId($id) {
        $this->setCustomerProfileId($id);
    }

    public function getProfile() {
        $this->requireCustomerProfileId('get the profile');
        $customer = $this->gateway->getCustomerProfile($this->getCustomerProfileId());
        $this->setData($customer->getData());
        $this->isConnected = true;
    }

    public function createProfile($options = null) {
        if ($this->getCustomerProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'The customer already has a profile with the id of '
                . $this->getCustomerProfileId()
            );
        }
        if (!$this->getMerchantCustomerId() && !$this->getEmail()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Please provide a merchantCustomerId and/or email to create a profile'
            );
        }

        $result = $this->gateway->createCustomerProfile($this);
        $profileId = $result['customerProfileId'];
        $this->setCustomerProfileId($profileId);

        return $profileId;
    }

    public function updateProfile($options = null) {
        $this->requireCustomerProfileId('update the profile');
        $this->gateway->updateCustomerProfile($this);
    }

    public function deleteProfile() {
        $this->requireCustomerProfileId('delete the profile');
        $this->gateway->deleteCustomerProfile($this->getCustomerProfileId());
        $this->setCustomerProfileId(null);
        $this->isConnected = false;
    }

    public function getPaymentProfiles(){
        return (array)parent::getPaymentProfiles();
    }

    public function getPaymentProfileById($paymentProfileId) {
        if ($this->getPaymentProfiles()) {
            foreach ($this->getPaymentProfiles() as $profile) {
                if ($profile->getCustomerPaymentProfileId() == $paymentProfileId) {
                    return $profile;
                }
            }
        }
        return null;
    }

    public function getPaymentProfileMatch(DDM_Payment_Customer_PaymentProfile $paymentProfile) {
        return $this->getPaymentProfileByData($paymentProfile->toArray());
    }

    /**
     * Gets the payment profile that matches the data
     *
     * @param array $data
     *
     * @return DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile|null
     */
    public function getPaymentProfileByData(array $data) {
        /* @TODO this matches a credit card based only on the last 4 digits of the
         * card number because everything else is masked in the stored profile.
         * is this sufficient? the card code and expiration are not considered,
         * but in terms of uniqueness for authorize.net, those don't matter anyway.
         */

        // payment stuff ...
        $paymentTypeMatchFields = array(
            DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment::TYPE_CREDITCARD => array(
                'cardNumber',
            ),
            DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment::TYPE_BANKACCOUNT => array(
                'accountNumber', 'routingNumber',
            ),
        );

        foreach ($paymentTypeMatchFields as $paymentType => $paymentMatchFields) {
            if (array_key_exists($paymentType, $data['payment'])) {
                break; // $paymentType and $paymentMatchFields have been set by loop.
            }
        }

        $paymentData = $data['payment'][$paymentType];

        // address stuff ...
        $addressMatchFields = array('firstName', 'lastName', 'address', 'zip');
        $addressData = array_intersect_key($data['billTo'], array_fill_keys($addressMatchFields, null));

        // test for matches ...
        $paymentProfiles = $this->getPaymentProfiles();

        foreach ($paymentProfiles as $paymentProfile) {
            // payment ...
            $paymentObject = $paymentProfile->getPayment()->getData($paymentType);
            $isPaymentMatch = $this->isDataObjectMatch(
                $paymentObject, $paymentData, array(), $paymentMatchFields
            );

            if (!$isPaymentMatch) {
                continue;
            }

            // address ...
            $addressObject = $paymentProfile->getBillTo();

            $isAddressMatch = $this->isDataObjectMatch(
                $addressObject, $addressData, $addressMatchFields
            );

            if (!$isAddressMatch) {
                continue;
            }

            return $paymentProfile;
        }

        return null;
    }

    public function getPaymentProfileIds() {
        $ids = array();
        foreach ((array)$this->getPaymentProfiles() as $profile) {
            $ids = $profile->getCustomerPaymentProfileId();
        }
        return $ids;
    }

    public function createPaymentProfile(DDM_Payment_Customer_PaymentProfile $paymentProfile, $options = null) {
        $this->requireCustomerProfileId('create a payment profile');
        return $this->gateway->createCustomerPaymentProfile($this->getCustomerProfileId(), $paymentProfile);
    }

    public function updatePaymentProfile(DDM_Payment_Customer_PaymentProfile $paymentProfile, $options = null) {
        $this->requireCustomerProfileId('update a payment profile');
        $this->gateway->updateCustomerPaymentProfile($this->getCustomerProfileId(), $paymentProfile);
    }

    public function validatePaymentProfile($paymentProfileId, $options = null) {
        $this->requireCustomerProfileId('validate a payment profile');

        $addressId = isset($options['customerShippingAddressId'])
                        ? $options['customerShippingAddressId']
                        : null;
        $cardCode = isset($options['cardCode'])
                        ? $options['cardCode']
                        : null;

        return $this->gateway->validateCustomerPaymentProfile(
            $this->getCustomerProfileId(), $paymentProfileId, $addressId, $cardCode
        );
    }

    public function deletePaymentProfile($paymentProfileId) {
        $this->requireCustomerProfileId('delete a payment profile');
        $this->gateway->deleteCustomerPaymentProfile($this->getCustomerProfileId(), $paymentProfileId);
        foreach ((array)$this->data['paymentProfiles'] as $key => $profile) {
            if ($profile->getCustomerPaymentProfileId() == $paymentProfileId) {
                unset($this->data['paymentProfiles'][$key]);
            }
        }
    }

    public function getShipToList() {
        return (array)parent::getShipToList();
    }

    public function getShippingAddresses() {
        return $this->getShipToList();
    }

    public function getShippingAddressById($addressId) {
        if ($this->getShipToList()) {
            foreach ($this->getShipToList() as $address) {
                if ($address->getCustomerAddressId() == $addressId) {
                    return $address;
                }
            }
        }
        return null;
    }

    public function getShippingAddressMatch(DDM_Payment_Customer_Address $address) {
        return $this->getShippingAddressByData($address->getData());
    }

    public function getShippingAddressByData(array $data) {
        if ($this->getShipToList()) {
            if (isset($data['shippingAddress'])) {
                $data = $data['shippingAddress'];
            }
            $matchFields = array('firstName', 'lastName', 'address', 'city', 'zip', 'phoneNumber');
            foreach ($this->getShipToList() as $address) {
                if ($this->isDataObjectMatch($address, $data, $matchFields)) {
                    return $address;
                }
            }
        }

        return null;
    }

    public function getShippingAddressIds() {
        $ids = array();
        foreach ((array)$this->getShipToList() as $address) {
            $ids = $address->getCustomerAddressId();
        }
        return $ids;
    }

    public function createShippingAddress(DDM_Payment_Customer_Address $address, $options = null) {
        $this->requireCustomerProfileId('create a shipping address');
        return $this->gateway->createCustomerShippingAddress($this->getCustomerProfileId(), $address);
    }

    public function updateShippingAddress(DDM_Payment_Customer_Address $address, $options = null) {
        $this->requireCustomerProfileId('update a shipping address');
        $this->gateway->updateCustomerShippingAddress($this->getCustomerProfileId(), $address);
    }

    public function deleteShippingAddress($addressId) {
        $this->requireCustomerProfileId('delete a shipping address');
        $this->gateway->deleteCustomerShippingAddress($this->getCustomerProfileId(), $addressId);
        foreach ((array)$this->data['shipToList'] as $key => $address) {
            if ($address->getCustomerAddressId() == $addressId) {
                unset($this->data['shipToList'][$key]);
            }
        }
    }

    /**
     * Returns true if the $object matches the provided $data
     *
     * @param DDM_Payment_DataObject $object
     * @param array $data
     * @param array $fields Fields to compare (non-strict)
     * @param array $maskedFields Masked fields to compare, such as account
     *                            numbers. Only the last 4 chars are compared.
     * @return type
     */
    protected function isDataObjectMatch(DDM_Payment_DataObject $object,
        array $data, array $fields, array $maskedFields = array()
    ) {
        foreach ($fields as $field) {
            $value = isset($data[$field]) ? $data[$field] : null;
            if ($value != $object->getData($field)) {
                return false;
            }
        }

        foreach ($maskedFields as $field) {
            $value = isset($data[$field]) ? $data[$field] : null;
            if (substr($value, -4) != substr($object->getData($field), -4)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Gets a payment medium based on the provided data. You can pass credit
     * card or bank account data, and the appropriate type will be returned.
     * This uses the gateway to create payment profiles and shipping addresses
     * as needed. If you pass data that can be matched with a current payment
     * profile and shipping address, nothing will be created. If you don't pass
     * any data, the only thing that will be set is the customerProfileId.
     *
     * @param array|null $data OPTIONAL
     * @param boolean $createPaymentProfile Whether to create a payment profile
     *                                      if a match is not found. OPTIONAL
     *
     * @return DDM_Payment_Medium_AuthorizeNetCim
     */
    public function getMedium(array $data = null, $createPaymentProfile = true) {
        $data = (array)$data;
        $data['customerProfileId'] = $this->getCustomerProfileId();
        if (count($data) === 1) {
            return new DDM_Payment_Medium_AuthorizeNetCim($data);
        }

        if ($createPaymentProfile) {
            $data['gateway'] = $this->getGateway();
        }

        // if payment data is provided, try to find a matching profile
        // otherwise, the Medium will try to create a new profile
        if (empty($data['customerPaymentProfileId'])) {
            $profile = $this->getPaymentProfileByData($data);
            if ($profile) {
                $data['customerPaymentProfileId'] = $profile->getCustomerPaymentProfileId();
            }
        }

        // if a shipping address is provided, try to match it to an existing one
        // otherwise, the Medium will try to create a new address
        if (empty($data['customerShippingAddressId'])) {
            $address = $this->getShippingAddressByData($data);
            if ($address) {
                $data['customerShippingAddressId'] = $address->getCustomerAddressId();
            }
        }

        // determine the type and return the medium
        // if no type can be determined, return a generic one
        return DDM_Payment_Medium_AuthorizeNetCim::createFromPaymentData($data);
    }

    /**
     * Proxy to gateway auth
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function auth(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->auth($medium, $invoice);
    }

    /**
     * Proxy to gateway authCapture
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function authCapture(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->authCapture($medium, $invoice);
    }

    /**
     * Proxy to gateway capture
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function capture(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->capture($medium, $invoice);
    }

    /**
     * Proxy to gateway priorAuthCapture
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function priorAuthCapture(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->priorAuthCapture($medium, $invoice);
    }

    /**
     * Proxy to gateway refund
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function refund(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->refund($medium, $invoice);
    }

    /**
     * Proxy to gateway void
     *
     * @param DDM_Payment_Medium_AuthorizeNetCim $medium
     * @param DDM_Payment_Invoice $invoice
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function void(DDM_Payment_Medium_AuthorizeNetCim $medium, DDM_Payment_Invoice $invoice) {
        return $this->gateway->void($medium, $invoice);
    }

}
