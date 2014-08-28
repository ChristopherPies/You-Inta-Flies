<?php
class DDM_View_Helper_Menu extends Zend_View_Helper_Abstract
{
    public function menu($name, $level = 1, $class = '', $minimum = 0)
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');
        $tree = $bootstrap->getResource('tree');
        
        return $tree->render($name, $level, $class, $minimum);
    }
}
