<?php

require_once 'Zend/Exception.php';

class DDM_Db_Exception_Validation extends Zend_Exception
{
    protected $_validationErrors;

    public function __construct($validationErrors)
    {
        $this->_validationErrors = $validationErrors;
        parent::__construct('Validation failed');
    }

    public function getValidationErrors()
    {
        return $this->_validationErrors;
    }
}