<?php

class DDM_Payment_Invoice_LineItem_Abstract implements DDM_Payment_Invoice_LineItem {
    
    protected $id;
    protected $name;
    protected $description;
    protected $value = 0;
    protected $defaultFormat = "Description: %s\nValue: %d";
    protected $quantity = 1;
    protected $currency;
    
    /**
     * Constructor
     * 
     * @param float|array $options The item value, or an array of options
     */
    public function __construct($options = null) {
        if (is_numeric($options)) {
            $this->value = $options;
        } else if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    /**
     * Sets class options
     * 
     * @param array $options 
     */
    public function setOptions(array $options = null) {
        foreach ($options as $name => $value) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }
    
    /**
     * Returns a string representation of the item, optionally with a custom format.
     * 
     * @param string $format OPTIONAL
     * 
     * @return string
     */
    public function toString($format = null) {
        if (!$format || !is_string($format)) {
            $format = $this->getDefaultFormat();
        }
        return sprintf($format, $this->getDescription(), $this->getTotal());
    }
    
    /**
     * Overridden to call toString() with default format
     * 
     * @return string
     */
    public function __toString() {
        return $this->toString();
    }
    
    /**
     * Gets the id
     * 
     * @return int|string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the id
     * 
     * @param int|string $id 
     */
    public function setId($id) {
        $this->id = $id;
    }
    
    /**
     * Gets the name. If the name has not been set, this will be the item type.
     * 
     * @return string
     */
    public function getName() {
        if (!$this->name) {
            $this->name = str_replace('DDM_Payment_Invoice_LineItem_', '', get_class($this));
        }
        return $this->name;
    }
    
    /**
     * Sets the name
     * 
     * @param string $name 
     */
    public function setName($name) {
        $this->name = $name;
    }
    
    /**
     * Gets the description
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }

    /**
     * Sets the description
     * 
     * @param string $description 
     */
    public function setDescription($description) {
        $this->description = $description;
    }

    /**
     * Gets the format string used for toString()
     * 
     * @return string
     */
    public function getDefaultFormat() {
        return $this->defaultFormat;
    }
    
    /**
     * Sets the format string to use for toString()
     * 
     * @param string $defaultFormat 
     */
    public function setDefaultFormat($defaultFormat) {
        $this->defaultFormat = $defaultFormat;
    }
    
    /**
     * Gets the value
     * 
     * @return float
     */
    public function getValue() {
        return $this->value;
    }
    
    /**
     * Sets the value
     * 
     * @param float $value
     */
    public function setValue($value) {
        $this->value = (float) $value;
    }
    
    /**
     * Gets the total. For line items that don't allow quantities, this will
     * be the same as getValue().
     * 
     * @return float
     */
    public function getTotal() {
        return (float) $this->quantity * $this->value;
    }
    
}
