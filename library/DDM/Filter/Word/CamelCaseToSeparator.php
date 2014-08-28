<?php

class DDM_Filter_Word_CamelCaseToSeparator extends Zend_Filter_Word_Separator_Abstract
{

    public function filter($value)
    {
        if (self::isUnicodeSupportEnabled()) {
            parent::setMatchPattern(array('#(?<=(?:\p{Lu}|\p{N}))((?:\p{Lu}|\p{N})(?:\p{Ll}|\p{N}))#','#(?<=\p{Ll})(\p{Lu}|\p{Nd})#'));
            parent::setReplacement(array($this->_separator . '\1', $this->_separator . '\1'));
        } else {
            parent::setMatchPattern(array('#(?<=(?:[A-Z0-9]))([A-Z0-9]+)([A-Z0-9][A-z0-9])#', '#(?<=(?:[a-z]))([A-Z0-9])#'));
            parent::setReplacement(array('\1' . $this->_separator . '\2', $this->_separator . '\1'));
        }

        return parent::filter($value);
    }

}
