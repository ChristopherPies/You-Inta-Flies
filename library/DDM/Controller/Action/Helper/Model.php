<?php
/**
 * Model Action Controller Helper Class (Singleton Pattern)
 * 
 * Use this a convenience method to load a model
 *
 * $this->_helper->model($name);
 * - $name is optional. 
 * - If $name is omitted, the controller name will be used as the model name.
 * 
 */
class DDM_Controller_Action_Helper_Model extends Zend_Controller_Action_Helper_Abstract 
{
    public $pluginLoader;

    public function __construct()
    {
        $this->pluginLoader = new Zend_Loader_PluginLoader();
    }  
    public function init()
    {
    }
    public function preDispatch()
    {
    }
    public function postDispatch()
    {
    }
    public function direct($name = '')
    { 
        if ($name == '') {
            $this->request = $this->getRequest();
            $this->controllerName = $this->request->getControllerName();
            $name = ucfirst(strtolower(str_replace('Controller', '', $this->controllerName)));
        }
        $name = ucfirst(strtolower($name));

        $modelName = "Models_DS_{$name}";
        $model = new $modelName();

        return $model;
    }
}
