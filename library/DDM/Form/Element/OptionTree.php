<?php

/**
 * Form Element for jQuery Tag Option Tree using jQuery optionTree plugin
 *
 * @package    DDM hack
  */
class DDM_Form_Element_OptionTree extends Zend_Form_Element_Hidden
{
    public $helper = 'OptionTree';

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