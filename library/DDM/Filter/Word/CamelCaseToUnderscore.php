<?php

class DDM_Filter_Word_CamelCaseToUnderscore extends DDM_Filter_Word_CamelCaseToSeparator
{
    /**
     * Constructor
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct('_');
    }
}
