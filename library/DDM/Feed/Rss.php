<?php

require_once 'Zend/Feed/Rss.php';

class DDM_Feed_Rss extends Zend_Feed_Rss {
    /**
     * Feed constructor
     *
     * The Zend_Feed_Abstract constructor takes the URI of a feed or a
     * feed represented as a string and loads it as XML.
     *
     * @param  string $uri The full URI of the feed to load, or NULL if not retrieved via HTTP or as an array.
     * @param  string $string The feed as a string, or NULL if retrieved via HTTP or as an array.
     * @param  Zend_Feed_Builder_Interface $builder The feed as a builder instance or NULL if retrieved as a string or via HTTP.
     * @return void
     * @throws Zend_Feed_Exception If loading the feed failed.
     */
    public function __construct($uri = null, $string = null, Zend_Feed_Builder_Interface $builder = null)
    {
        if ($uri !== null) {
            // Retrieve the feed via HTTP
            $client = Zend_Feed::getHttpClient();
            $client->setUri($uri);
            $response = $client->request('GET');
            if ($response->getStatus() !== 200) {
                /**
                 * @see Zend_Feed_Exception
                 */
                require_once 'Zend/Feed/Exception.php';
                throw new Zend_Feed_Exception('Feed failed to load, got response code ' . $response->getStatus());
            }
            $this->_element =  preg_replace('/\&/','&amp;',html_entity_decode($response->getBody()));
            $this->__wakeup();
        } else {
            parent::__construct($uri, $string, $builder);
        }
    }
}
