<?php

class DDM_Payment_Gateway_AuthorizeNetCim extends DDM_Payment_Gateway_Abstract
{

    const LIVE_URL = 'https://api.authorize.net/xml/v1/request.api';
    const SANDBOX_URL = 'https://apitest.authorize.net/xml/v1/request.api';

    const VALIDATION_NONE = 'none';
    const VALIDATION_TESTMODE = 'testMode';
    const VALIDATION_LIVEMODE = 'liveMode';

    const SPLIT_TENDER_STATUS_VOIDED = 'voided';
    const SPLIT_TENDER_STATUS_COMPLETED = 'completed';

    const TRANSACTION_TYPE_AUTH = 'AuthOnly';
    const TRANSACTION_TYPE_AUTHCAPTURE = 'AuthCapture';
    const TRANSACTION_TYPE_CAPTURE = 'CaptureOnly';
    const TRANSACTION_TYPE_PRIORAUTHCAPTURE = 'PriorAuthCapture';
    const TRANSACTION_TYPE_REFUND = 'Refund';
    const TRANSACTION_TYPE_VOID = 'Void';

    /**
     * Client responsible for making the request
     *
     * @var Zend_Http_Client
     */
    protected $httpClient;

    /**
     * @var boolean
     */
    protected $authorizeNetSandbox = true;

    /**
     * @var string
     */
    protected $authorizeNetName;

    /**
     * @var string
     */
    protected $authorizeNetTransactionKey;

    /**
     * @var string
     */
    protected $refId;

    /**
     * @param string
     */
    protected $validationMode = self::VALIDATION_NONE;

