<?php

require_once('ZendX/JQuery/Form.php');
require_once('DDM/Functions.php');

class DDM_Form extends ZendX_JQuery_Form {

	/**
	 * Enable local saving of form fields (input, textarea, select)
	 *
	 * @var boolean
	 */
	protected $enableLocalSave = false;

	/**
	 * Enable auto saving of form fields
	 *
	 * @var boolean
	 */
	protected $enableAutoSave = false;

	/**
	 * Render Json
	 *
	 * @var boolean
	 */
	protected $renderJson = false;

	/**
	 * field prefix
	 *
	 * @var string
	 */
	protected $fieldPrefix = '';

        /**
         * Whether to show an error summary at the top of the form -
         * requires this template in your default script path "partials/form-error-summary.phtml"
         *
         * @var boolean
         */
        protected $showErrorSummary = false;

	/**
	 * Determines if we verify the CSRF
	 * @var boolean
	 */
	protected $csrfEnabled = true;

	public $elementDecorators = array(
		'ViewHelper',
		'Errors',
		'Label',
		array('label', array('class' => 'label', 'requiredSuffix' => ' * ' ) ),
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
		array(array('row' => 'HtmlTag'), ''),
	);

	// array of view scripts and their placement
	protected $viewScripts = array();

	public function init()
	{

	}

	public function loadDefaultDecorators()
	{
	    parent::loadDefaultDecorators();
		$this->setDecorators(array(
			'FormElements',
			array('HtmlTag', array('tag' => 'ul')),
			'Form',
		));
	}

	/**
	 * Add a form
	 *
	 * @param Zend_Form $form
	 * @param unknown_type $name
	 * @param unknown_type $order
	 */
	public function addSubForm(Zend_Form $form, $name, $order = null ) {
		$fields = array_keys($form->getElements());
		foreach($fields as $f) {
			if( substr( $f, -5) == '_csrf' ) {
				$form->removeElement($f);
				///ppr(  array_keys($form->getElements()) ); exit;
			}
		}
		parent::addSubForm($form, $name, $order);

		$fields = array_keys($this->getElements());
		foreach($fields as $f) {
			if( substr( $f, -5) == '_csrf' ) {
				$this->removeElement($f);
			}
		}

	}

	public function __construct( $name=null )
	{
        $this->setDisableLoadDefaultDecorators(true);
		// make sure we have a name
		if( $name == null ) {
			$name = get_class($this);
		}
		//echo "name a new " . get_class($this) . ' as ' . $name . "<BR>";
		$this->setAttrib('name', $name );

		if($this->isCsrfEnabled()) {
			// add CSRF protection -- needs more testing
			$this->csrf();
		}

		parent::__construct();

		// allow custom form elements to be created in DDM/Form/Element (Andrew)
		$this->addPrefixPath('DDM_Form_Decorator', 'DDM/Form/Decorator', 'decorator')
             ->addPrefixPath('DDM_Form_Element', 'DDM/Form/Element', 'element')
             ->addElementPrefixPath('DDM_Form_Decorator', 'DDM/Form/Decorator', 'decorator')
             ->addDisplayGroupPrefixPath('DDM_Form_Decorator', 'DDM/Form/Decorator');

		// default the action to self
		if ($this->getAction() == '') {
                    if(isset($_SERVER['REQUEST_URI'])) {
                        $this->setAction( $_SERVER['REQUEST_URI'] );
                    }
		}

		$this->addAttribs( array('accept-charset' => 'utf-8' ) );

		/*
		// At decorator instantiation:
		$element->addDecorator('Description', array('escape' => false));
		*/
	}

