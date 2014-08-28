<?php

use DDM\Api\Exceptions\InvalidArgumentException;

/**
 * Provides a simple interface to work with DDM API's that expect to get a nonce, token, and signature in the get string.
 */
class DDM_Api_Nonce extends DDM_Api_Abstract {
    /**
	 * The private key assigned to the client for use with DDM_Nonce_APIs
     * @var string
	 */
	protected $_secret;

	/**
	 * The public key used with DDM_Nonce_APIs
     * @var string
	 */
	protected $_token;

    /**
	 * Constructor for DDM_Api_Protocol_Abstract
	 *
     * @param DDM_Api_Client_Interface $client
	 * @param string $token
	 * @param string $secret
	 */
	public function __construct(DDM_Api_Client_Interface $client, $token, $secret)
    {
        parent::__construct($client);
        $this->setAuth($token, $secret);
	}

    /**
     * Sets the public key used with DDM_Nonce_APIs
     *
     * @return string
     */
    public function getToken()
    {
        return $this->_token;
    }

    /**
     * Sets the private key used with DDM_Nonce_APIs
     *
     * @return string
     */
    public function getSecret()
    {
        return $this->_secret;
    }

    /**
     * Combined setter for Token and Secret -- these should be set together so
     * they don't get out of sync
     *
     * @param string $token
     * @param string $secret
     *
     * @throws DDM/Api/Exceptions/InvalidArgumentException
     */
    public function setAuth($token, $secret)
    {
        if (empty($token)) {
            throw new InvalidArgumentException('An api token is required for the nonce protocol.');
        }

        if (empty($secret)) {
            throw new InvalidArgumentException('An api secret is required for the nonce protocol.');
        }

        $this->_token = $token;
        $this->_secret = $secret;
    }

    /**
     * Updates the HTTP Client to work with the DDM Nonce APIs. Adds on token, nonce, and signature to the get string
     *
     * @param Zend_Http_Client $client
     * @param string $authUserToken OPTIONAL
     *
     * @return Zend_Http_Client $client
     */
    public function updateClientWithSignature(Zend_Http_Client $client, $authUserToken = null)
    {
        $authUserToken = ($authUserToken === null) ? 0 : $authUserToken;

        // Protocol-specific parameter data
        $get = array();
        $get['t'] = $this->getToken();
        $get['n'] = DDM_Security_Nonce::generateNonce();
        if (!empty($authUserToken)) {
            $get['u'] = $authUserToken;
        }
        $get['s'] = DDM_Security_Signature::generateSignature($get['t'], $get['n'], $this->getSecret(), $authUserToken);
        $client->setParameterGet($get);

        return $client;
    }

    /**
     * Removes the authUserToken from params and returns its value
     *
     * @param array $params
     *
     * @return string
     */
    protected function parseAuthUserToken(array &$params)
    {
        $authUserToken = null;
        $authUserTokenKeys = array(
            'authUserToken',
            'authToken',
            'u',
        );
        foreach ($authUserTokenKeys as $authUserTokenKey) {
            if (array_key_exists($authUserTokenKey, $params)) {
                $authUserToken = $params[$authUserTokenKey];
                unset($params[$authUserTokenKey]);
                break;
            }
        }

        return $authUserToken;
    }

    /**
     * Send out the call to the RPC Client
     *
     * @param string $method
     * @param array $params
     *
     * @return mixed
     */
    public function __call($method, array $params)
    {
        $authUserToken = $this->parseAuthUserToken($params);

        // Make sure all parameters for the http request from any previous calls are wiped out
        $client = $this->getClient()->getHttpClient();
        $client->resetParameters();
        $this->updateClientWithSignature($client, $authUserToken);

        return $this->getClient()->call($method, $params);
    }
}
