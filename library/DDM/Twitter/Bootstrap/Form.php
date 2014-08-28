<?php
/**
 * Base form class definition
 * https://github.com/Emagister/zend-form-decorators-bootstrap
 * @category Forms
 * @package Twitter
 * @subpackage Bootstrap
 * @author Christian Soronellas <csoronellas@emagister.com>
 */

/**
 * This is the base abstract form for the Twitter's Bootstrap UI
 *
 * @category Forms
 * @package Twitter
 * @subpackage Bootstrap
 * @author Christian Soronellas <csoronellas@emagister.com>
 */
abstract class DDM_Twitter_Bootstrap_Form extends DDM_Form
{
    /**
     * Class constants
     */
    const DISPOSITION_HORIZONTAL = 'horizontal';
    const DISPOSITION_INLINE     = 'inline';
    const DISPOSITION_SEARCH     = 'search';
    
    protected $_prefixesInitialized = false;

    /**
     * Override the base form constructor.
     *
     * @param string $name
     */
    public function __construct($name = null,$formType='inline')
    {
        $this->_initializePrefixes();
        
        
        switch ($formType)
        {
            case 'vertical':
                $this->setElementDecorators(array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('ElementErrors'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('Addon'),
                    array('Label', array('class' => 'control-label')),
                    array('Wrapper')
                ));
                break;
            case 'horizontal':
                $this->setDisposition(self::DISPOSITION_HORIZONTAL);

                $this->setElementDecorators(array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('Addon'),
                    array('ElementErrors'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'controls')),
                    array('Label', array('class' => 'control-label')),
                    array('Wrapper')
                ));
                break;
            case 'inline':
            default:
                $this->setDisposition(self::DISPOSITION_INLINE);

