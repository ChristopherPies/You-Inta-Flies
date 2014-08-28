<?php
/**
 * This helper allows you to set the page title from any controller.
 * It will set the title in both the head of the page as well as
 * in an <h2> tag within the body of the page.
 */
class DDM_Controller_Action_Helper_Page extends Zend_Controller_Action_Helper_Abstract
{
    private $title;
    private $subTitle;
    private $pageTitle;
    public $pluginLoader;

    public function __construct()
    {
        $this->title = '';
        $this->subTitle = '';
        $this->pluginLoader = new Zend_Loader_PluginLoader();
    }
    public function init()
    {
        /** initialize plugin here **/
    }
    public function preDispatch()
    {
        /** pre-dispatch code here **/
    }
    public function postDispatch()
    {
        $front = Zend_Controller_Front::getInstance();
        $bootstrap = $front->getParam('bootstrap');

        /** set the page title in the document headTitle **/
        $view = $bootstrap->getResource('View');
        if(!$this->pageTitle){
            $this->pageTitle = $this->title;
        }
        $view->headTitle()->append($this->pageTitle);

        /** set the page title in a layout variable **/
        $layout = $bootstrap->getResource('Layout');
        $layout_view = $layout->getView();
        $layout_view->title = $this->title;
        $layout_view->subTitle = $this->subTitle;
    }
    public function direct()
    {
        return $this;
    }
    public function setTitle($title)
    {
        $this->title = $title;
    }
    public function setPageTitle($title){
        $this->pageTitle = $title;
    }
    public function setSubTitle($subTitle)
    {
        $this->subTitle = $subTitle;
    }
}
