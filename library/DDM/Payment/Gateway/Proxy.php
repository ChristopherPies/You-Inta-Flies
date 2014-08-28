<?php

class DDM_Payment_Gateway_Proxy {
    
    /**
     * Actual gateway class
     * 
     * @var DDM_Payment_Gateway 
     */
    protected $gateway;
    
    /**
     * Error message to display when gateway cannot be called.
     * 
     * @var string
     */
    protected $errorMessage = 'Could not call the gateway method %s because the gateway is not set.';
    
    public function __construct(DDM_Payment_Gateway $gateway = null, $errorMessage = null) {
        if ($gateway) {
            $this->setGateway($gateway);
        }
        if ($errorMessage) {
            $this->setErrorMessage($errorMessage);
        }
    }
    
    public function __call($name, $arguments) {
        if (!$this->getGateway()) {
            throw new DDM_Payment_Exception_UnexpectedValueException(
                sprintf($this->getErrorMessage(), $name)
            );
        }
        
        return call_user_func_array(array($this->gateway, $name), $arguments);
    }
    
    public function getGateway() {
        return $this->gateway;
    }

    public function setGateway(DDM_Payment_Gateway $gateway = null) {
        $this->gateway = $gateway;
    }
    
    public function getErrorMessage() {
        return $this->errorMessage;
    }

    public function setErrorMessage($errorMessage) {
        $this->errorMessage = $gatewayErrorMessage;
    }
    
}
