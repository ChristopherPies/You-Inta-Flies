<?php
/**
 *
 * @author hhatfield
 *
 */
interface DDM_View_Interface extends Zend_View_Interface
{
    /**
     * Set the controller and action so we can stash the result
     *
     * @param $controller
     * @param $action
     */
    public function setSubAction($controller, $action);
}
?>