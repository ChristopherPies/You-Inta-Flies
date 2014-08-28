<?php

/**
 * Interface declaring the necessary methods for a Client adapter enabling
 * communication through the "DDM Api" library.
 *
 * The DDM Api library depends on Zend_Http_Client. It must be able to modify
 * the GET and POST data or manipulate the URI, depending on the requirements
 * of the Scheme.
 */
interface DDM_Api_Client_Interface {
    /**
     * Sets the Http Client
     *
     * @param Zend_Http_Client $httpClient
     */
    public function setHttpClient(Zend_Http_Client $httpClient);

    /**
     * Returns the Http Client
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient();

    /**
     * Calls a method on the api
     *
     * @param string $method
     * @param array $params OPTIONAL
     *
     * @return mixed
     */
    public function call($method, $params = array());
}
