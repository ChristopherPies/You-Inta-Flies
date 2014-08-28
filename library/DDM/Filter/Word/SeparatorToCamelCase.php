<?php

class DDM_Filter_Word_SeparatorToCamelCase extends Zend_Filter_Word_Separator_Abstract
{

    public function filter($value)
    {
        // a unicode safe way of converting characters to \x00\x00 notation
        $pregQuotedSeparator = preg_quote($this->_separator, '#');

        if (self::isUnicodeSupportEnabled()) {
            parent::setMatchPattern(array('#('.$pregQuotedSeparator.')((?:\p{L}|\p{Nd}){1})#e','#(^(?:\p{Ll}|\p{Nd}){1})#e'));
            parent::setReplacement(array("strtoupper('\\2')","strtoupper('\\1')"));
        } else {
            parent::setMatchPattern(array('#('.$pregQuotedSeparator.')([A-Za-z0-9]{1})#e','#(^[A-Za-z0-9]{1})#e'));
            parent::setReplacement(array("strtoupper('\\2')","strtoupper('\\1')"));
        }

        return parent::filter($value);
    }

}
