<?php
/**
 * The DDM Frameworkified version of Devin's mcache.php
 *
 */

class DDM_Cache_Mcache {

	protected static $instances = array();

	protected $defaultServers = array(
		0 => array('address'=>'64.147.130.163','port'=>'11211','socket'=>false),
		1 => array('address'=>'64.147.130.164','port'=>'11211','socket'=>false)
	);

	protected $servers = array();

	protected $hashlist = array(
		0 => 0,
		1 => false,
		2 => false,
		3 => false,
		4 => 1,
		5 => false,
		6 => false,
		7 => false,
		8 => 0,
		9 => false,
		10 => false,
		11 => false,
		12 => 1,
		13 => false,
		14 => false,
		15 => false,
		16 => 0,
		17 => false,
		18 => false,
		19 => false,
		20 => 1,
		21 => false,
		22 => false,
		23 => false,
		24 => 0,
		25 => false,
		26 => false,
		27 => false,
		28 => 1,
		29 => false,
		30 => false,
		31 => false
	);

	protected $get_key;

	/**
	 * Get an instance of this class - unique to connection(s)
	 *
	 * @param unknown_type $server
	 * @return DDM_Cache_Mcache
	 */
	public static function getInstance( $server = null ) {
		$key = md5(serialize($server));
    	if(!array_key_exists($key, self::$instances)) {
      		self::$instances[$key] = new DDM_Cache_Mcache( $server );
    	}
		return self::$instances[$key];
	}

	/**
	 * Don't call this, just all getInstance
	 *
	 * @param array $servers
	 * @return unknown
	 */
	public function __construct( $server = null ) {
		if( $server === null ) {
			$this->servers = $this->defaultServers;
		} else {
			$this->servers[] = $server;
		}
		$this->get_key = false;
	}

	/**
	 * Set a value
	 *
	 * @param string $key
	 * @param string $value
	 * @param int $exptime
	 * @param int $flags
	 * @return bool
	 */
	public function set($key,$value, $exptime=300,$flags=0)
	{
		return $this->store('set',$key,$flags,$exptime,$value);
	}

	/**
	 * Enter description here...
	 *
	 * @param string $key
	 * @return string
	 */
	public function get($key)
	{
		// key must be a single string or an array of strings
		// convert key into an array
		if (is_string($key)) {
			if (trim($key) == '') { // don't allow blank keys
				/*
				$xx = @fopen("/tmp/memd_blank.log", 'a+');
				if($xx) {
					$trace = debug_backtrace();
					foreach($trace as $i => $d) {
						$debugs[] = $d['file']." ".$d['function'];
					}
					$debugstr = join("|",$debugs);
					fputs($xx, "get() blank key: $debugstr\n");
					fclose($xx);
				}
				*/
				return false;
			}

			$keys = array($key);
		}
		else if (is_array($key)) {
			$keys = $key;
		}
		else {
			return false;
		}

		// group keys based on which server they hash to
		$batches = array();
		foreach ($keys as $i => $value) {
			if (trim($value) == '') // drop blank keys
				continue;

			$sidx = $this->open($value);
			if($sidx === false)
				continue;

			$batches[$sidx][] = $value;
		}

		// convert arrays of keys into a single space-separated string
		// and process them
		$responses = array();
		$this->get_key = array();
		foreach ($batches as $sidx => $bkeys) {
			$key_str = join(' ', $bkeys);

			fwrite($this->servers[$sidx]['socket'], "get {$key_str}\r\n");
			$this->get_key[$sidx] = 'get '.$key_str;
			$values = $this->response($sidx);

			if ($values === false) {
				continue;
			}

			foreach ($values as $k => $d) {
				$responses[$k] = $values[$k]['value'];
			}
		}

		// if we were invoken with a single key string, then return
		// a single value string
		if (is_string($key)) {
			if (isset($responses[$key]))
				return $responses[$key];
			else
				return false;
		}
		else {
			$ret = array();
			foreach ($key as $i => $value) {
				if(isset($responses[$value]))
					$ret[$value] = $responses[$value];
				else
					$ret[$value] = false;
			}

			return $ret;
		}
	}

