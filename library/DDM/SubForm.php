<?php

class DDM_SubForm extends Zend_Form_SubForm {

	public $elementDecorators = array(
		'ViewHelper',
		'Errors',
		'Label',
		array('label', array('class' => 'label')),
		array('description', ''),
		array(array('row' => 'HtmlTag'), array('tag' => 'li')),
		//array(array('Zend_Form_Decorator_HtmlTag' => 'HtmlTag'), ''),

	);

	public $buttonDecorators = array(
		'ViewHelper',
		//array(array('data' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'element')),
		//array(array('label' => 'HtmlTag'), array('tag' => 'ul', 'placement' => 'prepend')),
		array(array('row' => 'HtmlTag'), array('tag' => 'li', 'class' => 'submit')),
	);

	public $elementFileDecorators = array(
		'ViewHelper',
		'Errors',
		'Label',
		array('label', array('class' => 'label')),
		array('description', ''),
		array(array('row' => 'HtmlTag'), array('tag' => 'li')),
		//array(array('Zend_Form_Decorator_HtmlTag' => 'HtmlTag'), ''),

	);

	// not used yet
	public $subFormDecorators = array(
		'ViewHelper',
		//array(array('data' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'element')),
		//array(array('label' => 'HtmlTag'), array('tag' => 'td', 'placement' => 'prepend')),
		array(array('row' => 'HtmlTag'), array('tag' => 'li')),
	);

	public $elementHiddenDecorators = array(
		'ViewHelper',
		'Errors',
		//array(array('data' => 'HtmlTag'), array('tag' => 'ul', 'class' => 'element')),
		array('Label', ''),
		array(array('row' => 'HtmlTag'), null),
	);


	/**
     * Load the default decorators
     *
     * @return Zend_Form_SubForm
     */
    public function loadDefaultDecorators()
    {
        if ($this->loadDefaultDecoratorsIsDisabled()) {
            return $this;
        }

        $decorators = $this->getDecorators();
        if (empty($decorators)) {
            $this->addDecorator('FormElements');
            $this->addDecorator('HtmlTag', array('tag' => 'ul'));
            $this->addDecorator('Fieldset');
            //$this->addDecorator('UlLiWrapper');
        }
        return $this;
    }

    /**
	 * Tweak how we add elements to the form
	 *
	 * @param unknown_type $element
	 * @param unknown_type $name
	 * @param unknown_type $options
	 * @param boolean $keepZendDecorators
	 */
	public function addElement( $element, $name = null, $options = null, $keepZendDecorators = false ) {

	    // filters that could be added to elements, depending on type.
		$specialCharsFilter = new Zend_Filter_Callback(
			array('callback' => 'cleanSpecialChars')
		);
		$encodeFilter = new Zend_Filter_Callback(
			array('callback' => 'htmlentities')
		);
		$decodeFilter = new Zend_Filter_Callback(
			array('callback' => 'html_entity_decode')
		);

		$filterChain = new Zend_Filter();
        $filterChain->addFilter($specialCharsFilter);
		$filterChain->addFilter($decodeFilter);
        $filterChain->addFilter($encodeFilter);


		parent::addElement( $element, $name, $options );

		// bail if we don't need to tweak decorators
		if( $keepZendDecorators === true ) {
			return;
		}

		if( $name == null ) {
			$name = $element->getName();
		}

		if( $name !== null ) {
			$ele = $this->getElement($name);
			$ele->addFilter($filterChain);
			$type = $ele->getType();

			$currentDecos = $ele->getDecorators();
			$currentNames = array_keys($currentDecos);
			foreach($currentNames as &$n) {
				$n = str_replace('Zend_Form_Decorator_', '', $n);
			}

			if( strpos($type, 'Button') !== false || strpos($type, 'Submit') !== false ) {
				$ele->setDecorators( $this->buttonDecorators );

			} else if( strpos($type, 'Hidden') !== false ) {

				//ppr($currentDecos);
				//$ele->setDecorators( $this->elementHiddenDecorators );
				$ele->removeDecorator('row');
				$ele->removeDecorator('Errors');
				$ele->removeDecorator('Label');
				//ppr($ele->getDecorators() ); exit;



			} else if( strpos($type, 'Captcha') !== false ) {
				$ele->setDecorators( $this->elementDecorators );
				// remove view helper, it makes it render twice
				$ele->removeDecorator( 'ViewHelper' );

			} else if( strpos($type, 'File') !== false ) {
				$new = array();
				// file deco must be firstf
				$new[] = $currentDecos['Zend_Form_Decorator_File'];
				foreach($this->elementDecorators as $d) {
					$new[] = $d;
				}
				$ele->setDecorators( $new );
				// remove view helper, it makes it render twice
				$ele->removeDecorator( 'ViewHelper' );

			} else if( is_subclass_of($ele, 'ZendX_JQuery_Form_Element_UiWidget') === false ) {
				$ele->setDecorators( $this->elementDecorators );

			} else {

				foreach( $this->elementDecorators as $dk => $d ) {
					if( is_array( $d) ) {
						//echo "$dk <BR>";
						$ele->addDecorator($d[0],$d[1]);
					}
					//Zend_Form_Element::addDecorator( $d );
				}


			}
		}

		//ppr($ele->getDecorators() ); exit;

	}


}
