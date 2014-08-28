<?php

class DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_Abstract
    extends DDM_Payment_DataObject 
{
    
    /**
     * An array of data keys that should be masked when calling getMaskedData().
     * The key value is an int indicating how many chars should remain unmasked.
     * Example:
     * array(
     *     'key1' => 4, first 4 chars unmasked
     *     'key2' => -4, // last 4 chars unmasked
     *     'key3' => 0, // all chars masked
     * )
     * 
     * @var array
     */
    protected $dataMasks = array();
    
    /**
     * Gets the object data with data masks applied. This works like getData().
     * 
     * @param null|array|string $key
     * @param string $maskChar
     * @param int $maskLength
     * 
     * @return mixed 
     */
    public function getMaskedData($key = null, $maskChar = 'X', $maskLength = 4) {
        $data = $this->getData($key);
        $mask = str_repeat($maskChar, $maskLength);
        
        if (is_array($data)) {
            foreach ($this->dataMasks as $key => $maskStart) {
                if (!empty($data[$key])) {
                    $this->applyDataMask($data[$key], $key, $mask);
                }
            }
        } else {
            $this->applyDataMask($data, $key, $mask);
        }
        
        return $data;
    }
    
    /**
     * Apply the data mask (if any) to the $data
     * 
     * @param mixed $data
     * @param string $key
     * @param string $mask 
     */
    protected function applyDataMask(&$data, $key, $mask) {
        if (!empty($data) && isset($this->dataMasks[$key])) {
            $maskStart = $this->dataMasks[$key];
            if ($maskStart > 0) {
                $plain = substr($data, 0, $maskStart);
                $data = $plain . $mask;
            } else if ($maskStart < 0) {
                $plain = substr($data, $maskStart);
                $data = $mask . $plain;
            } else {
                $data = $mask;
            }
        }
    }
        
    /**
     * Override this in child class to provide string representation
     * 
     * @return string
     */
    public function toString() {
        return '';
    }
    
    /**
     * Overridden to call toString()
     * 
     * @return type 
     */
    public function __toString() {
        return $this->toString();
    }
    
}
