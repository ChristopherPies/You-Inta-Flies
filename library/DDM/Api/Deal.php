<?php

/**
 * Provides a simple interface to work with the DDM Deal API
 */
class DDM_Api_Deal extends DDM_Api_Nonce {

    /**
     * Flag that controls whether a service prefix should be added
     *
     * @var boolean
     */
    protected $usesServicePrefix = null;

    /**
     * General purpose call method to use if a specific method is not defined
     *
     * @param string $service
     * @param strin $method
     * @param array $parameters OPTIONAL
     *
     * @return mixed
     */
    public function call($service, $method, array $parameters = array())
    {
        $this->setServicePrefix($service);
        return $this->__call($method, $parameters);
    }

    /**
     * Authenticates a member by their username and password
     *
     * @param string $username
     * @param string $password
     *
     * @return array
     */
    public function authenticate($username, $password)
    {
        $this->setServicePrefix('member');
        return parent::authenticate($username, $password);
    }

    /**
     * Logsout a member by deleting their authUserToken
     *
     * @param string $authUserToken
     *
     * @return boolean
     */
    public function logout($authUserToken)
    {
        $this->setServicePrefix('member');
        return parent::logout($authUserToken);
    }

    /**
     * Gets the service prefix for a call
     *
     * @return string
     */
    protected function getServicePrefix()
    {
        return $this->servicePrefix;
    }

    /**
     * Sets the service prefix for a call
     *
     * @param string $prefix
     */
    protected function setServicePrefix($prefix)
    {
        $this->servicePrefix = $prefix;
    }

    /**
     * Clears the service prefix for a call
     *
     * @return void
     */
    protected function clearServicePrefix()
    {
        $this->setServicePrefix(null);
    }

    /**
     * Returns a flag to tell us if a service prefix is needed for the loaded URL
     *
     * @return boolean
     */
    protected function usesServicePrefix()
    {
        if ($this->usesServicePrefix === null) {
            $uri = $this->getClient()->getUri(true);
            $this->usesServicePrefix = preg_match('~/api(/admin|/public)?/json(rpc)?$~i', $uri);
        }
        return $this->usesServicePrefix;
    }

    /**
     * Returns the name of the method with the prefixed service if applicable
     *
     * @param string $method
     *
     * @return string
     *
     * @throws BadMethodCallException if service prefix should be used but was not set
     */
    protected function getServiceMethodName($method)
    {
        if (!$this->usesServicePrefix()) {
            return $method;
        }

        $servicePrefix = $this->getServicePrefix();
        if(!empty($servicePrefix)) {
            $method = $this->getServicePrefix() . '.' . $method;
            $this->clearServicePrefix();
            return $method;
        }

        throw new BadMethodCallException('Could not reliably determine the service
            to call for the method ' . $method . ' in the deal api.');
    }

    /**
     * Overridden to allow a service prefix to be added.
     *
     * @see parent::__call
     *
     * @param string $method
     * @param array $params
     *
     * @return mixed
     */
    public function __call($method, array $params)
    {
        $method = $this->getServiceMethodName($method);
        return parent::__call($method, $params);
    }
}
