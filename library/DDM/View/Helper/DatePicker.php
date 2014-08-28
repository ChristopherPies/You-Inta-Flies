<?php

/**
* @see Zend_Registry
*/
require_once "Zend/Registry.php";

/**
 * @see ZendX_JQuery_View_Helper_UiWidget
*/
require_once "ZendX/JQuery/View/Helper/UiWidget.php";

/**
 * jQuery Date Picker View Helper
*/
class DDM_View_Helper_DatePicker extends ZendX_JQuery_View_Helper_DatePicker
{
    /**
     * (non-PHPdoc)
     * @see ZendX_JQuery_View_Helper_DatePicker::datePicker()
     */
    public function datePicker($id, $value = null, array $params = array(), array $attribs = array())
    {
        $this->jquery->addStylesheet('/css/lib/timepicker.css');

        return parent::datePicker($id, $value, $params, $attribs);
    }
}