	/**
	 * Delete a key
	 *
	 * @param string $key
	 * @return boolean
	 */
	public function delete($key)
	{
		if (($sidx = $this->open($key)) === false) {
			return false;
		}

		fwrite($this->servers[$sidx]['socket'], "delete {$key}\r\n");
		$value = trim(fgets($this->servers[$sidx]['socket'], 256));

		if($value == 'DELETED') {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Increment a counter
	 *
	 * @param string $key
	 * @param int $val
	 * @return unknown
	 */
	public function incr($key,$val)
	{
		if (($sidx = $this->open($key)) === false) {
			return false;
		}

		fwrite($this->servers[$sidx]['socket'], "incr {$key} {$val}\r\n");
		$value = trim(fgets($this->servers[$sidx]['socket'], 256));

		return $value;
	}

	/**
	 * Decrement a counter
	 *
	 * @param string $key
	 * @param unknown_type $val
	 * @return int
	 */
	public function decr($key,$val)
	{
		if (($sidx = $this->open($key)) === false) {
			return false;
		}

		fwrite($this->servers[$sidx]['socket'], "decr {$key} {$val}\r\n");
		$value = trim(fgets($this->servers[$sidx]['socket'], 256));

		return $value;
	}

	/**
	 * Get cached content, or call the function and cache it for next time
	 *
	 * @param string $key
	 * @param string $functionName
	 * @param mixed $functionParams
	 * @param mixed $objectOptions
	 * @param int $ttl
	 *
	 * @return mixed;
	 */
	public function getCachedContent( $key, $functionName, $functionParams = null, $objectOptions = null, $ttl = 300 ) {

		// cache hit
		$result = $this->get($key);
		if( substr( $result, 0, 2) == 'a:') {
			$result = unserialize($result);
		}
		if( $result !== false ) {
			return $result;
		}

		$obj = null;
		// check for an object
		if( is_array( $objectOptions ) ) {
			if( !empty($objectOptions['object']) ) {
				$obj = $objectOptions['object'];
			} else {
				// instansiate the object
				$class = $objectOptions['class'];
				if( !empty($objectOptions['params'] ) ) {
					$obj = new $class($params);
				}
				$obj = new $class();
			}
		} elseif( is_object( $objectOptions) ) {
				$obj = $objectOptions;
		}

		$newData = null;
		// make the call to get the content
		if( $obj === null ) {
			$newData = call_user_func( $functionName, $functionParams );
		} else {
			$functionParams = array($functionParams);
			$newData = call_user_func_array( array($obj, $functionName), $functionParams );
		}

		// serialize if needed
		if( is_array($newData) || is_object($newData) ) {
			$cacheData = serialize($newData);
		} else {
			$cacheData = $newData;
		}

		// cache the new data and return it
		$this->set($key, $cacheData, $ttl);
		return $newData;

	}


	/**
	 * Open a Key
	 *
	 * @param string $key
	 * @return unknown
	 */
	protected function open($key)
	{
		$sidx = $this->get_server_idx($key);
		if($sidx === false)
			return false;

		// don't connect if we're already connected to the server
		if ($this->servers[$sidx]['socket'] !== false) {
			return $sidx;
		}

		// connect
		$addr = $this->servers[$sidx]['address'];
		$port = $this->servers[$sidx]['port'];

		// XXX if this fails, we should loop through the server list
		// until we find one that works
		$ret = $this->servers[$sidx]['socket'] = @fsockopen($addr, $port, $errno, $errstr, 1);

		if ($this->servers[$sidx]['socket'] === false)
			return false;

		return $sidx;
	}

	/**
	 * turn a given key string into a number
	 *
	 * @param string $key
	 * @return int
	 */
	protected function intify_key($key)
	{
		$md5 = md5($key);
		if($md5 === false)
			return false;

		$hash = 0;
		$len = strlen($md5);
		for($i=0; $i<$len; $i++) {
			$value = ord($md5[$i]);
			if($value >= 61)
				$hash += $value-61+10;
			else
				$hash += $value-48;
		}

		return $hash;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $key
	 * @return unknown
	 */
	protected function get_server_idx($key)
	{
	    if(count($this->servers) == 1) {
	        return 0;
	    }

		// find the right server for this key
		$keyint = $this->intify_key($key);
		$idx = $keyint % count($this->hashlist);

		if($this->hashlist[$idx] !== false) {
			return $this->hashlist[$idx];
		}

		// search for the next server in the list
		$start = $idx;
		$idx = ($idx+1) % count($this->hashlist);
		while($this->hashlist[$idx] === false && $idx != $start) {
			$idx = ($idx+1) % count($this->hashlist);
		}

		// this is a big problem -- there isn't a non-false value in the hashlist!
		if($this->hashlist[$idx] === false) {
			return false;
		}

		return $this->hashlist[$idx];
	}

	/**
	 * Store stuff
	 *
	 * @param string $command
	 * @param string $key
	 * @param int $flags
	 * @param int $exptime
	 * @param string $packet
	 * @return bool
	 */
	protected function store($command,$key,$flags,$exptime,$packet)
	{
		if (!is_string($key))
			return false;

		if (($sidx = $this->open($key)) === false)
			return false;

		$bytes = strlen($packet);
		fwrite($this->servers[$sidx]['socket'], "{$command} {$key} {$flags} {$exptime} {$bytes}\r\n$packet\r\n");
		$value = trim(fgets($this->servers[$sidx]['socket'], 256));

		return $value;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $sidx
	 * @return unknown
	 */
	protected function response($sidx)
	{
		if ($this->servers[$sidx]['socket'] === false) {
			return false;
		}

		$limit = 50;
		$values = false;
		while (1) {
			$recv = fgets($this->servers[$sidx]['socket'], 1024);
			if ($recv === false) return false;
			$line = trim($recv);

			if ($line == 'ERROR') {
				return false;
			}
			if ($line == '') {
				$limit--;
				if ($limit > 0) continue;
				else return false;
			}

			$tag = null;
			$bytes = null;
			$key = null;
			$flag = null;
			$pieces = explode(" ", $line);
            if (count($pieces) == 4) {
                list($tag,$key,$flag,$bytes) = $pieces;
            } else if (count($pieces) == 1) {
                list($tag) = $pieces;
            }

			if ($tag == 'END') {
				return $values;
			}
			$val = '';
			while ($bytes > 0) {
				$s = fread($this->servers[$sidx]['socket'], $bytes);
				$bytes -= strlen($s);
				$val .= $s;
			}
			if($values === false) {
				$values = array();
			}
			$values[$key]['flag'] = $flag;
			$values[$key]['value'] = $val;
		}
		return $values;
	}

}