    /**
     * Constructor
     *
     * @param array $options
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
    }

    public function auth(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_AUTH
        );
    }

    public function authCapture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_AUTHCAPTURE
        );
    }

    public function capture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_CAPTURE
        );
    }

    public function priorAuthCapture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_PRIORAUTHCAPTURE
        );
    }

    public function refund(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_REFUND
        );
    }

    public function void(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice)
    {
        return $this->createCustomerProfileTransaction(
            DDM_Payment_Transaction_AuthorizeNetCim::createFromMediumAndInvoice($medium, $invoice),
            self::TRANSACTION_TYPE_VOID
        );
    }

    public function getCustomerProfileIds()
    {
        $dom = $this->getDom('getCustomerProfileIdsRequest', false);
        $response = $this->execute($dom);
        $ids = (array)$response->getXml()->ids;
        return isset($ids['numericString'])
               ? array_map('intval', (array)$ids['numericString'])
               : array();
    }

    public function createCustomerProfile(DDM_Payment_Customer_AuthorizeNetCim $profile)
    {
        if ($profile->getCustomerProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'The customer already has a profile with the id '
                . $profile->getCustomerProfileId()
            );
        }

        $dom = $this->getDom('createCustomerProfileRequest', false);
        $dom->documentElement->appendChild(
            $this->convertToElement($profile->toArray(), $dom, 'profile')
        );

        $validation = count($profile->getPaymentProfiles()) > 0;
        $response = $this->execute($dom, $validation);

        $paymentProfileIds = (array)$response->getXml()->customerPaymentProfileIdList;
        $paymentProfileIds = isset($paymentProfileIds['numericString'])
                           ? array_map('intval', (array)$paymentProfileIds['numericString'])
                           : array();

        $shippingAddressIds = (array)$response->getXml()->customerShippingAddressIdList;
        $shippingAddressIds = isset($shippingAddressIds['numericString'])
                            ? array_map('intval', (array)$shippingAddressIds['numericString'])
                            : array();

        return array(
            'customerProfileId' => (int)$response->getXml()->customerProfileId,
            'customerPaymentProfileIds' => $paymentProfileIds,
            'customerShippingAddressIds' => $shippingAddressIds,
        );
    }

    public function getCustomerProfile($profileId)
    {
        $dom = $this->getDom('getCustomerProfileRequest', $profileId);
        $response = $this->execute($dom);
        return new DDM_Payment_Customer_AuthorizeNetCim($response->getData('profile'));
    }

    public function updateCustomerProfile(DDM_Payment_Customer_AuthorizeNetCim $profile)
    {
        if (!$profile->getCustomerProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'The profile must have a customerProfileId to update.'
            );
        }

        // clear data that can't be updated
        $profile->setData(array(
            'paymentProfiles' => null,
            'shipToList' => null,
        ));

        $dom = $this->getDom('updateCustomerProfileRequest');
        $dom->documentElement->appendChild(
            $this->convertToElement($profile->toArray(), $dom, 'profile')
        );

        $this->execute($dom);
    }

    public function deleteCustomerProfile($profileId)
    {
        $dom = $this->getDom('deleteCustomerProfileRequest', $profileId);
        $this->execute($dom);
    }

    public function createCustomerPaymentProfile($profileId,
        DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile $paymentProfile
    ) {
        if ($paymentProfile->getCustomerPaymentProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Cannot create a payment profile that already has a customerPaymentProfileId ('
                . $paymentProfile->getCustomerPaymentProfileId() . ')'
            );
        }

        $dom = $this->getDom('createCustomerPaymentProfileRequest', $profileId);
        $dom->documentElement->appendChild(
            $this->convertToElement($paymentProfile->toArray(), $dom, 'paymentProfile')
        );

        $response = $this->execute($dom, true);
        return (int)$response->getXml()->customerPaymentProfileId;
    }

    public function getCustomerPaymentProfile($profileId, $paymentProfileId)
    {
        $dom = $this->getDom('getCustomerPaymentProfileRequest', $profileId);
        $dom->documentElement->appendChild(
            new DOMElement('customerPaymentProfileId', $paymentProfileId)
        );

        $response = $this->execute($dom);
        return new DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile($response->getData('paymentProfile'));
    }

    public function updateCustomerPaymentProfile($profileId,
        DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile $paymentProfile
    ) {
        if (!$paymentProfile->getCustomerPaymentProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Payment profile customerPaymentProfileId is required to update.'
            );
        }

        $dom = $this->getDom('updateCustomerPaymentProfileRequest', $profileId);
        $dom->documentElement->appendChild(
            $this->convertToElement($paymentProfile->toArray(), $dom, 'paymentProfile')
        );

        $this->execute($dom, true);
    }

    public function deleteCustomerPaymentProfile($profileId, $paymentProfileId)
    {
        $dom = $this->getDom('deleteCustomerPaymentProfileRequest', $profileId);
        $dom->documentElement->appendChild(
            new DOMElement('customerPaymentProfileId', $paymentProfileId)
        );
        $this->execute($dom);
    }

    /**
     * Creates a customer shipping address profile
     *
     * @param int $profileId
     * @param DDM_Payment_Customer_AuthorizeNetCim_Address $address
     *
     * @return int
     */
    public function createCustomerShippingAddress($profileId,
        DDM_Payment_Customer_AuthorizeNetCim_Address $address
    ) {
        if ($address->getCustomerAddressId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Cannot create a shipping address that already has a customerAddressId ('
                . $address->getCustomerAddressId() . ')'
            );
        }

        $dom = $this->getDom('createCustomerShippingAddressRequest', $profileId);
        $dom->documentElement->appendChild(
            $this->convertToElement($address->toArray(), $dom, 'address')
        );

        $response = $this->execute($dom);
        return (int)$response->getXml()->customerAddressId;
    }

    /**
     * Gets a specified customer shipping address
     *
     * @param int $profileId
     * @param int $addressId
     *
     * @return DDM_Payment_Customer_AuthorizeNetCim_Address
     */
    public function getCustomerShippingAddress($profileId, $addressId)
    {
        $dom = $this->getDom('getCustomerShippingAddressRequest', $profileId);
        $dom->documentElement->appendChild(
            new DOMElement('customerAddressId', $addressId)
        );

        $response = $this->execute($dom);
        return new DDM_Payment_Customer_AuthorizeNetCim_Address($response->getData('address'));
    }

    /**
     * Updates a specified customer shipping address
     *
     * @param int $profileId
     * @param DDM_Payment_Customer_AuthorizeNetCim_Address $address
     *
     * @return void
     *
     * @throws DDM_Payment_Exception_UnexpectedValueException
     */
    public function updateCustomerShippingAddress($profileId,
        DDM_Payment_Customer_AuthorizeNetCim_Address $address
    ) {
        if (!$address->getCustomerAddressId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Shipping address must have a customerAddressId to update.'
            );
        }