	/**
	 * Tweak how we add elements to the form
	 *
	 * @param unknown_type $element
	 * @param unknown_type $name
	 * @param unknown_type $options
	 * @param boolean $keepZendDecorators
	 */
	public function addElement($element, $name = null, $options = null, $keepZendDecorators = false)
	{

		parent::addElement($element, $name, $options);

		// bail if we don't need to tweak decorators
		if ($keepZendDecorators === true) {
			return;
		}

		if ($name == null) {
			$name = $element->getName();
		}

		if ($name !== null) {
            $ele = $this->getElement($name);
			$type = $ele->getType();

			$currentDecos = $ele->getDecorators();
			$currentNames = array_keys($currentDecos);
			foreach ($currentNames as &$n) {
				$n = str_replace('Zend_Form_Decorator_', '', $n);
			}

			if (strpos($type, 'Submit') !== false && strpos($ele->getAttrib('class'),'btn') !== false ) {
				$this->tweakType($ele->getName(), 'Button', $ele->getOrder());
				$ele = $this->getElement($name);
				$type = $ele->getType();
				$ele->setAttrib('type', 'submit');
			}

			if (strpos($type, 'Button') !== false || strpos($type, 'Submit') !== false || strpos($type, 'Image') !== false) {
				$ele->setDecorators( $this->buttonDecorators );

			} else if (strpos($type, 'Hidden') !== false) {
				//$ele->setDecorators( $this->elementHiddenDecorators );
				$ele->setDecorators(array('ViewHelper'));

			} else if (strpos($type, 'Captcha') !== false) {
				$ele->setDecorators($this->elementDecorators);
				// remove view helper, it makes it render twice
				$ele->removeDecorator('ViewHelper');

			} else if (strpos($type, 'File') !== false) {
				$new = array();
				// file deco must be firstf
				$new[] = $currentDecos['Zend_Form_Decorator_File'];
				foreach ($this->elementDecorators as $d) {
					$new[] = $d;
				}
				$ele->setDecorators( $new );
				// remove view helper, it makes it render twice
				$ele->removeDecorator( 'ViewHelper' );

			} else if (is_subclass_of($ele, 'ZendX_JQuery_Form_Element_UiWidget') === false) {
				$ele->setDecorators($this->elementDecorators);

			} else {
				foreach ($this->elementDecorators as $dk => $d) {
					if (is_array($d)) {
						$ele->addDecorator($d[0], $d[1]);
					}
				}
			}

		}

		//ppr($ele->getDecorators() ); exit;

	}

	public function csrf() {

		$name = $this->getName() . '_csrf';

		// only add one of these fields...
		if( is_object($this->getElement( $name ) ) ) {
			return true;
		}

		// makes the token if it does not exist
		$token = $this->getCsrfToken();

		// add some CSRF protection for all DDM Forms
        $this->addElement('hidden', $name );
        $ele = $this->getElement($name)->setValue( $token );

        return $ele;

	}


	/**
	 * Is the form data valid?
	 *
	 * @param array $data
	 * @return boolean
	 */
	public function isValid($data) {

	    $this->populateOptions();
		$valid = parent::isValid($data);

		if( $this->isCsrfEnabled() ) {
			$csrfName = $this->getName() . '_csrf';
			$ele = $this->getElement( $csrfName );
			$value = null;
			if( is_object($ele) ) {
				$value = $ele->getValue();
				$token = $this->getCsrfToken();
				if( $token != $value || !$value ) {
					$ele->setErrors(array('Invalid CSRF token. Please try submitting the form again.'));
					$valid = false;
					// refresh the token. If they logged out, the form will retain the old value. If we can pull
					// from the session and "fix" the value then they will only see it once and they can retry.
					$ele->setValue( $this->getCsrfToken() );
				}
			}
		}

		$this->_errorsExist = !$valid;
		return $valid;

	}

        /**
         * Finds all errors and error messages in a form and it's sub forms and groups them
         * in an array with the path to the default id the element would have on the page
         *
         * @param Zend_Form $form
         * @param array $errors - for recursive calls
         * @param string $concatName - for recursive calls
         * @return array
         */
        public function getAllErrors($form,$errors = array(),$concatName=false)
        {
            foreach($form->getElements() as $element)
            {
                foreach(array_merge($element->getErrorMessages(),$element->getErrors()) as $error)
                {
                    if(!empty($error))
                    {
                        $label = ($element->getLabel()) ? $element->getLabel() : $element->getName();

                        $errors []= array(
                            'element'=>(($concatName)?$form->getName().'-':'').$element->getName(),
                            'error'=>$error,
                            'label' => $label
                        );
                    }
                }
            }
            foreach($form->getSubForms() as $subForm)
            {
                $errors = $this->getAllErrors($subForm,$errors,true);
            }
            return $errors;
        }


