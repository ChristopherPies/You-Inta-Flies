<?php
include_once 'DDM/Http/Status.php';

/**
 * ErrorResponse - Standardizes error responses
 *
 */
class DDM_Upload_Chunker_ErrorResponse
{

    const STATUS       = 'status';
    const MESSAGE      = 'message';
    const CLIENT_ERROR = 'clientError';
    const SERVER_ERROR = 'serverError';

    /**
     * Holds status code
     *
     * @var int
     */
    protected $_status = DDM_Http_Status::INTERNAL_SERVER_ERROR;

    /**
     * Holds error messages
     *
     * @var array
     */
    protected $_message = array();

	/**
     * Constructor
     *
     * suported field for $config:
     * -
     * - allowDynamicFields    = boolean
     * - emptyType             = mixed
     * - notEmptyValidator     = Zend_Validate_Abstract should return false on empty fields
     *
     * @param array $data
     * @param array $config
     */
    public function __construct($errorData=null)
    {

        if (! is_null($errorData)) {
            $this->populate($errorData);
        }

    }

    /**
     * Getter for status code
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->_status;
    }

    /**
     * Setter for status
     *
     * @param int $status
     */
    public function setStatus($status)
    {
        $this->_status = $status;
    }

    /**
     * Getter for message
     *
     * @return array();
     */
    public function getMessage()
    {
        $message = $this->_message;

        if ($this->_messageFromStatusEnabled && empty($this->_message)) {
	        $this->setMessageFromStatus();
	    }

        return $message;
    }

	/**
     * Setter for message
     *
     * @param string|array $message
     * @param string $type applied only if $message is string
     * @param boolean $append
     */
    public function setMessage($message, $type=null, $append=false)
    {
        if (is_string($message)) {
            if (! is_null($type)) {
                $message = array($type => $message);
            } else {
                $message = array($message);
            }
        }

        // Reset messages
        if (! $append) {
            $this->_message = array();
        }

        foreach($message as $type => $msg) {
            $this->_message[] = array(
                'type'    => (!is_numeric($type))? $type : null,
                'message' => $msg
            );
        }
    }

	/**
     * Loads error data into response
     *
     * @param mixed array $data
     */
    public function populate($data)
    {
        if (is_array($data)) {
            if (isset($data[self::STATUS])) {
                $this->setStatus($data[self::STATUS]);
            }

            if (isset($data[self::MESSAGE])) {
                $this->setMessage($data[self::MESSAGE]);
            }
        }
    }

    /**
     * If message is empty, attempts to set message from status code
     *
     * @return array
     */
	public function toArray()
	{
	    if (empty($this->_message)) {
	        $this->setMessageFromStatus();
	    }

	    return array(
	        self::STATUS  => $this->_status,
	        self::MESSAGE => $this->_message
	    );
	}


	/**
	 * Attempts to add message using status code
	 *
	 */
	public function setMessageFromStatus()
	{
	    $message = DDM_Http_Status::statusCodeAsText($this->_status);

	    // Set message type
	    switch (substr($this->_status, 0, 1)) {
	        case '4':
	            $messageType = self::CLIENT_ERROR;
	            break;
	        case '5':
	            $messageType = self::SERVER_ERROR;
	            break;
	        default:
	            $messageType = null;
	            break;
	    }

	    if (! empty($message)) {
	        $this->setMessage($message, $messageType);
	    }

	}

}