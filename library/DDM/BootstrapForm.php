<?php

class DDM_BootstrapForm extends DDM_Form {

    /**
     * Class constants
     */
    const DISPOSITION_HORIZONTAL = 'horizontal';
    const DISPOSITION_INLINE     = 'inline';
    const DISPOSITION_SEARCH     = 'search';
    
    protected $_prefixesInitialized = false;
    
    public $elementDecorators;
    
    /**
     * Override the base form constructor.
     *
     * @param string $name
     */
    public function __construct($name = null,$formType = 'horizontal')
    {
        $this->_initializePrefixes();
        
        $this->setBootStrapFormType($formType);
        
        $this->setDecorators(array(
            'FormElements',
            'Form'
        ));
        parent::__construct($name);
    }
    
    public function setBootStrapFormType($formType='inline')
    {
        switch ($formType)
        {
            case 'vertical':
                $this->elementDecorators = array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('ElementErrors'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('Addon'),
                    array('Label', array('class' => 'control-label', 'escape'=>false, 'requiredSuffix' => '&nbsp;<span class="required-suffix">*</span> ')),
                    array('Wrapper')
                );
                break;
            case 'horizontal':
                $this->setDisposition(self::DISPOSITION_HORIZONTAL);

                $this->elementDecorators = array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('Addon'),
                    array('ElementErrors'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('HtmlTag', array('tag' => 'div', 'class' => 'controls')),
                    array('Label', array('class' => 'control-label', 'escape'=>false, 'requiredSuffix' => '&nbsp;<span class="required-suffix">*</span> ')),
                    array('Wrapper')
                );
                break;
            case 'inline':
            default:
                $this->setDisposition(self::DISPOSITION_INLINE);

                $this->elementDecorators = array(
                    array('FieldSize'),
                    array('ViewHelper'),
                    array('Description', array('tag' => 'p', 'class' => 'help-block')),
                    array('Addon'),
                    array('ElementErrors'),
                    array('Label', array('class' => 'control-label', 'escape'=>false, 'requiredSuffix' => '&nbsp;<span class="required-suffix">*</span> ')),
                    array('Wrapper')
                );
        }
    }
    
    public function fixElement($e)
    {
        $type = get_class($e);

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
        if(strpos($type, 'Submit') !== false || strpos($type, 'DDM_Twitter_Bootstrap_Form_Element_Button') !== false) {
            $this->tweakType($e->getName(), 'Button', $e->getOrder());
            $e = $this->getElement($e->getName());
            if($e->getAttrib('type') != 'button')
            {
                $e->setAttrib('type', 'submit');
            }
            if(!trim(str_replace(array('btn-large','btn'),'',$e->getAttrib('class'))))
            {
                $e->setAttrib('class','btn btn-large');
            }

        } else if(strpos($type, 'Button') !== false) {
           $e->setAttrib('escape', false);
            if(!trim(str_replace(array('btn-large','btn'),'',$e->getAttrib('class'))))
            {
                $e->setAttrib('class','btn btn-large');
            }

        } 
        return $e;
    }
    
    public function addElement($element, $name = null, $options = null, $keepZendDecorators = false)
    {
        parent::addElement($element, $name, $options,true);
        
        if ($name == null) {
            $name = $element->getName();
        }
        $e = $this->getElement($name);

        // bail if we don't need to tweak decorators
        if (!$keepZendDecorators && !$this->keepZendDecorators) {
            $e = $this->fixElement($e);
        }
        if($e instanceof Zend_Form_Element)
        {
            if($e instanceof ZendX_JQuery_Form_Element_UiWidget)
            {
                $decorators = array();
                $e->getDecorators();
                $decorators ['ZendX_JQuery_Form_Decorator_UiWidgetElement']= $e->getDecorator('ZendX_JQuery_Form_Decorator_UiWidgetElement');
                $e->setDecorators(array_merge($decorators,$this->elementDecorators));
                $e->removeDecorator('ViewHelper');
            }
            else if ($e instanceof Zend_Form_Element_Button || $e instanceof Zend_Form_Element_Submit)
            {
                $e->setDecorators(array(
                        array('ViewHelper'),
                        array('Description'),
                ));
            }
            else if($e instanceof Zend_Form_Element_File)
            {
                $decorators = array();
                $decorators ['file']= $e->getDecorator('file');
                $e->setDecorators(array_merge($decorators,$this->elementDecorators));
                $e->removeDecorator('ViewHelper');
            }
            else if($e instanceof Zend_Form_Element_Hidden && strpos($e->getType(), 'Hidden'))
            {
                $e->setDecorators(array(array('ViewHelper')));
            }
            else
            {
                if(!empty($this->elementDecorators))
                {
                    $e->setDecorators($this->elementDecorators);
                }
            }
            if ($e instanceof Zend_Form_Element_Captcha) 
            {
                $e->removeDecorator('ViewHelper');
            } 
        }
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
    
    public function tweakType($elementName, $newType, $order = null)
    {
        $element = parent::tweakType($elementName, $newType, $order);
        if($element instanceof Zend_Form_Element_MultiCheckbox || $element instanceof Zend_Form_Element_Checkbox || $element instanceof Zend_Form_Element_Radio)
        {
            $element->setAttrib('class', str_replace('input-xxlarge', '', $element->getAttrib('class')));
        }
        return $element;
    }
    
    public function render(Zend_View_Interface $view = null)
    {
        $firstButton = true;
        $hasPrimary = false;
        foreach($this->getElements() as $element)
        {
            if($element instanceof Zend_Form_Element_Button || $element instanceof Zend_Form_Element_Submit)
            {
                if(strpos($element->getAttrib('class'),'btn-primary'))
                {
                    $hasPrimary = true;
                }
            }
        }
        foreach($this->getElements() as $element)
        {
            if($element->isRequired())
            {
                if($element->getDecorator('wrapper') && !$element->getErrors()) {
                    $element->getDecorator('wrapper')->setOption('class', 'control-group warning');
                }
            }
            if(!$hasPrimary && $firstButton && ($element instanceof Zend_Form_Element_Button || $element instanceof Zend_Form_Element_Submit))
            {
                if(!trim(str_replace(array('btn-large','btn'),'',$element->getAttrib('class'))))
                {
                    $element->setAttrib('class', 'btn btn-large btn-primary');
                }
                $firstButton = false;
            }
        }
        return parent::render($view);
    }
}
