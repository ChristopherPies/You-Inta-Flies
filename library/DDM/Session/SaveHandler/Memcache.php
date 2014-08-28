<?php

/**
 * Memcache session handler that is compatible with ksl.com memcache sessions.
 * This uses DDM_Cache_Mcache to do the actual caching.
 *
 * Note: If you don't need to share cached data with the old mcache.php,
 * it would probably be easier to use DMM_Session_SaveHandler_Cache with
 * a default Zend cache backend.
 */
class DDM_Session_SaveHandler_Memcache implements Zend_Session_SaveHandler_Interface {

    protected $config = array(
        'keyPrefix' => 'ksl_',
    );

    public function __construct($config) {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }
        $this->config = array_merge($this->config, $config);
        
        $this->mcd = DDM_Cache_Mcache::getInstance();
    }

    /**
     *
     *
     * @param integer $sessionId
     * @return array $data
     */
    public function read($sessionId)
    {
        $memkey = $this->_getCacheKey($sessionId);
        $raw = $this->mcd->get($memkey);
        if ($raw === false)
            $data = '';
        else {
            $data = @unserialize($raw);
            if ($data === false)
                $data = '';
        }

        if (strlen($sessionId) < 32) {
            $fh = @fopen('/tmp/sess.log', 'a+');
            if ($fh) {
                $timestr = strftime('%Y-%m-%d %H:%M:%S');
                @fputs(
                    $fh, '['. $timestr. '] '. $this->_getCacheKey($sessionId). ' (bad session id)'. PHP_EOL
                );
                @fclose($fh);
            }
        }

        // dbunker 2009-08-03
        // must return a string of some sort from this function, per
        // the PHP manual
        if ($data === false)
            $data = '';

        return $data;
    }
    
    /**
     *
     *
     * @param integer $sessionId
     * @param array $data
     * @return boolean
     */
    public function write($sessionId, $data)
    {
        $out = serialize($data);
        
        // we need to create a new mcache object since the
        // regular global object has been destroyed by now
        
        $mcdKey = $this->_getCacheKey($sessionId);
        $this->mcd->set($mcdKey, $out, 86400);

        return true;
    }
    
    /**
     *
     *
     * @param integer $sessionId
     * @return none
     */
    public function destroy($sessionId)
    {
        $mcdKey = $this->_getCacheKey($sessionId);
        $this->mcd->delete($mcdKey);
    }

    /**
     *
     *
     * @param string $path
     * @param string $name
     * @return boolean always true
     */
    public function open($path, $name)
    {
        return true;
    }
    /**
     *
     *
     * @param none
     * @return boolean always true
     */
    public function close()
    {
        return true;
    }
    /**
     *
     *
     * @param integer $life
     * @return boolean always true
     */
    public function gc($life)
    {
        return true;
    }
    
	/**
     * Returns standardized cache key
     *
     * @param string $sessionId
     * @return string
     */
    protected function _getCacheKey($sessionId)
    {
        return $this->config['keyPrefix'] . $sessionId;
    }
}
