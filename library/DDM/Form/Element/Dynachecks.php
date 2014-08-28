<?php

class DDM_Form_Element_Dynachecks extends Zend_Form_Element_Xhtml
{
    public $options = array();
    
    /**
     * (non-PHPdoc)
     * @see Zend_Form_Element::loadDefaultDecorators()
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->setDecorators(array(
		    	'Dynachecks',
		        array(array('viewData' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'dynachecks')),
		    	'Errors',
		        array('Label', array('class' => 'label')),
		        array(array('data' => 'HtmlTag'), array('tag' => 'li'))
		    ));
        }
    }
    
    /**
     * Enter description here ...
     * @param unknown_type $name
     * @param unknown_type $value
     */
    public function addOption($name, $value)
    {
        $this->options[$name] = $value;    
    }

    /**
     * Enter description here ...
     * @param unknown_type $options
     */
    public function addOptions($options)
    {
        if (is_array($options)) {
            foreach ($options as $name => $value) {
                $this->addOption($name, $value);
            }
        }
    }

    public function addMultiOption($options)
    {
        return $this->addOptions($options);
    }
    
    /**
     * Enter description here ...
     */
    public function getOptions()
    {
        return $this->options;
    }
    
    public function getMultiOptions()
    {
         return $this->getOptions();   
    }
}