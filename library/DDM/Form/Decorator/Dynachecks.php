<?php

class DDM_Form_Decorator_Dynachecks extends Zend_Form_Decorator_Abstract
{
    public function render($content)
    {
        // -- only render elements of this type --
        $element = $this->getElement();
        if (!$element instanceof DDM_Form_Element_Dynachecks) {
            return $content;
        }

        // -- don't render if no view is present --
        $view = $element->getView();
        if (!$view instanceof Zend_View_Interface) {
            return $content;
        }

        $ename = $element->getName();
        $values = $element->getValue();

        $markup = "\n";
        $markup .= "<ul id=\"{$ename}_values\" class=\"checks\">\n";

        foreach ($element->getOptions() as $name => $value) {
            $checked = '';
            if( is_array($value) && in_array($name, $values) ) {
            	$checked = 'CHECKED';
            }
            $markup .= "<li><input type=\"checkbox\" name=\"{$ename}[{$name}]\"{$checked}> {$value}</li>\n";
        }
        $markup .= "</ul>\n";

        switch ($this->getPlacement()) {
            case self::PREPEND:    return $markup . $content;
            case self::APPEND:     return $content . $markup;
                      default:     return $content . $markup;
        }
    }
}