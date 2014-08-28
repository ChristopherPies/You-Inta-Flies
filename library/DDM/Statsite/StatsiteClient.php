<?php

/**
 * Sends statistics to the statsite daemon over UDP or TCP
 *
 * Based heavily on statsD php client:
 * @url https://gist.github.com/1065177/7676cd362cc8f85c4898ad7ec32628e80c554044
 *
 * Modified to support statsite protocol
 * @url https://github.com/armon/statsite
 *
 * Note:
 * -
 * - currently statsite binary protocol is not supported
 * - Statsite doesn't support the sampling flag
 *   TOODO download latest release and recompile statsite
 *   Need to update statsite proxy as well
 **/

namespace DDM\Statsite;

use DDM\Statsite\Exception\RuntimeException;


class StatsiteClient
{
    const TRANSPORT_UDP = 'UDP';
    const TRANSPORT_TCP = 'TCP';

    /**
     * Prefixed to metric key
     *
     * @var string
     */
    protected $_keyspace = null;

    /**
     * Track available servers
     *
     * @var array
     */
    protected $_availableServers = null;

    /**
     * Track selected transport protocol for each request
     *
     * @var string
     */
    protected $_requestTransport = null;

    /**
     * Carbon socket errornum
     *
     * @var int
     */
    protected $_socketErrorNum = null;

    /**
     * Carbon socket error message
     *
     * @var string
     */
    protected $_socketErrorMsg = null;

    /**
     * Statsite config
     *
     * Config fields:
     * -
     * - keyspace              = string
     * - defaultUdpPort        = int
     * - defaultTcpPort        = int
     * - defaultTimeout        = int
     * - defaultTransport      = string either 'UDP' | 'TCP'
     * - tcpFailoverEnabled    = boolean
     * - tcpFailoverMaxRetries = int
     * - servers               = array
     * - flushThreshold        = int number of metrics sent across socket at once
     *
     * @var array
     */
    protected $_config = array(
        'defaultUdpPort'        => 8150,
        'defaultTcpPort'        => 8150,
        'defalutTimeout'        => 10,
        'defaultTransport'      => self::TRANSPORT_UDP,
        'tcpFailoverEnabled'    => true,
        'tcpFailoverMaxRetries' => 3,
    	'servers'               => null,
        'flushThreshold'        => 50
    );


    /**
     * Constructor
     *
     * Supported fields for $config:
     * -
     * - keyspace              = string
     * - defaultUdpPort        = int
     * - defaultTcpPort        = int
     * - defaultTimeout        = int
     * - defaultTransport      = string either 'UDP' | 'TCP'
     * - tcpFailoverEnabled    = boolean
     * - tcpFailoverMaxRetries = int
     * - servers               = array
     * - flushThreshold        = int number of metrics sent across socket at once
     *
     * TODO update above to match final settings
     *
     * Supported fields for server config array:
     * -
     * - host    = string
     * - port    = int
     * - timeout = int
     *
     * Usage.
     * <pre>
     * $config['servers'] = array(
     *     array(
     *         'host'    => '{host}',
     *         'port'    => {port},
     *         'timeout' => {timeout}
     *      )
     * );
     * </pre>
     *
     * @param array $config
     */
    public function __construct($config=null)
    {
        // Set options
        $this->setOptions($config);
    }

	/**
     * Getter for config
     *
     * @param int $serverId
     * @return array
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Sets options bassed on configuration array
     *
     * Supported fields for server config array:
     * -
     * - see __construct
     *
     * @param array $config
     * @return boolean true on success false otherwise
     */
    public function setOptions($config) {
        // Extract data from Config Object
        if (is_object($config) && method_exists($config, 'toArray')) {
            $config = $config->toArray();
        }

        // No config set
        if (! is_array($config)) {
            return false;
        }

        // Set Config options
        $this->_config = $config += $this->_config;

        // Set servers
        if (isset($config['servers'])) {
            $this->setServers($config['servers']);
        }

        // Set key space
        if (isset($config['keyspace'])) {
            $this->setKeyspace($config['keyspace']);
        }

        return true;

    }

