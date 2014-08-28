<?php

class DDM_Json_Client
{
    /**
     * Full address of the JSON-RPC service
     * @var string
     */
    protected $_serverAddress;

    /**
     * HTTP Client to use for requests
     * @var Zend_Http_Client
     */
    protected $_httpClient = null;

    /**
     * Request of the last method call
     * @var Zend_Json_Server_Request
     */
    protected $_lastRequest = null;

    /**
     * Response received from the last method call
     * @var DDM_Json_Client_Response
     */
    protected $_lastResponse = null;

    /**
     * Create a new JSON-RPC client to a remote server
     *
     * @param  string $server      Full address of the JSON-RPC service
     * @param  Zend_Http_Client $httpClient HTTP Client to use for requests
     * @return void
     */
    public function __construct($server, Zend_Http_Client $httpClient = null)
    {
        if ($httpClient === null) {
            $this->_httpClient = new Zend_Http_Client();
        } else {
            $this->_httpClient = $httpClient;
        }

        $this->_serverAddress = $server;
    }


    /**
     * Sets the HTTP client object to use for connecting the XML-RPC server.
     *
     * @param  Zend_Http_Client $httpClient
     * @return Zend_Http_Client
     */
    public function setHttpClient(Zend_Http_Client $httpClient)
    {
        return $this->_httpClient = $httpClient;
    }

    /**
     * Gets the HTTP client object.
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient()
    {
        return $this->_httpClient;
    }

   /**
     * The request of the last method call
     *
     * @return Zend_Json_Server_Request
     */
    public function getLastRequest()
    {
        return $this->_lastRequest;
    }


    /**
     * The response received from the last method call
     *
     * @return DDM_Json_Client_Response
     */
    public function getLastResponse()
    {
        return $this->_lastResponse;
    }

    /**
     * Returns the URI used by the client
     *
     * @param boolean $asString OPTIONAL
     *
     * @return Zend_Uri|string
     */
    public function getUri($asString = false)
    {
        $uri = $this->getHttpClient()->getUri($asString);
        if ($uri === null) {
            $uri = $this->_serverAddress;
            if (!$asString) {
                $uri = Zend_Uri::factory($uri);
            }
        }
        return $uri;
    }

    /**
     * Perform an JSON-RPC request and return a response.
     *
     * @param DDM_Json_Client_Request $request
     * @param null|DDM_Json_Client_Response $response
     * @return void
     * @throws DDM_Json_Client_Exception
     */
    public function doRequest($request, $response = null)
    {
        $this->_lastRequest = $request;

        $http = $this->getHttpClient();
        if($http->getUri() === null) {
            $http->setUri($this->_serverAddress);
        }

        $http->setHeaders(array(
            'Content-Type: application/json-rpc; charset=utf-8',
            'Accept: application/json-rpc',
        ));

        if ($http->getHeader('user-agent') === null) {
            $http->setHeaders(array('User-Agent: DDM_Json_Client'));
        }

        $json = $this->_lastRequest->__toString();
        $http->setRawData($json);
        $httpResponse = $http->request(Zend_Http_Client::POST);

        if (! $httpResponse->isSuccessful()) {
            /**
             * Exception thrown when an HTTP error occurs
             * @see DDM_Json_Client_Exception
             */
            require_once 'DDM/Json/Client/Exception.php';
            throw new DDM_Json_Client_Exception(
                                    $httpResponse->getMessage(),
                                    $httpResponse->getStatus());
        }

        if ($response === null) {
            $response = new DDM_Json_Client_Response();
        }
        $this->_lastResponse = $response;
        $this->_lastResponse->loadJson($httpResponse->getBody());
    }

    /**
     * Send an JSON-RPC request to the service (for a specific method)
     *
     * @param  string $method Name of the method we want to call
     * @param  array $params Array of parameters for the method
     * @return mixed
     * @throws DDM_Json_Client_FaultException
     */
    public function call($method, $params=array())
    {
        $request = $this->_createRequest($method, $params);

        $this->doRequest($request);

        if ($this->_lastResponse->isError()) {
            $fault = $this->_lastResponse->getError();
            /**
             * Exception thrown when an JSON-RPC fault is returned
             * @see DDM_Json_Client_FaultException
             */
            require_once 'DDM/Json/Client/FaultException.php';
            throw new DDM_Json_Client_FaultException($fault->getMessage(),
                                                        $fault->getCode());
        }

        return $this->_lastResponse->getResult();
    }

    /**
     * Create request object
     *
     * @return DDM_Json_Client_Request
     */
    protected function _createRequest($method, $params)
    {
        $request = new Zend_Json_Server_Request();
        $request->setMethod($method);
        $request->setParams($params);
        $request->setVersion('2.0');
        $request->setId(1);
        return $request;
    }
}
