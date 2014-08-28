<?php

class DDM_Payment_Exception_ResponseError_AuthorizeNetCim
    extends Exception
    implements DDM_Payment_Exception,
               DDM_Payment_Exception_ResponseError
{
    
    /**
     * The gateway response associated with the exception
     * 
     * @var DDM_Payment_Response_AuthorizeNetCim
     */
    protected $response;
    
    /**
     * Gets the gateway response associated with the exception
     * 
     * @return DDM_Payment_Response_AuthorizeNetCim
     */
    public function getResponse() {
        return $this->response;
    }
    
    /**
     * Sets the gateway response associated with the exception
     * 
     * @param DDM_Payment_Response_AuthorizeNetCim $response 
     */
    public function setResponse($response) {
        if (!$response instanceof DDM_Payment_Response_AuthorizeNetCim) {
            throw new DDM_Payment_Exception_UnexpectedValueException('Invalid response');
        }
        $this->response = $response;
    }

}
