<?php

class DDM_Session_SaveHandler_Cache implements Zend_Session_SaveHandler_Interface
{

    private $maxlifetime = 3600;
    private $cache = null;

    /**
     * Memcache Session Handler Constructor
     *
     * @param object $config
     */
    public function __construct($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        } else if (!is_array($config)) {
            require_once 'Zend/Session/SaveHandler/Exception.php';
            throw new Zend_Session_SaveHandler_Exception(
                '$config must be an instance of Zend_Config'
            );
        }

        if(array_key_exists('maxlifetime', $config)) {
            $this->maxlifetime = $config['maxlifetime'];
        }

		$this->cache = Zend_Cache::factory(
            'Core',
            $config['type'],
            $config['front'],
            $config['back']
        );
    }

    /**
     * Open Session Handler
     *
     * @param string $save_path
     * @param string $name
     * @return bool
     */
    public function open($save_path, $name)
    {
        return true;
    }

    /**
     * Close Session Handler
     *
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * Read Session Handler
     *
     * @param int $id
     * @return array
     */
    public function read($id)
    {
        if (!($data = $this->cache->load($id))) {
            return '';
        } else {
            return $data;
        }
    }

    /**
     * Write Session Handler
     *
     * @param int $id
     * @param array $sessionData
     * @return bool
     */
    public function write($id, $sessionData)
    {
        $this->cache->save(
            $sessionData,
            $id,
            array(),
            $this->maxlifetime
        );

        return true;
    }

    /**
     * Destroy Session Handler
     *
     * @param int $id
     * @return bool
     */
    public function destroy($id)
    {
        $this->cache->remove($id);

        return true;
    }

    /**
     * Garbage Collection
     *
     * @param unknown $notusedformemcache
     * @return bool
     */
    public function gc($notusedformemcache)
    {
        return true;
    }

}
