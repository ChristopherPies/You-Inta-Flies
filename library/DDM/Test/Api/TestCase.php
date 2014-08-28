<?php

/**
 * API test case for functional tests using Zend_Http_Client calls.
 * This class automatically adds the DDM_Security params (token, nonce, signature)
 * to every API call.
 *
 * This class uses these config constants:
 * - API_ROOT_URL
 * - API_TOKEN
 * - API_SECRET
 * - API_DEBUG (optional, default=false)
 */
abstract class DDM_Test_Api_TestCase extends DDM_Test_TestCase {

    /**
     * The API path relative to API_ROOT_URL.
     * Example: classifieds/general/listing
     *
     * @var string|null
     */
    protected $apiPath = null;

    /**
     * The client that will be used for the next api call.
     *
     * @var Zend_Http_Client|null
     */
    protected $client = null;

    /**
     * Gets the API path relative to API_ROOT_URL.
     * Override $apiPath or getApiPath to provide the path.
     * Example: classifieds/general/listing
     *
     * @return string
     */
    protected function getApiPath() {
        if ($this->apiPath === null) {
            throw new Exception('You must set $apiPath to the API path (without method).');
        }
        return $this->apiPath;
    }

    /**
     * Gets the full API URL with $url appended.
     *
     * @return string
     */
    protected function getApiUrl($url = '') {
        return rtrim(API_ROOT_URL, '/') . '/' . trim($this->getApiPath(), '/') . '/' . $url;
    }

    /**
     * Returns an array of default params that will be merged with the params
     * for every API call. Override this to add API-specific params.
     *
     * @return array
     */
    protected function getDefaultApiParams() {
        return array();
    }

    /**
     * Returns an array with DDM_Security token, nonce, and signature.
     * These params are merged with the params for every API call.
     * Override this to disable or change the security params.
     *
     * @return array
     */
    protected function getApiSecurityParams() {
        if (!defined('API_ROOT_URL') || !API_ROOT_URL
            || !defined('API_TOKEN') || !API_TOKEN
            || !defined('API_SECRET') || !API_SECRET
        ) {
            throw new Exception('Missing config constant API_ROOT_URL, API_TOKEN, or API_SECRET');
        }

        $nonce = DDM_Security_Nonce::generateNonce();
        $signature = DDM_Security_Signature::generateSignature(API_TOKEN, $nonce, API_SECRET);
        return array('t' => API_TOKEN, 'n' => $nonce, 's' => $signature);
    }

    /**
     * Gets the client that will be used for the next api call.
     *
     * @return Zend_Http_Client
     */
    protected function getClient() {
        if (!$this->client) {
            $this->client = new Zend_Http_Client();
        }
        return $this->client;
    }

    /**
     * Sets the client that will be used for the next api call.
     *
     * @param Zend_Http_Client $client
     */
    protected function setClient(Zend_Http_Client $client) {
        $this->client = $client;
    }

    /**
     * Executes a request using Zend_Http_Client
     *
     * @param array $data Request data in the form array(param => value). OPTIONAL
     *
     * @return string Response body
     *
     * @throws \Exception
     */
    protected function execute($url, array $data = array(), $method = 'GET') {
        $client = $this->getClient();
        $client->setUri($url);

        $paramSetMethod = 'setParameter' . ucfirst(strtolower($method));
        foreach ($data as $key => $value) {
            $client->$paramSetMethod($key, $value);
        }

        $response = $client->request($method);
        if (!$response->isSuccessful()) {
            throw new Exception(sprintf(
                'HTTP Error %s %s for the URI %s',
                $response->getStatus(),
                $response->getMessage(),
                $url
            ));
        }

        return $response->getBody();
    }

    /**
     * Calls the API and returns the decoded result.
     *
     * The full API url for the call will be:
     * API_ROOT_URL + $this->apiPath + $url
     *
     * The params for the call will be:
     * $this->getDefaultApiParams() + $this->getApiSecurityParams() + $data
     *
     * @param string $url OPTIONAL
     * @param array $data OPTIONAL
     * @param string $requestMethod OPTIONAL
     * @param boolean $failOnError OPTIONAL
     *
     * @return mixed Result
     */
    protected function callApi($url = '', $data = array(), $requestMethod = 'GET', $failOnError = true) {
        $fullUrl = $this->getApiUrl($url);
        $data = array_merge($this->getDefaultApiParams(), $this->getApiSecurityParams(), $data);
        $rawResponse = $this->execute($fullUrl, $data, $requestMethod);
        $debug = defined('API_DEBUG') && API_DEBUG;

        // setup details for dashboard listener
        $this->setLocation($fullUrl);
        $this->setStatusDetails($rawResponse);

        if (!empty($rawResponse)) {
            $response = json_decode($rawResponse, true);
            if (!empty($response)) {
                if ($this->handleApiResponse($response)) {
                    if ($debug) {
                        echo sprintf(
                            '%4$s*** API success: %1$s *** %4$sParams: %2$s %4$sResponse: %3$s %4$s*** End %1$s ***%4$s',
                            $fullUrl,
                            var_export($data, true),
                            var_export($response, true),
                            PHP_EOL
                        );
                    }
                    return $response;
                }
            }
        }

        if ($failOnError || $debug) {
            $error = sprintf(
                '%4$s*** API ERROR: %1$s *** %4$sParams: %2$s %4$sResponse: %3$s %4$s*** End %1$s ***%4$s',
                $fullUrl,
                var_export($data, true),
                var_export((!empty($response) ? $response : $rawResponse), true),
                PHP_EOL
            );
            if ($failOnError) {
                $this->fail($error);
            } else {
                echo $error;
            }
        }

        return null;
    }

    /**
     * Handles the decoded API response and returns true if it's a successful call.
     * Override to do API-specific stuff.
     *
     * @param array $response
     *
     * @return boolean
     */
    protected function handleApiResponse(&$response) {
        return true;
    }

}