	/**
     * Setter for servers config array
     *
     * @param array $servers
     */
    public function setServers($servers)
    {
        // Init
        $this->_config['servers'] = $servers;
        $this->_availableServers = $this->_config['servers'];
    }

    /**
     * Sets the keyspace to be prepended on all stat names
     *
     * @param string $keyspace
     */
    public function setKeyspace($keyspace) {

        // Ensures that keyspace suffixed with a dot
        if (substr_compare($keyspace, '.', -1, 1) !== 0) {
            $keyspace .= '.';
        }

        $this->_keyspace = $keyspace;
    }

    /**
     * Set msg transport protocol
     *
     * @param string $transport if null defaults uses the defaultTransport specified in config
     */
    public function setTransport($transport=null)
    {
        // Determine msg transport
        if (is_null($transport)) {
            $transport = $this->_config['defaultTransport'];
        } else {
            $transport = strtoupper($transport);
        }
        $this->_requestTransport = ($transport == self::TRANSPORT_TCP)? 'TCP' : 'UDP';
    }

    /**
     * Query function to determine if transport is TCP
     *
     * @return boolean
     */
    public function transportIsTCP()
    {
        return ($this->_requestTransport == self::TRANSPORT_TCP);
    }

    /**
     * Log timing information
     *
     * @param string $stat The metric to in log timing info for.
     * @param float $time The elapsed time (ms) to log
     */
    public function timing($stat, $time)
    {
        $this->send(array(
            $stat => $time . '|ms'
        ));
    }

    /**
     * Log gauge information
     *
     * @param string $stat The metric key.
     * @param float $time Gauge value to log.
     */
    public function gauge($stat, $value)
    {
        $this->send(array(
            $stat => $value . '|g'
        ));
    }

	/**
     * Log key val information
     *
     * @param string $stat The metric key.
     * @param float $value Value to log.
     */
    public function keyval($stat, $value)
    {
        $this->send(array(
            $stat => $value . '|kv'
        ));
    }

    /**
     * Increments one or more stats counters
     *
     * @param string|array $stats The metric(s) to increment.
     * @param float|int $sampleRate rate (0-1) for sampling
     */
    public function increment($stats, $sampleRate=1)
    {
        $this->updateStats($stats, 1, $sampleRate);
    }

    /**
     * Decrements one or more stats counters.
     *
     * @param string|array $stats The metric(s) to decrement.
     * @param float|int $sampleRate rate (0-1) for sampling
     */
    public function decrement($stats, $sampleRate=1)
    {
        $this->updateStats($stats, -1, $sampleRate);
    }

    /**
     * Updates one or more stats counters by arbitrary amounts.
     *
     * @param string|array $stats The metric(s) to update. Should be either a string or array of metrics.
     * @param int $delta The amount to increment/decrement each metric by.
     * @param float $sampleRate rate (0-1) for sampling
     */
    public function updateStats($stats, $delta=1, $sampleRate=1)
    {
        if (!is_array($stats)) {
            $stats = array($stats);
        }

        $data = array();

        // Prepare Count stats
        if ($sampleRate >= 1) {
            foreach($stats as $stat) {
                $data[$stat] = $delta . '|c';
            }
        } else {
            // Apply Sampling at specified rate
            foreach($stats as $stat) {
                if ((mt_rand() / mt_getrandmax()) <= $sampleRate) {
                    $data[$stat] = $delta . '|c|@' . $sampleRate;
                }
            }
        }

        // Send only if we have data to send
        if (! empty($data)) {
            $this->send($data);
        }

    }

