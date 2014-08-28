<?php

class DDM_Payment_Response_AuthorizeNetAim extends DDM_Payment_Response_Abstract {

    const RESPONSE_CODE_APPROVED = 1;
    const RESPONSE_CODE_DECLINED = 2;
    const RESPONSE_CODE_ERROR = 3;
    const RESPONSE_CODE_HELD = 4;
  
    protected $rawData;
    
    protected $data = array(
        'responseCode' => null,
        'responseSubcode' => null,
        'responseReasonCode' => null,
        'responseReasonText' => null,
        'authorizationCode' => null,
        'avsResponse' => null,
        'transactionId' => null,
        'invoiceNumber' => null,
        'description' => null,
        'amount' => null,
        'method' => null,
        'transactionType' => null,
        'customerId' => null,
        'firstName' => null,
        'lastName' => null,
        'company' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'zipCode' => null,
        'country' => null,
        'phone' => null,
        'fax' => null,
        'emailAddress' => null,
        'shipToFirstName' => null,
        'shipToLastName' => null,
        'shipToCompany' => null,
        'shipToAddress' => null,
        'shipToCity' => null,
        'shipToState' => null,
        'shipToZipCode' => null,
        'shipToCountry' => null,
        'tax' => null,
        'duty' => null,
        'freight' => null,
        'taxExempt' => null,
        'purchaseOrderNumber' => null,
        'md5Hash' => null,
        'cardCodeResponse' => null,
        'cavvResponse' => null, // index 39 in response data ...
        'accountNumber' => null, // ... index 50 in response data
        'cardType' => null,
        'splitTenderId' => null,
        'requestedAmount' => null,
        'balanceOnCard' => null,
    );

    /**
     * Constructor. Parses the AuthorizeNet response string.
     *
     * @param string $response      The response string from the AuthNet server
     * @param string $delimiter     The delimiter (default is ',') OPTIONAL
     * @param string $encapChar     The character that encapsulates values OPTIONAL
     * @param array  $customFields  Any custom fields set in the request OPTIONAL
     */
    public function __construct($response, $delimiter = null, $encapChar = null,
        array $customFields = null
    ) {
        $delimiter = $delimiter ?: ',';
        
        if (!empty($response)) {
            $this->rawData = $response;
            
            if ($encapChar) {
                $responseArray = explode($encapChar.$delimiter.$encapChar, substr($response, 1, -1));
            } else {
                $responseArray = explode($delimiter, $response);
            }
            
            if (count($responseArray) < 10) {
                // AuthorizeNet didn't return a delimited response
                $this->setFailure('Unrecognized response from AuthorizeNet: ' . $response);
                return;
            }
            
            // set the response data
            // indexes 40-49 are empty
            $i = 0;
            foreach ($this->data as &$datum) {
                $datum = ($responseArray[$i] !== '') ? $responseArray[$i] : null;
                $i++;
                if ($i === 40) {
                    $i = 50;
                }
            }
            
            // set custom fields from the end of the response data array
            if (!empty($customFields)) {
                $i = count($responseArray) - 1;
                foreach ($customFields as $field) {
                    $this->data[$field] = ($responseArray[$i] !== '') ? $responseArray[$i] : null;
                    $i--;
                }
                
            }
            
            $this->setRemoteId($this->data['transactionId']);
            $this->setCode($this->data['responseCode']);
            
            if ($this->data['responseCode'] == self::RESPONSE_CODE_APPROVED) {
                $this->setSuccess($this->data['responseReasonText']);
            } else {
                $this->setFailure(
                    "AuthorizeNet Error:
                    Response Code: " . $this->data['responseCode'] . "
                    Response Subcode: " . $this->data['responseSubcode'] . "
                    Response Reason Code: " . $this->data['responseReasonCode'] . "
                    Response Reason Text: " . $this->data['responseReasonText']
                );
            }
        } else {
            $this->setFailure('Error connecting to AuthorizeNet');
        }
    }
    
    public function getRawData() {
        return $this->rawData;
    }

    public function getData($key = null) {
        if ($key) {
            if (is_array($key)) {
                $data = array();
                foreach ($key as $k) {
                    if (array_key_exists($k, $this->data)) {
                        $data[$k] = $this->data[$k];
                    }
                }
                return $data;
            }
            
            return $this->data[$key];
        }
        
        return $this->data;
    }
    
    public function isApproved() {
        return $this->data['responseCode'] == self::RESPONSE_CODE_APPROVED;
    }
    
    public function isDeclined() {
        return $this->data['responseCode'] == self::RESPONSE_CODE_DECLINED;
    }
    
    public function isHeld() {
        return $this->data['responseCode'] == self::RESPONSE_CODE_HELD;
    }
    
    public function isError() {
        return $this->data['responseCode'] == self::RESPONSE_CODE_ERROR;
    }
    
    public function isPartial() {
        return $this->data['splitTenderId'] != null;
    }

}
