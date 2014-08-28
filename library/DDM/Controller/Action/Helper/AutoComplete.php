<?php

class DDM_Controller_Action_Helper_AutoComplete
extends ZendX_JQuery_Controller_Action_Helper_AutoComplete
{
    /**
     * Prepare autocompletion data
     *
     * @param  mixed   $data
     * @param  boolean $keepLayouts
     * @return mixed
     */
    public function prepareAutoCompletion($data, $keepLayouts = false)
    {
        if (!$this->validateData($data)) {
            /**
             * @see Zend_Controller_Action_Exception
             */
            require_once 'Zend/Controller/Action/Exception.php';
            throw new Zend_Controller_Action_Exception('Invalid data passed for autocompletion');
        }
        $data = (array) $data;
        $output = $this->encodeJson($data);
        return $output;
    }
}