<?php

class DDM_Navigation_Render_Side implements DDM_Interface_Render 
{
    public function render( $items ) 
    {
        $front = Zend_Controller_Front::getInstance();
        $controller = $front->getRequest()->getControllerName();
        $action = $front->getRequest()->getActionName();
        $output = '';
		foreach( $items as $item) {
			if ($controller == $item['controller'] 
			    && isset($item['children']) 
			    && is_array($item['children']) 
			    && count($item['children']) > 0) {
			    	
				$output .= "\n<ul class=\"sub-navigation\">\n";
				foreach ($item['children'] as $i) {
				    $output .= "\t<li" 
                    . (($action == $i['action']) ? ' class="selected" ' : '') 
                    . "><a href=\"/{$i['controller']}/{$i['action']}\">{$i['label']}</a></li>\n";	
				}
                $output .= "</ul>\n";
			}
        }
        return $output;
	}
}