        $dom = $this->getDom('updateCustomerShippingAddressRequest', $profileId);
        $dom->documentElement->appendChild(
            $this->convertToElement($address->toArray(), $dom, 'address')
        );

        $this->execute($dom);
    }

    /**
     * Deletes a specified customer shipping address
     *
     * @param int $profileId
     * @param int $addressId
     *
     * @return void
     */
    public function deleteCustomerShippingAddress($profileId, $addressId)
    {
        $dom = $this->getDom('deleteCustomerShippingAddressRequest', $profileId);
        $dom->documentElement->appendChild(
            new DOMElement('customerAddressId', $addressId)
        );
        $this->execute($dom);
    }

    /**
     * Validates a payment profile by creating a test transaction. This returns
     * the response object (instead of throwing an exception) even if
     * validation fails.
     *
     * @param int $paymentProfileId
     * @param int $shippingAddressId OPTIONAL
     * @param int $cardCode 3-4 digit card code. OPTIONAL
     * @param string $validationMode One of the VALIDATION mode constants. OPTIONAL
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     */
    public function validateCustomerPaymentProfile($profileId, $paymentProfileId,
        $shippingAddressId = null, $cardCode = null
    ) {
        if (empty($profileId) || empty($paymentProfileId)) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'To validate, customerProfileId and customerPaymentProfileId are required.'
            );
        }

        $validationMode = $this->getValidationMode();
        if (empty($validationMode) || $validationMode == self::VALIDATION_NONE) {
            $validationMode = self::VALIDATION_TESTMODE;
        }

        $dom = $this->getDom('validateCustomerPaymentProfileRequest', $profileId);
        $dom->documentElement->appendChild(
            new DOMElement('customerPaymentProfileId', $paymentProfileId)
        );
        if ($shippingAddressId) {
            $dom->documentElement->appendChild(
                new DOMElement('customerShippingAddressId', $shippingAddressId)
            );
        }
        if ($cardCode) {
            $dom->documentElement->appendChild(
                new DOMElement('cardCode', $cardCode)
            );
        }

        try {
            $response = $this->execute($dom, $validationMode);
        } catch (DDM_Payment_Exception_ResponseError_AuthorizeNetCim $ex) {
            // validation failed...
            $response = $ex->getResponse();
        }
        return $response->getDirectResponse();
    }

    /**
     * Creates a transaction using the customer profile
     *
     * @param DDM_Payment_Transaction_AuthorizeNetCim $transaction
     * @param string $type One of the TRANSACTION_TYPE constants
     * @param string|array $extraOptions A query string or an array of
     *                                   name/value pairs. OPTIONAL
     *
     * @return DDM_Payment_Response_AuthorizeNetAim
     *
     * @throws DDM_Payment_Exception_UnexpectedValueException
     */
    public function createCustomerProfileTransaction(
        DDM_Payment_Transaction_AuthorizeNetCim $transaction,
        $type, $extraOptions = array()
    ) {
        // replace 'Only' so 'AuthOnly' becomes 'Auth' and so on
        $typeConst = 'TRANSACTION_TYPE_' . str_replace('ONLY', '', strtoupper($type));
        if (defined('self::' . $typeConst)) {
            $type = constant('self::' . $typeConst);
        } else {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Invalid transaction type ' . $type
            );
        }

        if (!$transaction->getCustomerProfileId() || !$transaction->getCustomerPaymentProfileId()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'You must set the customerProfileId and customerPaymentProfileId for the transaction.'
            );
        }

        // get transaction data based on type
        $dataMethodName = 'get' . str_replace('Only', '', $type) . 'Data';
        if (method_exists($transaction, $dataMethodName)) {
            $data = $transaction->$dataMethodName();
        } else {
            $data = $transaction->getData();
        }

        // data checks
        if ($type == self::TRANSACTION_TYPE_CAPTURE && empty($data['approvalCode'])) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'You must set the approvalCode for auth type ' . $type
            );
        }
        if (($type == self::TRANSACTION_TYPE_PRIORAUTHCAPTURE
             || $type == self::TRANSACTION_TYPE_VOID) && empty($data['transId'])
        ) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'You must set the transId for auth type ' . $type
            );
        }

        $dom = $this->getDom('createCustomerProfileTransactionRequest');
        $transElem = $dom->documentElement->appendChild(new DOMElement('transaction'));
        $transElem->appendChild(
            $this->convertToElement($data, $dom, 'profileTrans' . $type)
        );

        $extraOptionNames = array();
        if (!empty($extraOptions)) {
            if (is_array($extraOptions)) {
                $extraOptionNames = array_keys($extraOptions);
                $extraOptions = http_build_query($extraOptions);
            } else {
                parse_str($extraOptions, $extraOptionNames);
                $extraOptionNames = array_keys($extraOptionNames);
            }
            $extraOptionsElem = $dom->documentElement->appendChild(
                new DOMElement('extraOptions')
            );
            $extraOptionsElem->appendChild($dom->createCDATASection($extraOptions));
        }

        $response = $this->execute($dom);
        return $response->getDirectResponse($extraOptionNames);
    }

    public function updateSplitTenderGroup($splitTenderId, $splitTenderStatus)
    {
        $statusConst = 'SPLIT_TENDER_STATUS_' . strtoupper($splitTenderStatus);
        if (defined('self::' . $statusConst)) {
            $splitTenderStatus = constant('self::' . $statusConst);
        } else {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                'Invalid splitTenderStatus ' . $splitTenderStatus
            );
        }


        $dom = $this->getDom('updateSplitTenderGroupRequest', false);
        $dom->documentElement->appendChild(
            new DOMElement('splitTenderId', $splitTenderId)
        );
        $dom->documentElement->appendChild(
            new DOMElement('splitTenderStatus', $splitTenderStatus)
        );

        $this->execute($dom);
    }

    /**
     * Gets a DOMDocument for building a request. This automatically includes
     * the Authorize.net credentials and, optionally, the customerProfileId.
     *
     * @param string $rootName
     * @param boolean $customerProfileId
     *
     * @return DOMDocument
     */
    protected function getDom($rootName, $customerProfileId = null)
    {
        $dom = new DOMDocument('1.0', 'utf-8');
        $elem = $dom->appendChild($dom->createElement($rootName));
        $elem->setAttribute('xmlns', 'AnetApi/xml/v1/schema/AnetApiSchema.xsd');

        $authElem = $elem->appendChild($dom->createElement('merchantAuthentication'));
        $authElem->appendChild(new DOMElement('name', $this->getAuthorizeNetName()));
        $authElem->appendChild(new DOmElement('transactionKey', $this->getAuthorizeNetTransactionKey()));

        if ($this->getRefId()) {
            $elem->appendChild(new DOMElement('refId', $this->getRefId()));
        }

        if ($customerProfileId) {
            $elem->appendChild(new DOMElement('customerProfileId', $customerProfileId));
        }

        return $dom;
    }

    /**
     * Converts the $value to a DOMElement
     *
     * @param mixed $value The value to convert (usually an associative array)
     * @param DOMDocument $dom The DOMDocument to use for element creation
     * @param string $elemName
     * @param boolean $includeEmpty Exclude empty values. OPTIONAL
     *
     * @return DOMElement
     */
    protected function convertToElement($value, $dom, $elemName, $includeEmpty = false)
    {
        $elem = $dom->createElement($elemName);

        if ($includeEmpty || $value || $value === '0') {
            if (is_array($value)) {
                foreach ($value as $childKey => $childValue) {
                    if (!is_numeric($childKey) && ($includeEmpty || $childValue || $childValue === '0')) {
                        if (is_array($childValue) && isset($childValue[0]) && isset($childValue[count($childValue)-1])) {
                            // if the value array is int indexed, build elements
                            // with the same key so 'Name' => array(1, 2) becomes
                            // <Name>1</Name><Name>2</Name>
                            foreach ($childValue as $child) {
                                $elem->appendChild($this->convertToElement($child, $dom, $childKey));
                            }
                        } else {
                            $elem->appendChild($this->convertToElement($childValue, $dom, $childKey));
                        }
                    }
                }
            } else {
                // recreate the element using the DOMElement constructor so we
                // can catch the exception if the $value is not string-able
                try {
                    $elem = new DOMElement($elemName, $value);
                } catch (DOMException $ex) {
                    // do nothing - this element will just be empty
                }
            }
        }

        return $elem;
    }

    /**
     * Sends the request to Authorize.net and returns the response. If validation
     * occured, the validation responses are in $response->getValidationResponses().
     * If validation fails, however, an exception will be thrown, and the
     * validation exceptions are available through $exception->getPrevious().
     *
     * @param DOMDocument $dom
     * @param boolean|string $validation Use validation mode. Can pass a string
     *                                   to use a custom validation mode.
     *
     * @return DDM_Payment_Response_AuthorizeNetCim
     *
     * @throws DDM_Payment_Exception_GatewayError
     */
    protected function execute($dom, $validation = false)
    {
        if ($validation && $validation !== self::VALIDATION_NONE) {
            $validationMode = (is_string($validation))
                            ? $validation : $this->getValidationMode();
            $dom->documentElement->appendChild(
                new DOMElement('validationMode', $validationMode)
            );
        } else {
            $validation = false;
        }

        $dom->formatOutput = $this->getDebug();
        $requestXml = $dom->saveXML();
        $this->debug("AuthorizeNetCim Request: \n" . $requestXml, true);

        $uri = $this->getAuthorizeNetSandbox() ? self::SANDBOX_URL : self::LIVE_URL;
        $client = $this->getHttpClient();
        $client->setUri($uri);
        $client->setRawData($requestXml);
        $client->setHeaders('Content-Type: text/xml');
        $httpResponse = $client->request(Zend_Http_Client::POST);

        $response = new DDM_Payment_Response_AuthorizeNetCim($httpResponse->getBody());
        $exception = null;

        if ($this->getDebug()) {
            $responseDom = dom_import_simplexml($response->getXml())->ownerDocument;
            $responseDom->formatOutput = true;
            $this->debug("AuthorizeNetCim Response: \n" . $responseDom->saveXML(), true);
        }

        if ($validation) {
            foreach ($response->getValidationDirectResponses() as $valResponse) {
                if (!$valResponse->isSuccess()) {
                    $exception = new DDM_Payment_Exception_ResponseError_AuthorizeNetCim(
                        $valResponse->getMessage(), null, $exception
                    );
                    $exception->setResponse($response);
                }
            }
        }

        if (!$response->isSuccess()) {
            $exception = new DDM_Payment_Exception_ResponseError_AuthorizeNetCim(
                $response->getMessage(), null, $exception
            );
            $exception->setResponse($response);
        }

        if ($exception) {
            throw $exception;
        }

        return $response;
    }

    public function getHttpClient()
    {
        if ($this->httpClient === null) {
             $this->httpClient = new Zend_Http_Client();
        }
        return $this->httpClient;
    }

    public function setHttpClient(Zend_Http_Client $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function getAuthorizeNetSandbox()
    {
        return $this->authorizeNetSandbox;
    }

    public function setAuthorizeNetSandbox($authorizeNetSandbox)
    {
        $this->authorizeNetSandbox = (bool)$authorizeNetSandbox;
    }

    public function getAuthorizeNetName()
    {
        return $this->authorizeNetName;
    }

    public function setAuthorizeNetName($authorizeNetName)
    {
        $this->authorizeNetName = $authorizeNetName;
    }

    public function getAuthorizeNetTransactionKey()
    {
        return $this->authorizeNetTransactionKey;
    }

    public function setAuthorizeNetTransactionKey($authorizeNetTransactionKey)
    {
        $this->authorizeNetTransactionKey = $authorizeNetTransactionKey;
    }

    public function getRefId()
    {
        return $this->refId;
    }

    public function setRefId($refId)
    {
        $this->refId = $refId;
    }

    public function getValidationMode()
    {
        return $this->validationMode;
    }

    public function setValidationMode($validationMode)
    {
        if ($validationMode === null) {
            $validationMode = 'none';
        }
        $const = 'self::VALIDATION_' . strtoupper($validationMode);
        if (defined($const)) {
            $this->validationMode = constant($const);
        }
    }

}
