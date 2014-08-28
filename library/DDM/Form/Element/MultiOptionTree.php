<?php

/**
 * Form Element for jQuery Tag Option Tree using jQuery optionTree plugin
 *
 * @package    DDM hack
  */
class DDM_Form_Element_MultiOptionTree extends Zend_Form_Element_Multi
{
    public $helper = 'MultiOptionTree';
    protected $_isArray = true;

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