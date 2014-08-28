<?php

/**
 * A simple interface to work with API's through an HTTP Client
 */
abstract class DDM_Api_Abstract {
    /**
     * A link to the client that sends out the actual calls
     *
     * @var DDM_Api_Client_Interface
     */
    protected $_client;

    /**
     * Constructor
     *
     * @param DDM_Api_Client_Interface $client
     *
     * @return void
     */
    public function __construct(DDM_Api_Client_Interface $client = null) {
        $this->setClient($client);
    }

    /**
     * Returns the API
     *
     * @return DDM_Api_Abstract
     */
    public function getClient() {
        return $this->_client;
    }

    /**
     * Sets the API
     *
     * @param DDM_Api_Abstract $api
     */
    public function setClient(DDM_Api_Client_Interface $client) {
        $this->_client = $client;
    }

    /**
     * Send out the call to the RPC Client
     *
     * @param string $method
     * @param array $params
     *
     * @return mixed
     */
    public function __call($method, array $params) {
        // Make sure all parameters for the http request from any previous calls are wiped out
        $this->getClient()->getHttpClient()->resetParameters();
        return $this->getClient()->call($method, $params);
    }
}