	/**
	 * Require all fields except ones with the given names
	 *
	 * @param array $except
	 */
	public function requireAll( $except = null ) {
		// require all elements
        $eles = $this->getElements();
        foreach($eles as &$e) {

        	if( $e instanceof Zend_Form_Element_Hidden ) {
        		continue;
        	}

        	if( $except !== null && in_array( $e->getName(), $except ) ) {
        		$e->setRequired( false );
        		continue;
        	}
        	$e->setRequired( true );
        }
	}

	/**
	 * Remove all elements except the listed ones
	 *
	 * @param array $elementsToKeep
	 */
	public function removeAllExcept( $elementsToKeep ) {
		$eles = $this->getElements();
		foreach($eles as $e) {
			// don't remove the csrf in a batch
			if( $this->getName() . '_csrf' == $e->getName() ) {
				continue;
			}
			if( !in_array($e->getName(), $elementsToKeep) ) {
				$this->removeElement( $e->getName() );
			}
		}
	}

	/**
	 * Change the type of a field
	 *
	 * @param string $elementName
	 * @param string $newType
	 * @param int	$order
	 *
	 * @return Zend_Form_Element
	 */
	public function tweakType( $elementName, $newType, $order = null ) {

		// get what we neeed from the
		$e = $this->getElement( $elementName );
		if( !is_object($e) ) {
			return false;
		}
		$filters = $e->getFilters();
		$label = $e->getLabel();
		if( $newType == 'Hidden' ) {
			$label = '';
		}
		$attribs = $e->getAttribs();
		$validators = $e->getValidators();
		$required = $e->isRequired();

		// the helper (type) is in the attribs, that is what we want to change.
		//nuke it or the new field will render the same as the old
		unset($attribs['helper']);


		// default to a new Zend Form Element, unless it looks like we were given a class name
		if( strpos($newType, '_') === false ) {
			$newType = 'Zend_Form_Element_'. $newType;
		}

		// make the new element
		$new = new $newType( $elementName );
		$new->setLabel($label);
		$new->setAttribs($attribs);
		$new->setRequired($required);
		if( count($validators) ) {
			$new->addValidators( $validators );
		}
		if($order){
		    $new->setOrder($order);
		}

		// add it to the form
		$this->addElement($new);

		return $this->getElement($elementName);

	}

	/**
     * This will set the form class
     * @param string $class
     */
    public function setClass($class)
    {
        $this->setAttrib('class', $class);
        /*
         $this->setDecorators(array(
          'FormElements',
          array('HtmlTag', array('tag' => 'ul')),
          array('Form', array('class' => $class))
        ));
        */
    }

    public function setRenderJson($bool=false) {
        if($bool === true || $bool == 1 || $bool == 'true') {
            $bool = true;
        } else {
            $bool = false;
        }
        $this->renderJson = $bool;
    }

    /**
     * Append a class to the existing classes (if any)
     *
     * @param string $class
     */
    public function addClass($class){
        $old_class = $this->getClass();
        $new_class = trim($old_class . ' ' . $class);
        $this->setClass($new_class);
    }

    /**
     * get the class assigned to this form
     *
     * @return string
     */
    public function getClass(){
        return $this->getAttrib('class');
    }

    /**
     * Enable local saving of form data
     *
     */
    public function enableLocalSave() {
    	$this->enableLocalSave = true;
    }

    /**
     * Disable local save
     *
     */
    public function disableLocalSave() {
    	$this->enableLocalSave = false;
    }

    /**
     *
     * enable auto saving of form
     */
    public function enableAutoSave() {
        $this->enableAutoSave = true;
    }


    /**
     *
     * disable auto save
     */
    public function disableAutoSave() {
        $this->enableAutoSave = false;
    }

    /**
     * Toggles on or off the csrf
     * @boolean $enable
     */
    public function toggleCsrf($enabled = null) {
    	/* If we didn't pass in $enabled, figure out what $enabled should be */
    	if(is_null($enabled)) {
    		$enabled = !$this->isCsrfEnabled();
    	}

    	if($enabled) {
    		$this->enableCsrf();
    	} else {
    		$this->disableCsrf();
    	}
    }

