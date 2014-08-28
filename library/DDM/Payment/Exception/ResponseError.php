<?php

interface DDM_Payment_Exception_ResponseError {
    
    /**
     * Gets the gateway response associated with the exception
     * 
     * @return mixed
     */
    public function getResponse();
    
    /**
     * Sets the gateway response associated with the exception
     * 
     * @param mixed $response 
     */
    public function setResponse($response);

}
