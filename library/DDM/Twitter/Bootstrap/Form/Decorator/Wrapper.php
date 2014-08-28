<?php
/**
 * Form decorator definition
 *
 * @category Forms
 * @package Twitter_Bootstrap_Form
 * @subpackage Decorator
 * @author Christian Soronellas <csoronellas@emagister.com>
 */

/**
 * Defines a decorator to wrap all the Bootstrap form elements
 *
 * @category Forms
 * @package Twitter_Bootstrap_Form
 * @subpackage Decorator
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
class DDM_Twitter_Bootstrap_Form_Decorator_Wrapper extends Zend_Form_Decorator_Abstract
{
    /**
     * Renders a form element decorating it with the Twitter's Bootstrap markup
     *
     * @param $content
     *
     * @return string
     */
    public function render($content)
    {
        $hasErrors = $this->getElement()->hasErrors();
        $class = $this->getOption('class');
        $options = '';
        foreach($this->getOptions() as $key=>$option)
        {
            if($key == 'class')
            {
                continue;
            }
            $options .= " $key='$option' ";
        }

        return '<div '.$options.' class="control-group' . (($hasErrors) ? ' error' : '') . (($class) ? ' '.$class : '') . '">
                    ' . $content . '
                </div>';
    }
}
