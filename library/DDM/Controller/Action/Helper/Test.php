<?php
/**
 * Test Action Controller Helper Class (Singleton Pattern)
 * Invoke this in an action controller via one the following methods:
 *
 * Method #1 : direct()
 * $this->_helper->test(array('test'));
 * 
 * Method #2 : __get()
 * $test = $this->helper->test;
 * $test->helloworld(array('test'));
 *
 * Method #3 : getHelper()
 * $test = $this->helper->getHelper('test');
 * $test->helloworld(array('test'));
 *
 */
class DDM_Controller_Action_Helper_Test extends Zend_Controller_Action_Helper_Abstract 
{
    public $pluginLoader;

    public function __construct()
    {
        $this->pluginLoader = new Zend_Loader_PluginLoader();
    }  
    public function init()
    {
        /** initialize plugin here **/
        echo "Test init()<BR />";
    }
    public function preDispatch()
    {
        /** pre-dispatch code here **/
        echo "Test preDispatch()<BR />";
    }
    public function postDispatch()
    {
        /** post-dispatch code here **/
        echo "TEST postDispatch()<BR />";
    }
    public function direct($data)
    {
        echo "TEST direct()<BR />";
        return $this->helloworld($data);
    }
    public function helloworld($data)
    {
        /** execute code here **/
        echo "TEST helloworld()<BR />";
    }
}
