<?php

class DDM_Filter_Crap2Html extends Zend_Filter_StripTags
{
    private $closePtoBr = true;
    private $replaceNbspWithSpace = true;
    private $stripAttribsInTags = true;

    public function __construct($options = null) {
        parent::__construct($options);
        if(isset($options['closePtoBr'])) {
            $this->closePtoBr = (boolean) $options['closePtoBr'];
        }
        if(isset($options['replaceNbspWithSpace'])) {
            $this->replaceNbspWithSpace = (boolean) $options['replaceNbspWithSpace'];
        }
        if(isset($options['stripAttribsInTags'])) {
            $this->stripAttribsInTags = (boolean) $options['stripAttribsInTags'];
        }
    }

    public function filter($value)
    {

        $allowed = $this->getTagsAllowed();
        if(!count($allowed)) {
            $allowed = '';
        } else {
            $allowed = array_keys($allowed);
            $allowed = '<'. join($allowed, '><') . '>';
        }

        return crap2html($value, $allowed, $this->closePtoBr, $this->replaceNbspWithSpace, $this->stripAttribsInTags);

    }

}
