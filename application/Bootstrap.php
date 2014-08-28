<?php

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{

    public function __construct($a)
    {
        parent::__construct($a);
    }

    protected function _initForceSession() {
        Zend_Session::start();
    }

    protected function _initView()
    {
        $options = $this->getOptions();
        if (isset($options['resources']['view']))
        {
            $view = new Zend_View($options['resources']['view']);
        }
        else
        {
            $view = new Zend_View;
        }

        if (isset($options['resources']['view']['doctype']))
        {
            $view->doctype($options['resources']['view']['doctype']);
        }

        if (isset($options['resources']['view']['contentType']))
        {
            $view->headMeta()->appendHttpEquiv('Content-Type', $options['resources']['view']['contentType']);
        }
        
        //$view->addHelperPath("SD/View/Helper", "SD_View_Helper");
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('ViewRenderer');
        $viewRenderer->setView($view);
        return $view;
    }

    protected function _initModifiedFrontController()
    {
        $options = $this->getOptions();
        if (!isset($options['resources']['modifiedFrontController']['contentType']))
        {
            return;
        }

        $this->bootstrap('FrontController');
        $front = $this->getResource('FrontController');
        //to add a module controller path:
//        $front->addControllerDirectory(APPLICATION_PATH . '/modules/test/controllers','test');
        $response = new Zend_Controller_Response_Http;
        $response->setHeader('Content-Type', $options['resources']['modifiedFrontController']['contentType'], true);
        $front->setResponse($response);
    }
    
    /**
     * Add databases to the registry
     *
     * @return void
     */
    public function _initDbRegistry()
    {
        $this->bootstrap('multidb');
        $multidb = $this->getPluginResource('multidb');
        Zend_Registry::set('master', $multidb->getDb('master'));
    }

    protected function _initDoctype() {
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype('XHTML1_STRICT');
    }

//    protected function _initConfig() {
//
//        $config = new Zend_Config($this->getOptions(), true);
//        Zend_Registry::set('config', $config);
//        return $config;
//
//    }

//    public function _initMailInfo()
//    {
//    	$options = $this->getOptions();
//    	Zend_Registry::set('mail',$options['resources']['mail']);
//    }
}

?>