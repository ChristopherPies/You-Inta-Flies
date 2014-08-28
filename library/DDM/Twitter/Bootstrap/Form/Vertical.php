<?php
/**
 * Form definition
 *
 * @category Forms
 * @package Twitter_Bootstrap
 * @subpackage Form
 * @author Christian Soronellas <csoronellas@emagister.com>
 */

/**
 * Base class for default form style
 *
 * @category Forms
 * @package Twitter_Bootstrap
 * @subpackage Form
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
class DDM_Twitter_Bootstrap_Form_Vertical extends DDM_Twitter_Bootstrap_Form
{
    /**
     * Class constructor override.
     *
     * @param null $options
     */
    public function __construct($options = null)
    {
        $this->_initializePrefixes();
        
        $this->setElementDecorators(array(
            array('FieldSize'),
            array('ViewHelper'),
            array('ElementErrors'),
            array('Description', array('tag' => 'p', 'class' => 'help-block')),
            array('Addon'),
            array('Label', array('class' => 'control-label')),
            array('Wrapper')
        ));
        
        parent::__construct($options);
    }
}
