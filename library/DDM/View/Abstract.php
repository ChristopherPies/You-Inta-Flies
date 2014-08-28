<?php
/**
 *
 * @author hhatfield
 *
 */
abstract class DDM_View_Abstract extends Zend_View_Abstract
{

    protected $_isSubAction = false;

    protected $_subActionKey = '';

    /**
     * Processes a view script and returns the output.
     *
     * @param string $name The script name to process.
     * @return string The script output.
     */
    public function render($name)
    {

        // this logic helps us render .phtml and .tpl with the smarty view helper
        $script = null;
        $isSmarty = false;
        if ($this instanceof DDM_View_Smarty) {
            $isPhtml = false;
            $isSmarty = true;
            $scriptExists = false;
            $script = $this->getScriptPath($name);
            if($script != '' && file_exists($script)) {
                $scriptExists = true;
                if(strpos($script, '.phtml')){
                    $isPhtml = true;
                }
            }
            if(!$scriptExists) {
                $name = str_replace('.tpl', '.phtml', $name);
            }
        }
        
        // this block exists so projects using smarty can start using .phtml (php templates) while still rendering smarty
        if($isSmarty && (!$scriptExists || $isPhtml)) {

            $vars = $this->getVars();
            $dirs = $this->getAllPaths();

            $view = new Zend_View();
            if(isset($dirs['helper']) && is_array($dirs['helper'])) {
                foreach($dirs['helper'] as $type => $paths) {
                    $type = substr($type, 0, -1);
                    foreach($paths as $p) {
                        $view->addHelperPath($p, $type);
                    }
                }
            }

            if(isset($dirs['script']) && is_array($dirs['script'])) {
                foreach($dirs['script'] as $path) {
                    $view->addScriptPath($path);
                }
            }

            if(isset($dirs['filter']) && is_array($dirs['filter'])) {
                foreach($dirs['filter'] as $type => $paths) {
                    $type = substr($type, 0, -1);
                    foreach($paths as $p) {
                        $view->setFilterPath($p, $type);
                    }
                }
            }

            if(count($vars)) {
                foreach($vars as $key => $var) {
                    $view->$key = $var;
                }
            }

            $viewRenderer = new Zend_Controller_Action_Helper_ViewRenderer();
            $viewRenderer->setView($view);

            $output = $view->render($name);

        } else {
            // normal render, not the smarty/php mess above.
            $output = parent::render($name);
        }

        if($this->_isSubAction){
            $this->placeholder($this->_subActionKey)->set($output);
            $this->_isSubAction = false;
            $this->_subActionKey = '';
        }
        return $output; // filter output
    }

    /**
     *
     * @param boolean $val
     */
    public function setSubAction( $controller, $action ){
        $this->_isSubAction = true;
        $this->_subActionKey = strtolower($controller) . '_' . $action;
    }

    public function getSubAction(){
        return $this->_subActionKey;
    }
}