<?php

/**
 * Sends statistics to the stats daemon over UDP
 *
 * @url https://gist.github.com/1065177/7676cd362cc8f85c4898ad7ec32628e80c554044
 **/

class DDM_StatsD
{
    /** @var string IP address of the StatsD server */
    protected $server_ip;

    /** @var int Port of the StatsD server */
    protected $server_port;

    /** @var string Key namespace to append to the stat */
    protected $keyspace = '';

    static protected $_instance;

    /**
     * Constructs a DDM_StatsD with specified ip and port
     *
     * @param string $server_ip
     * @param int $server_port
     */
    public function __construct($server_ip = '127.0.0.1', $server_port = null, $keyspace = null)
    {
        $this->server_ip = $server_ip;
        $this->server_port = $server_port ?: 8125;

        if($keyspace) {
            $this->setKeyspace($keyspace);
        }
    }

    public static function getInstance()
    {
        // Late static binding
        $calledClass = get_called_class();

        $config = Zend_Registry::get('config')->statsd;
        if(!self::$_instance) {
            self::$_instance = new $calledClass($config->host, $config->port, $config->keyspace);
        }

        return self::$_instance;
    }

    /**
     * Sets the keyspace to be prepended on all stat names
     * Ensures that keyspace ends with a .
     *
     * @param string $keyspace
     */
    public function setKeyspace($keyspace) {
        if (substr($keyspace, -1) !== '.') {
            $keyspace .= '.';
        }
        $this->keyspace = $keyspace;
    }

    /**
     * Log timing information
     *
     * @param string $stats The metric to in log timing info for.
     * @param float $time The elapsed time (ms) to log
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     */
    public function timing($stat, $time, $sampleRate = 1)
    {
        $this->send(array($stat => "$time|ms"), $sampleRate);
    }

    /**
     * Log gauge information
     *
     * @param string $stats The metric to in log timing info for.
     * @param float $time The elapsed time (ms) to log
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     */
    public function gauge($stat, $value, $sampleRate = 1)
    {
        $this->send(array($stat => "$value|g"), $sampleRate);
    }

    /**
     * Increments one or more stats counters
     *
     * @param string|array $stats The metric(s) to increment.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     */
    public function increment($stats, $sampleRate = 1)
    {
        $this->updateStats($stats, 1, $sampleRate);
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string|array $stats The metric(s) to decrement.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     */
    public function decrement($stats, $sampleRate = 1)
    {
        $this->updateStats($stats, -1, $sampleRate);
    }

    /**
     * Updates one or more stats counters by arbitrary amounts.
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int|1 $delta The amount to increment/decrement each metric by.
     * @param float|1 $sampleRate the rate (0-1) for sampling.
     */
    public function updateStats($stats, $delta = 1, $sampleRate = 1)
    {
        if (!is_array($stats)) { $stats = array($stats); }
        $data = array();
        foreach($stats as $stat) {
            $data[$stat] = "$delta|c";
        }

        $this->send($data, $sampleRate);
    }

    /**
     * Sends the metrics over UDP
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int|1 $delta The amount to increment/decrement each metric by.
     */
    public function send($data, $sampleRate = 1)
    {
        // sampling
        $sampledData = array();

        if ($sampleRate < 1) {
            foreach ($data as $stat => $value) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $sampledData[$stat] = "$value|@$sampleRate";
                }
            }
        } else {
            $sampledData = $data;
        }

        if (empty($sampledData)) {
            return;
        }

        // Wrap in a try/catch. Application shouldn't care if we fail sending the stats.
        try {
            $errno = null;
            $errstr = null;
            // one second timeout
            $fp = fsockopen("udp://".$this->server_ip, $this->server_port, $errno, $errstr, 1);
            if (!$fp) {
                return;
            }
            foreach ($sampledData as $stat => $value) {
                fwrite($fp, $this->keyspace . "$stat:$value");
            }
            fclose($fp);
        } catch (Exception $ex) {
            // Do nothing
        }
    }
}