<?php

// http://pksoftware.de/software-blog/articles/using-twitter-bootstrap-together-with-zend-forms
class DDM_Form_Decorator_Bootstrap extends Zend_Form_Decorator_Abstract {
	
	public function buildLabel() {
		$element = $this->getElement ();
		$label = $element->getLabel ();
		if ($translator = $element->getTranslator ()) {
			$label = $translator->translate ( $label );
		}
		if ($element->isRequired () && !empty($label)) {
                    
			$label .= '*';
		}
		
		return $element->getView ()->formLabel ( $element->getName (), $label, array (
				'class' => 'control-label' 
		) );
	}
	
	public function buildInput() {
		$element = $this->getElement ();
		$helper = $element->helper;
					
		if ($element instanceof ZendX_JQuery_Form_Element_UiWidget) {
			$html = $element->getView ()->$helper ( $element->getName (), $element->getValue (), $element->getJQueryParams (), $element->getAttribs () );
		} else {
			$html = $element->getView ()->$helper ( $element->getName (), $element->getValue (), $element->getAttribs (), $element->options );
		}
		
		return $html;
	}
	
	public function buildErrors() {
		$element = $this->getElement ();
		$messages = $element->getMessages ();
		if (empty ( $messages )) {
			return '';
		}
		return '<div class="help-block">' . $element->getView ()->formErrors ( $messages ) . '</div>';
	}
	
	public function buildDescription() {
		$element = $this->getElement ();
		$desc = $element->getDescription ();
		if (empty ( $desc )) {
			return '';
		}
		return '<div class="help-block">' . $desc . '</div>';
	}
	
	public function render($content) {
		$element = $this->getElement ();
		
		if (! $element instanceof Zend_Form_Element) {
			return $content;
		}
		if (null === $element->getView ()) {
			return $content;
		}
		
		$separator = $this->getSeparator ();
		$placement = $this->getPlacement ();
		$label = $this->buildLabel ();
		$input = $this->buildInput ();
		$errors = $this->buildErrors ();
		$desc = $this->buildDescription ();
		
		$messages = $element->getMessages ();
		
		$elementClasses = 'control-group';
		if (! empty ( $messages )) {
			$elementClasses .= ' error';
		}
		return '<div class="' . $elementClasses . '">' . $label . '<div class="controls">' . $input . $desc . $errors . '</div></div>';
	}
}
?>