    /**
     * Enable csrf checking
     *
     */
    public function enableCsrf() {
    	$this->csrfEnabled = true;
    }

    /**
     * Disable csrf checking
     *
     */
    public function disableCsrf() {
    	$this->csrfEnabled = false;
    }

    /**
     * Returns if csrf is enabled
     * @return boolean
     */
    public function isCsrfEnabled() {
    	return $this->csrfEnabled;
    }

    /**
     * Set the classes on an element
     * @param string $name
     * @param string $elementClass
     * @param string $lineClass
     */
    public function setElementClass($name, $elementClass = '', $lineClass = '')
    {
        $element = $this->getElement($name);

        if ($elementClass != '') {
            $element->setAttrib('class', $elementClass);
        }
        if ($lineClass != '') {
            $element->setDecorators(array(
                'viewHelper',
                'Errors',
                'Label',
                array('HtmlTag', array('tag' => 'li', 'class' => $lineClass))
            ));
        }
    }

    /**
     * This will load a template script and add it to a form
     * @param string $script
     */
    public function addViewScript($script, $placement = 'prepend')
    {
    	if( $placement !== 'prepend' ) {
    		$placement = 'append';
    	}
        $this->setDecorators(array(
            'FormElements',
            array('viewScript', array('viewScript' => $script, 'placement' => $placement)),
            array('HtmlTag', array('tag' => 'ul')),
            'Form'
        ));
    }

    /**
     * This will add a new element which is a script
     * @param string $name
     * @param string $script
     * @param string $lineClass
     * @param int $order
     */
    public function addScript($script, $lineClass = '', $order = 0)
    {
        static $counter = 1;
        $counter++;
        $name = 'addScript_' . $counter;
        $this->addElement(
            'Text',
            $name,
            $order ? array('order' => $order) : array()
        );
        $this->getElement($name)->setDecorators(array(
            array('ViewScript', array('viewScript' => $script)),
            array('HtmlTag', array('tag' => 'li', 'class' => $lineClass))
        ));
    }



	/**
	 * Add a view script inbetween an element (prepend or append)
	 *
	 * @param string $elementName
	 * @param strring $script
	 * @param string $placement
	 * @param int $order
	 */
	public function insertViewScript( $elementName, $script, $placement = 'append', $order = null, $values = array() ) {

    	if( $order === null && isset($this->viewScripts[ $elementName ]) ) {
    		$order = count($this->viewScripts[ $elementName ]);
    	} else {
    		$order = 0;
    	}

    	$this->viewScripts[ $elementName ][$order] = array(
    			'script' => $script,
    			'placement' => $placement,
    			'values' => $values,
    		);
    }

