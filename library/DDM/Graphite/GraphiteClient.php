<?php

namespace DDM\Graphite;

use DDM\Graphite\Exception\RuntimeException;


class GraphiteClient extends \Zend_Http_Client
{

    /**
     * Most request processed
     *
     * @var string
     */
    protected $_lastResourcePath = null;

    /**
     * Base API uri
     *
     * @var string
     */
    protected $_baseUri = null;

    /**
     * Constructor method. Will create a new HTTP client. Accepts the target
     * URL and optionally configuration array.
     *
     * Supported fiels for config:
     * -
     * -baseUri           = string
     *
     * {All Zend_Http_Client config}
     * -maxredirects      = int
     * -strictredirects   = boolean
     * -useragent         = string
     * -timeout           = int
     * -adapter           = string|Zend_Http_Client_Adapter_Interface
     * -httpversion       = strin
     * -keepalive         = boolean
     * -storeresponse     = boolean
     * -strict            = boolean
     * -output_stream     = boolean
     * -encodecookies     = boolean
     * -rfc3986_strict    = boolean
     *
     * @see Zend_Http_Client for defaults
     *
     * @param Zend_Uri_Http|string $uri
     * @param array $config Configuration key-value pairs.
     */
    public function __construct($uri = null, $config = array())
    {

        // Set default adaptor
        if (! isset($config['adapter'])) {
            $config['adapter'] = 'Zend_Http_Client_Adapter_Curl';
        }

        // Init Hook
        $this->_init();

        parent::__construct($uri, $config);

    }

    /**
     * Set base API uri. This is used to construct API paths
     *
     * @param string $uri
     */
    public function setBaseUri($uri)
    {
        $this->_baseUri = $uri;
    }

    /**
     * Return API base URI
     */
    public function getBaseUri()
    {
        return $this->_baseUri;
    }

    /**
     * Set configuration parameters for this HTTP client
     *
     * @param  Zend_Config | array $config
     * @return Local_Api_Client_Abstract
     * @throws Zend_Http_Client_Exception
     */
    public function setConfig($config = array())
    {
        // Convert Zend_Config
        if ($config instanceof \Zend_Config) {
            $config = $config->toArray();
        }

        // Read in API specific config
        if (isset($config['baseUri'])) {
            $this->_baseUri = rtrim($config['baseUri'], '/');
            $this->_baseUri .= '/';
            unset($config['baseUri']);
        }

        parent::setConfig($config);

        return $this;
    }

    /**
     * Get the full URI including queryParams for the next request
     *
     * @param boolean $as_string If true, will return the URI as a string
     * @return mixed Zend_Uri_Http|string
     */
    public function getFullUri($asString=false)
    {
        // Clone the URI and add the additional GET parameters to it
        if (is_null($this->uri)) {
            return ($asString)? 'uri not set' : $this->uri;
        }

        $uri = clone $this->uri;
        if (! empty($this->paramsGet)) {
            $query = $uri->getQuery();
               if (! empty($query)) {
                   $query .= '&';
               }
            $query .= http_build_query($this->paramsGet, null, '&');
            if ($this->config['rfc3986_strict']) {
                $query = str_replace('+', '%20', $query);
            }

            $uri->setQuery($query);
        }

        if ($asString) {
            return $uri->__toString();
        }

        return $uri;
    }

    /**
     * Replaces Zend Uri
     *
     * (non-PHPdoc)
     * @see Local_Api_Client_Abstract::_init()
     */
    protected function _init()
    {
        $uri = GraphiteUri::fromScheme('http');
        $uri->setUseSquareBracketNotation(false);

        $this->setUri($uri);
    }

    public function setApiUri($resourcePath, $base=null)
    {
        if (is_null($base)) {
            $base = $this->getBaseUri();
        }

        // Trim / from front of path
        $resourcePath = ltrim($resourcePath, '/');

        // Store last resource path
        $this->_lastResourcePath = $resourcePath;

        $uri = \Zend_Uri::factory($base. $resourcePath, 'DDM\\Graphite\\GraphiteUri');
        $uri->setUseSquareBracketNotation(false);

        $this->setUri($uri);

        return $this;
    }


}