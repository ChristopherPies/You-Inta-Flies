<?php

class DDM_Navigation_Render_Main implements DDM_Interface_Render 
{
    public function render($items) 
    {
        $front = Zend_Controller_Front::getInstance();
        $controller = $front->getRequest()->getControllerName();
		$output = '';
		if (count($items) > 0) {
			$output .= "<ul>";
            foreach( $items as $item ) {
	            $output .= "<li" 
	                . (($controller == $item['controller']) ? ' class="selected" ' : '') 
	                . "><a href=\"/{$item['controller']}/{$item['action']}\">{$item['label']}</a></li>\n";
	        }
            $output .= "</ul>";	        
		}
        return $output;
	}
}
