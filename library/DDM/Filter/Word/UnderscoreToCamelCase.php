<?php

class DDM_Filter_Word_UnderscoreToCamelCase extends DDM_Filter_Word_SeparatorToCamelCase
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
