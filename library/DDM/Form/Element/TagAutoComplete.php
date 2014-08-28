<?php

/**
 * Form Element for jQuery Tag Auto-Complete using jQuery tagit plugin
 *
 * @package    DDM hack
  */
class DDM_Form_Element_TagAutoComplete extends Zend_Form_Element_Multi
{
    public $helper = 'TagAutoComplete';
    protected $_isArray = true;
    protected $_options = array();

    public function __construct($spec, $options = null)
    {
      parent::__construct($spec, $options);

      if(isset($options['tagit'])) {
        $this->_options = array_merge($this->_options, $options['tagit']);
      }
    }

    /**
     * Returns an array of the tags this element has
     *
     * @return array
     */
    public function getValue()
    {
        $helperTags = $this->getView()->getHelper($this->helper)->tags;
        return isset($helperTags[$this->_name]) ? $helperTags[$this->_name] : array();
    }

    /**
     * Sets the given array of tags on the element
     *
     * @param array $tags
     * @return DDM_Form_Element_TagAutoComplete
     */
    public function setValue(array $tags = null)
    {
        if($tags !== null) {
            $this->getView()->getHelper($this->helper)->tags[$this->_name] = $tags;
        }
        return $this;
    }

    /**
     * Get the options that were passed to instantiate this element
     */
    public function getOptions()
    {
      return $this->_options;
    }

     /**
     * Is the value provided valid?
     *
     * @param  string $value
     * @param  mixed $context
     * @return bool
     */
    public function isValid($value, $context = null)
    {
        $this->setValue($value);

        return true;
    }
}