    /**
     * Sends the metrics over UDP or TCP
     *
     * @param string|array $data The metric(s) to update. Should be either a string or array of metrics.
     * @param string $msgTransport either 'UDP' | 'TCP'
     * @throws RuntimeException
     */
    public function send($data, $transport=null)
    {

        // Init
        $this->_resetAvailableServers();
        $this->setTransport($transport);

        // Connect to stats server
        $serverFd = $this->_getRandomServer();

        // No server, must be UDP so just giveup already
        if (! $serverFd) {
            return false;
        }

        // Send message using specified transport
        $metricCnt = 0;
        foreach ($data as $stat => $value) {
            fwrite($serverFd, $this->_keyspace . $stat . ':' . $value . PHP_EOL);
            $metricCnt++;

            // handle flush threshold
            if ($metricCnt >= $this->_config['flushThreshold']) {
                fflush($serverFd);
                $metricCnt = 0;
            }
        }

        fclose($serverFd);

    }

    /**
     * Resets available servers list
     *
     * @param boolean $softReset
     */
    protected function _resetAvailableServers()
    {
        $this->_availableServers = $this->_config['servers'];
    }

    /**
     * Randomly selects server and connects
     *
     * Provides failover support for TCP
     *
     * @return int socket resource
     * @throws exception
     */
    protected function _getRandomServer()
    {
        // Init
        $socketFd = false;
        $isTCP = $this->transportIsTCP();

        // Select random server
        $serverInfo = $this->_getRandomServerInfo();
        if (! $serverInfo && $isTCP) {
            throw new Exception\RuntimeException('No stats servers available');
        }

        // Connect
        $socketFd = $this->_connect($serverInfo);

        // TCP Failover
        if (! $socketFd && $isTCP) {
            if ($this->_config['tcpFailoverEnabled']) {
                for($i = 0; $i < $this->_config['tcpFailoverMaxRetries']; $i++) {
                    $serverInfo = $this->_getRandomServerInfo();
                    if (! $serverInfo) {
                        throw new Exception\RuntimeException('No more stats servers to try');
                    }

                    $socketFd = $this->_connect($serverInfo);
                    if ($socketFd) {
                        break;
                    }
                }
            }

            // Couldn't connect to anything
            if (! $socketFd) {
                $this->_throwSocketConnectionException();
            }
        }

        return $socketFd;
    }

    /**
     * Randomly select server info from available servers
     *
     * @return mixed array|boolean server info on succes false otherwise
     */
    protected function _getRandomServerInfo()
    {
        $numAvailableServers = count($this->_availableServers);
        $serverInfo = null;

        // No selectable servers
        if (! $numAvailableServers) {
            return false;
        }

        // Loadbalance Cluster
        if ($numAvailableServers > 1) {
            $keys = array_keys($this->_availableServers);
            $serverKey = $keys[mt_rand(0, $numAvailableServers-1)];

            $serverInfo = $this->_availableServers[$serverKey];

            unset($this->_availableServers[$serverKey]);

        } else {
            $serverInfo = current($this->_availableServers);
            $this->_availableServers = array();
        }

        // Prepare server info / backfill defaults
        if (! isset($serverInfo['port'])) {
            if ($this->_requestTransport == self::TRANSPORT_TCP) {
                $serverInfo['port'] = $this->_config['defaultTcpPort'];
            } else {
                $serverInfo['port'] = $this->_config['defaultUdpPort'];
            }
        }

        if (! isset($serverInfo['timeout'])) {
            $serverInfo['timeout'] = $this->_config['defalutTimeout'];
        }

        return $serverInfo;

    }

    /**
     * Attempts to connect to specified server
     *
     * @param array $serverInfo
     * @return int resource handle
     */
    protected function _connect($serverInfo)
    {
        return stream_socket_client(
            strtolower($this->_requestTransport) . '://' . $serverInfo['host'] . ':' . $serverInfo['port'],
            $this->_socketErrorNum,
            $this->_socketErrorMsg,
            $serverInfo['timeout']
        );
    }

    /**
     * Throws socket connection error
     *
     * @throws \DDM\Statsite\Exception\RuntimeException
     */
    protected function _throwSocketConnectionException()
    {
        throw new Exception\RuntimeException(
        	'Unable to establish ' . $this->_requestTransport . ' connection.' . PHP_EOL
        	. 'Error (' . $this->_socketErrorNum . ') - ' . $this->_socketErrorMsg
        );
    }




}