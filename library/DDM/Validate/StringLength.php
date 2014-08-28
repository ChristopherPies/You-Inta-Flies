<?php
/**
 *
 * String Length Validator
 * @package DDM_Validate
 *
 */

/**
 *
 * Enter description here ...
 * @package DDM_Validate
 *
 */
class DDM_Validate_StringLength extends Zend_Validate_StringLength
{

    const TOO_SHORT_DIFF = 'stringLengthTooShortDiff';
    const TOO_LONG_DIFF  = 'stringLengthTooLongDiff';

    /**
     *
     * diff
     * @var integer
     */
    protected $_diff;

    /**
     *
     * Whether we should show the difference
     * @var boolean
     */
    protected $_useDiff = false;

    /**
     * Sets validator options
     *
     * @param  integer|array|Zend_Config $options
     * @return void
     */
    public function __construct($options = array())
    {
        parent::__construct($options);
        if($options['use_diff']){
            $this->setUseDiff($options['use_diff']);
        }
        $this->_messageVariables['diff'] = '_diff';
        $this->setMessage(self::TOO_SHORT_DIFF, "This is %diff% characters too short (minimum %min% characters)");
        $this->setMessage(self::TOO_LONG_DIFF, "This is %diff% characters too long (maximum %max% characters)");
    }

    protected function setDiff( $diff )
    {
        $this->_diff = (int) $diff;
    }

    protected function setUseDiff( $useDiff )
    {
        $this->_useDiff = (bool) $useDiff;
    }

    /**
     * Defined by Zend_Validate_Interface
     *
     * Returns true if and only if the string length of $value is at least the min option and
     * no greater than the max option (when the max option is not null).
     *
     * @param  string $value
     * @return boolean
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->_error(self::INVALID);
            return false;
        }

        $this->_setValue($value);
        if ($this->_encoding !== null) {
            $length = iconv_strlen($value, $this->_encoding);
        } else {
            $length = iconv_strlen($value);
        }

        if ($length < $this->_min) {
            if($this->_useDiff){
                $this->setDiff($this->_min - $length);
                $this->_error(self::TOO_SHORT_DIFF);
            }else{
                $this->_error(self::TOO_SHORT);
            }
        }

        if (null !== $this->_max && $this->_max < $length) {
            if($this->_useDiff){
                $this->setDiff($length - $this->_max);
                $this->_error(self::TOO_LONG_DIFF);
            }else{
                $this->_error(self::TOO_LONG);
            }
       }

        if (count($this->_messages)) {
            return false;
        } else {
            return true;
        }
    }
}