                $this->setElementDecorators(array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('Addon'),
                    array('ElementErrors'),
                    array('Label', array('class' => 'control-label')),
                    array('Wrapper')
                ));
        }
        
        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));
        parent::__construct($name);
        $this->setDisableLoadDefaultDecorators(false);
    }
    
    public function addElement($element, $name = null, $options = null, $keepZendDecorators = false)
    {
        if($element instanceof Zend_Form_Element)
        {
            $decorators = array();
            if($element instanceof ZendX_JQuery_Form_Element_UiWidget)
            {
                $element->getDecorators();
                $decorators ['ZendX_JQuery_Form_Decorator_UiWidgetElement']= $element->getDecorator('ZendX_JQuery_Form_Decorator_UiWidgetElement');
            }
            $decorators = array_merge($decorators,$this->_elementDecorators);
            $element->setDecorators($decorators);
        }
        parent::addElement($element, $name, $options,true);

        // bail if we don't need to tweak decorators
        if ($keepZendDecorators === true || $this->keepZendDecorators === true) {
//                return;
        }
        $hasPrimaryButton = false;

        if ($name == null) {
                $name = $element->getName();
        }
        $e = $this->getElement($name);
        $type = get_class($e);
        
        $e->getDecorators();
        if($e->getDecorator('wrapper') && $e->isRequired()) {
            $e->getDecorator('wrapper')->setOption('class', 'control-group warning');
        }

        if(strpos($type, 'Text') !== false) {
            if(!$e->getAttrib('class'))
            {
                $e->setAttrib('class', 'input input-xxlarge');
            }
        }
        if(strpos($type, 'Select') !== false) {
            if(!$e->getAttrib('class'))
            {
                $e->setAttrib('class', 'input input-xxlarge');
            }
        }    
        if(strpos($type, 'Submit') !== false) {
            $e->setDecorators(array(
                    array('ViewHelper'),
                    array('Description'),
                    //array('HtmlTag', array('tag' => 'div', 'class'=>'control-group')),
            ));
            foreach($this->getElements() as $ele)
            {
                if(strstr($ele->getAttrib('class'),'btn-primary'))
                {

                    $hasPrimaryButton = true;
                    break;
                }
            }
            if(!$hasPrimaryButton && !trim(str_replace('btn','',$e->getAttrib('class'))))
            {
                $e->setAttrib('class','btn btn-primary btn-large');
            }
            else
            {
                $e->setAttrib('class','btn btn-large');
            }

        } else if(strpos($type, 'Button') !== false) {
           $e->setDecorators(array(
                    array('ViewHelper'),
                    array('Description'),
                    //array('HtmlTag', array('tag' => 'div', 'class'=>'control-group')),
            )); 
            
           $e->setAttrib('escape', false);
            foreach($this->getElements() as $ele)
            {
                if(strstr($ele->getAttrib('class'),'btn-primary'))
                {
                    $hasPrimaryButton = true;
                    break;
                }
            }
            if(!$hasPrimaryButton && !trim(str_replace('btn','',$e->getAttrib('class'))))
            {
                $e->setAttrib('class','btn btn-primary btn-large');
            }
            else
            {
                $e->setAttrib('class','btn btn-large');
            }

        } else if (strpos($type, 'Hidden') !== false) {
                    $e->setDecorators(array('ViewHelper')); // no layout around these

        } else if (strpos($type, 'Captcha') !== false) {
                    $e->removeDecorator('ViewHelper');

        } 

        return $e;

    }
    
    protected function _initializePrefixes()
    {
        if (!$this->_prefixesInitialized)
        {
            $this->getView()->addHelperPath(
                    'DDM/Twitter/Bootstrap/View/Helper',
                    'DDM_Twitter_Bootstrap_View_Helper'
            );
            
            $this->addPrefixPath(
                    'DDM_Twitter_Bootstrap_Form_Element',
                    'DDM/Twitter/Bootstrap/Form/Element',
                    'element'
            );
            
            $this->addElementPrefixPath(
                    'DDM_Twitter_Bootstrap_Form_Decorator',
                    'DDM/Twitter/Bootstrap/Form/Decorator',
                    'decorator'
            );
            
            $this->addDisplayGroupPrefixPath(
                    'DDM_Twitter_Bootstrap_Form_Decorator',
                    'DDM/Twitter/Bootstrap/Form/Decorator'
            );
            
            $this->setDefaultDisplayGroupClass('DDM_Twitter_Bootstrap_Form_DisplayGroup');
            
            $this->_prefixesInitialized = true;
        }
    }

    /**
     * Adds default decorators if none are specified in the options and then calls Zend_Form::createElement()
     * (non-PHPdoc)
     * @see Zend_Form::createElement()
     */
    public function createElement($type, $name, $options = null)
    {
        // If we haven't specified our own decorators, add the default ones in.
        if (is_array($this->_elementDecorators)) {
            if (null === $options) {
                $options = array('decorators' => $this->_elementDecorators);
            } elseif ($options instanceof Zend_Config) {
                $options = $options->toArray();
            }

            if ( is_array($options) && !array_key_exists('decorators', $options) ) {
                $options['decorators'] = $this->_elementDecorators;
            }
        }
        return parent::createElement($type, $name, $options);
    }
    
    /**
     * @param string $disposition
     */
    public function setDisposition($disposition)
    {
        if (
            in_array(
                $disposition,
                array(
                    self::DISPOSITION_HORIZONTAL,
                    self::DISPOSITION_INLINE,
                    self::DISPOSITION_SEARCH
                )
            )
        ) {
            $this->_addClassNames('form-' . $disposition);
        }
    }

    /**
     * Adds a class name
     *
     * @param string $classNames
     */
    protected function _addClassNames($classNames)
    {
        $classes = $this->_getClassNames();

        foreach ((array) $classNames as $className) {
            $classes[] = $className;
        }

        $this->setAttrib('class', implode(' ', $classes));
    }

    /**
     * Removes a class name
     *
     * @param string $classNames
     */
    protected function _removeClassNames($classNames)
    {
        $classes = $this->getAttrib('class');

        foreach ((array) $classNames as $className) {
            if (false !== strpos($classes, $className)) {
                str_replace($className . ' ', '', $classes);
            }
        }
    }

    /**
     * Extract the class names from a Zend_Form_Element if given or from the
     * base form
     *
     * @param Zend_Form_Element $element
     * @return array
     */
    protected function _getClassNames(Zend_Form_Element $element = null)
    {
        if (null !== $element) {
            return explode(' ', $element->getAttrib('class'));
        }

        return explode(' ', $this->getAttrib('class'));
    }
}