    public function removeViewScript($scriptName) {
        if(count($this->viewScripts)) {
            foreach($this->viewScripts as $elementName => $elements) {
                if(count($elements)) {
                    foreach($elements as $orderPos => $order) {
                        if($order['script'] == $scriptName) {
                            unset($this->viewScripts[$elementName][$orderPos]);
                            if(count($this->viewScripts[$elementName])) {
                                unset($this->viewScripts[$elementName]);
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * Render form with view scripts between elements
     *
     * @param  Zend_View_Interface $view
     * @return string
     */
    public function render(Zend_View_Interface $view = null) {

        if($this->_errorsExist && $this->showErrorSummary)
        {
            $errors = $this->getAllErrors($this);
            if(!empty($errors))
            {
                foreach($this->getElements() as $element)
                {
                    $name = $element->getName();//get first element
                    break;
                }
                $this->insertViewScript($name,'partials/form-error-summary.phtml','prepend',null,array('errors'=>$errors));
            }
        }

        $this->populateOptions();
    	$append = '';

		if (null !== $view) {
			$this->setView($view);
		}
		// local save feature
        $view = $this->getView();
    	if ($this->enableLocalSave) {
            $view->HeadScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.form.js'));
    	    $view->HeadScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.json-2.2.min.js'));
    	    $view->HeadScript()->appendFile(noCacheFile('/js/lib/formSaver/formSaver-0.1.1.js'));
    	    $this->addClass('localSave');
    	}

    	if ($this->enableAutoSave) {
    	    $view->HeadScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.form.js'));
    	    $view->HeadScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.json-2.2.min.js'));
    	    $view->HeadScript()->appendFile(noCacheFile('/js/lib/formSaver/formSaver-0.1.1.js'));
    	    $this->addClass('autoSave');
    	}

        if($this->renderJson) {
            return $this->renderJson();
        }

    	// this isn't needed except to stop the htmlspecial chars from running again. -
    	$this->_view->setEscape('stripslashes');

        // there has to be a better way to loop on elements and render them
        $eles = $this->getElements();
        $eleText = '';

        $rendered = array();

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

        foreach ($this as $e)
        {

        	$tmpStart = '';
        	$tmpEnd = '';

        	// -- is an element --
        	if (!is_object($e)) {
        		$e = $this->getSubForm($e);
        		continue;
        	}
            // -- hasn't been rendered --
        	if (in_array( $e->getName(), $rendered)) {
        		continue;
        	}
        	$rendered[] = $e->getName();
        	 //echo "<hr>". get_class($e) . " => " . $e->getName() . "<BR>";

			// -- add render filters - used to be in addElement, but then filtered values are validated --
			$filterChain = new Zend_Filter();
			$filterChain->addFilter($specialCharsFilter);

			if( method_exists($e, 'getType') ) {
				$type = $e->getType();
                    if (strpos($type, 'Wysiwyg') !== false || strpos($type, 'MultiCheckbox') !== false || strpos($type, 'Radio') !== false) {
	                $e->addFilter($specialCharsFilter);
	            } else if (strpos($type, 'Textarea') !== false) {
	                $filterChain->addFilter($decodeFilter);
	                $filterChain->addFilter($encodeFilter);
	                $e->addFilter($filterChain);
	            } else {
	            	$filterChain->addFilter($decodeFilter);
	            	$filterChain->addFilter($encodeFilter);
	                $e->addFilter($filterChain);
	            }
			}

        	// -- render any prepend or append view scripts associated with the element --
        	if (isset($this->viewScripts[$e->getName()])) {
        		$scripts = $this->viewScripts[$e->getName()];
        		foreach ($scripts as $s) {
        			$viewData = array_merge( $this->getView()->getVars(), $s['values'] );
	        		$part = $view->partial($s['script'], $viewData);
	        		if ($s['placement'] == 'prepend') {
	        			$tmpStart .= $part;
	        		} else {
	        			$tmpEnd .= $part;
	        		}
        		}
        	}

        	$e->removeDecorator('DtDdWrapper');

        	$tmpMid = '';
        	foreach ($this->getDecorators() as $decoName => $decorator) {
        	    // echo "&nbsp; &nbsp; $decoName<BR>";
        		if (get_class($decorator) != 'Zend_Form_Decorator_FormElements') {
        			continue;
        		}
        		if ($e instanceof Zend_Form_SubForm) {
                    $tmpMid = $e->render();
                    $tmpMid = str_replace('&amp;', '&', $tmpMid);
        		} else {
            		$decorator->setElement($e);
            		$tmpMid = $decorator->render($e);
        		}
            	//echo "<textarea rows=4 cols=80>$tmpMid</textarea><br>";
        	}

        	// put together the view (before or after) this field
        	$tmp = $tmpStart . $tmpMid . $tmpEnd;
        	$eleText .= $tmp;

        }

        //echo $eleText; exit;

        // put the elements into <form>
        $content = '';
        foreach ($this->getDecorators() as $decoName => $decorator) {
        	//echo "$decoName <BR>";
        	//$decorator->clearOptions();
            $decorator->setElement($this);
            $content = $decorator->render($eleText);
        }

        $this->_setIsRendered();

        // put the fields into the form - hackety-hack, don't talk back
		if( strpos($content, '<li') !== false  && 0 && !$this instanceof DDM_BootstrapForm) {
	        $content = str_replace('</div></form>', '</ul></form>',$content );
	        $content = str_replace('"><div>', '"><ul>',$content );

	        if( strpos($content, '</ul></form>') === false ) {
	        	//echo "replaced"; exit;
	        	$content = str_replace('</form>', '</ul></form>', $content);
	        	$posLi = strpos($content, '<li');
	        	if( $posLi !== false ) {
	        		$first = substr($content, 0, $posLi);
	        		$first .= "<ul>";
	        		$last = substr($content, $posLi);
	        		$content = $first . $last;
	        	}
	        }
		}
        //$content = str_replace('</ul></form>', $eleText . '</ul></form>', $content);

        $content = $content . $append;
        $content = str_replace('&amp;', '&', $content);
        $this->_view->setEscape('htmlspecialchars');
        return $content . $append;

    }

    public function makeAjaxSubmit($functionName = '', $params = array(), $scriptName = ''){
        $this->getView()->headScript()->appendFile(noCacheFile('/js/ajaxForm.js', '', true));
        $this->addClass('ajax');
        if($scriptName){
            $this->getView()->headScript()->appendFile($scriptName);
        }
    }

    /**
     * Render the form a string of json data
     *
     * @return string
     */
    protected function renderJson() {

        $prefix = $this->fieldPrefix;
        if($prefix != '') {
            $prefix .= '_';
        }

        $json = array(
            'name' => get_class($this),
            'prefix' => $prefix,
        );

        // render errors
        if($this->_errorsExist) {
            $json['errors'] = array();
            foreach ($this as $e) {
                $error = array(
                    'id' => $e->getId(),
                    'messages' => $e->getMessages()
                );
            }
            return json_encode($json);
        }

        // render elements
        foreach ($this as $e) {

            $type = get_class($e);
            if(strpos($type, 'Button') !== false) {
                continue;
            }
            if(strpos($type, 'Submit') !== false) {
                continue;
            }

            $el = array(
                'type' => $type,
                'id' => $e->getId(),
                'name' => $e->getName(),
                'value' => $e->getValue(),
                'label' => $e->getLabel(),
                'required' => (int) $e->isRequired()
            );

            if(method_exists($e, 'getMultiOptions')) {
                $opts = $e->getMultiOptions();
                $el['options'] = $opts;
            }

            if(method_exists($e, 'getOptions')) {
                $opts = $e->getOptions();
                $el['options'] = $opts;
            }

            //ppr($el);
            $json['elements'][] = $el;
            //ppr($e); exit;
         }

         //ppr($json); exit;
         return json_encode($json);
    }

    /**
     * Sort items according to their order
     *
     * @return void
     */
    protected function _sort()
    {
        if($this->_orderUpdated) {
            $items = array();
            $index = 0;
            foreach($this->_order as $key => $order) {
                if(null === ($order = $this->{$key}->getOrder())) {
                    while(array_search($index, $this->_order, true)) {
                        ++ $index;
                    }
                    $items[$index][] = $key;
                    ++ $index;
                } else {
                    $items[$order][] = $key;
                }
            }
            ksort($items);
            $index = 0;
            foreach($items as $i => $item) {
                foreach($item as $subItem) {
                    $newItems[$index ++] = $subItem;
                }
            }
            $items = array_flip($newItems);
            asort($items);
            $this->_order = $items;
            $this->_orderUpdated = false;
        }
    }

    /**
     * Persist the value of request parameters in the session
     * @param Zend_Request $request
     * @param array $params
     * @param string $storage
     */
    public function persistRequestParams($request, $params, $storage = null)
    {
        if (is_array($params)) {
            foreach ($params as $param) {
                $this->persistRequestParam($request, $param, $storage);
            }
        }
    }

    /**
     * Persist the value of a request parameter in the session
     * @param Zend_Request $request
     * @param array $params
     * @param string $storage
     */
    public function persistRequestParam($request, $param, $key = '', $storage = null)
    {
        if ($storage == null) {
            $storage = $this->getName();
        }
	    $params = $request->getParams();
        $keyToUse = $key ? $key : $param;
        $fullKey = 'Search_' . $storage . '_' . $keyToUse;

        // get the stored value and element
        if(!empty($_COOKIE[$fullKey])) {
            $value = @unserialize($_COOKIE[$fullKey]);
            if($value === false)
            {
                $value = $_COOKIE[$fullKey];
            }
            $el = $this->getElement($param);
            // if the element has a defined list of options and the value in the cookie isn't in the list, nuke it.
            if(!is_array($value) && is_object($el) && method_exists($el, 'getMultiOptions') && !array_key_exists($value, $el->getMultiOptions())) {
                unset($_COOKIE[$fullKey]);
            }
            else if(is_array($value) && is_object($el) && method_exists($el, 'getMultiOptions')) {
                $options = $el->getMultiOptions();
                foreach($value as $i=>$val)
                {
                    if(!array_key_exists($val, $options))
                    {
                        unset($value[$i]);
                    }
                }
                $_COOKIE[$fullKey] = serialize($value);
            }
        }
        if (array_key_exists($param, $params)) {

            if(is_array($params[$param]))
            {
                setcookie($fullKey, serialize($params[$param]));
            }
            else
            {
                setcookie($fullKey, $params[$param]);
            }
        } else if( array_key_exists($fullKey, $_COOKIE)){
            $value = @unserialize($_COOKIE[$fullKey]);
            if($value === false)
            {
                $value = $_COOKIE[$fullKey];
            }
            $request->setParam($param, $value);

        }
    }

    /**
     * Get a token for csrf use
     *
     * @return string
     */
    protected function makeCsrfToken() {
    	$token = md5(rand(1,100000). $this->getName());
    	$_SESSION['form_token_' . $this->getName() ] = $token;
    	return $token;
    }

    /**
     * Get/make a token for this form
     *
     * @return unknown
     */
    protected function getCsrfToken() {
    	if( isset( $_SESSION['form_token_' . $this->getName() ] ) ) {
    		return $_SESSION['form_token_' . $this->getName() ];
    	} else {
    		return $this->makeCsrfToken();
    	}
    }

    /**
     * Populate Element Multi-Options
     *
     * NOTE: set element attrib['populateOptions'] = 'methodName' to
     * override the default derived from the element name. To disable
     * the populateOptions altogether, set attrib['populateOptions'] = false
     *
     */
    public function populateOptions()
    {
       foreach ($this->getElements() as $element) {
            if ((is_subclass_of($element, 'Zend_Form_Element_Multi'))
                && (!$element->getMultiOptions())) {

                $method = $element->getAttrib('populateOptions');
                if ($method === null) {
                    $method = ucwords(preg_replace(
                        array('/' . $this->fieldPrefix . '/', '/_/'),
                        array('', ' '),
                        $element->getName()
                    ));
                    $method = 'populate' . str_replace(' ', '', $method);
                } else if ($method === false) {
                    continue;
                }
                if (method_exists($this, $method)) {
                    $this->$method();
                } else {
                    throw new Exception("No method ($method) of populating field: ".$element->fieldName);
                }
            }
        }
    }

    /**
     * Populates the request object with all form values that haven't already been set in the request object
     * This is helpful when you need to know if a form will process using it's current stored values
     * without actually loading the form in the traitional manner
     * @param Zend_Controller_Request_Http $request
     * @param array $elementsNotToUse
     */
    public function populateUnSetRequestParamsWithCurrentFormValues($request,$elementsNotToUse=array())
    {
        $currentParams = $request->getParams();
        foreach($this->getElements() as $element)
        {
            if(!isset($currentParams[$element->getName()]) && !in_array($element->getName(), $elementsNotToUse))
            {
                $request->setParam($element->getName(), $element->getValue());
            }
        }
        foreach($this->getSubForms() as $subForm)
        {
            if(!isset($currentParams[$subForm->getName()]) && !in_array($element->getName(), $elementsNotToUse))
            {
                $param = array();
                $param[$subForm->getName()] = array();
                foreach($subForm->getElements() as $element)
                {
                    $param[$subForm->getName()][$element->getName()] = $element->getValue();
                }
                $request->setParam($subForm->getName(), $param[$subForm->getName()]);
            }
        }
    }
}
