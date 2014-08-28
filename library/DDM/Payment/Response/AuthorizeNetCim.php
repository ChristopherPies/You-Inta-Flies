<?php

class DDM_Payment_Response_AuthorizeNetCim extends DDM_Payment_Response_Abstract {
    
    const RESULT_CODE_OK = 'Ok';
    const RESULT_CODE_ERROR = 'Error';
    
    /**
     * The response xml
     * 
     * @var SimpleXmlElement
     */
    protected $xml;
    
    /**
     * The response xml data converted to an array
     * 
     * @var array
     */
    protected $data;
    
    /**
     * Holds the direct transaction response, if any
     * 
     * @var DDM_Payment_Response_AuthorizeNetAim
     */
    protected $directResponse;
    
    /**
     * Holds any validation responses included in the CIM response
     * 
     * @var DDM_Payment_Response_AuthorizeNetAim[]
     */
    protected $validationDirectResponses = null;
    
    /**
     * Constructor.
     * 
     * @param string $response Authorize.net XML response string
     */
    public function __construct($response) {
        $this->xml = @simplexml_load_string(
            preg_replace('/ xmlns:xsi[^>]+/', '', $response)
        );
        
        if (!$this->xml || !isset($this->xml->messages->resultCode)
            || !isset($this->xml->messages->message->code)
            || !isset($this->xml->messages->message->text)
        ) {
            $this->setFailure('Invalid response: ' . $response);
            return;
        }
        
        $code = $this->xml->messages->resultCode;
        $msg = $this->xml->messages->message->code 
               . ': ' . $this->xml->messages->message->text;
        
        if ($code == self::RESULT_CODE_OK) {
            $this->setSuccess($msg);
        } else {
            $this->setFailure($msg);
        }
        
        $this->setCode($code);
    }
    
    /**
     * Get the response data as an array
     * 
     * @param string|array $key The data key to return. If this an array, 
     *                          each item key will be traversed in order.
     * 
     * @return mixed The data array or value for specified $key
     */
    public function getData($key = null) {
        if ($this->data === null) {
            // convert xml to array
            $json = json_encode($this->getXml());
            $this->data = json_decode($json, true);
        }
        
        if ($key) {
            if (is_array($key)) {
                $value = $this->data;
                foreach ($key as $k) {
                    if (is_array($value) && isset($value[$k])) {
                        $value = $value[$k];
                    } else {
                        return null;
                    }
                }
                return $value;
            }
            
            return isset($this->data[$key]) ? $this->data[$key] : null;
        }
        
        return $this->data;
    }
    
    /**
     * Get the response xml
     * 
     * @return SimpleXmlElement
     */
    public function getXml() {
        return $this->xml;
    }
    
    /**
     * Get the refId
     * 
     * @return string|null
     */
    public function getRefId() {
        return isset($this->xml->refId) ? (string)$this->xml->refId : null;
    }
    
    /**
     * Get the message code (response/messages/message/code)
     */
    public function getMessageCode() {
        return isset($this->xml->messages->message->code)
               ? (string)$this->xml->messages->message->code : null;
    }
    
    /**
     * Gets the direct transaction response included in the CIM response, if any
     * 
     * @param array $customFields Custom fields included in the request. OPTIONAL
     * 
     * @return DDM_Payment_Response_AuthorizeNetAim|null
     */
    public function getDirectResponse(array $customFields = null) {
        if ($this->directResponse === null) {
            if (isset($this->xml->directResponse)) {
                return new DDM_Payment_Response_AuthorizeNetAim(
                    (string)$this->xml->directResponse, null, null, $customFields
                );
            }
        }
        
        return $this->directResponse;
    }
    
    /**
     * Gets any validation responses included in the CIM response
     * 
     * @return DDM_Payment_Response_AuthorizeNetAim[]
     */
    public function getValidationDirectResponses() {
        if ($this->validationDirectResponses === null) {
            $this->validationDirectResponses = array();
            if (isset($this->xml->validationDirectResponse)) {
                $this->validationDirectResponses[] = new DDM_Payment_Response_AuthorizeNetAim(
                    (string)$this->xml->validationDirectResponse
                );
            } else if (isset($this->xml->validationDirectResponseList)) {
                foreach ($this->xml->validationDirectResponseList->string as $string) {
                    $this->validationDirectResponses[] = new DDM_Payment_Response_AuthorizeNetAim(
                        (string)$string
                    );
                }
            }
        }
        
        return $this->validationDirectResponses;
    }

}
