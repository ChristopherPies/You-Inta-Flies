<?php
/**
 * DDM_Writer
 *
 */
class DDM_Writer {
	/**
	 * Smarty class
	 * @var Smarty
	 */
	protected $_smarty;

	/**
	 * request
	 * @var Zend_Controller_Action
	 */
	protected $_request;
	/**
	 * Template path
	 * @var string
	 */
	protected $_template;

	/**
	 * Consrtuctor
	 * @param Zend_Request_Interface $request Zend_Controller_Action
	 */
	public function __construct($request=null) {
        $this->_request = $request;
        $this->init();
	}

	/**
	 * init
	 */
	private function init() {
		require_once 'Smarty/Smarty.class.php';
		$this->_smarty = new Smarty();
        $this->_smarty->force_compile = true;
        $this->_smarty->compile_dir = '/tmp';
        $this->_smarty->template_dir = APPLICATION_PATH . '/views/scripts/';
	}

	/**
	 * Set template name and path
	 * @param string $name
	 * @param string $path
	 * @param string $ext
	 * @return DDM_Writer
	 */
	public function setTemplate ($name, $path = null, $ext = 'tpl') {
		if( is_null($path) && $this->_request !== null ) {
        	$this->_template = $this->_request->getControllerName();
		} elseif( !is_null($path) ) {
			$this->_template = $path;
		}
        $this->_template .= '/' . $name . '.' . $ext;
        return $this;
	}

	/**
	 * Assign variable to template
	 * @param string $name
	 * @param mixed $value
	 * @return DDM_Writer
	 */
	public function assign($name, $value) {
        $this->_smarty->assign($name, $value);
        return $this;
	}


	/**
	 * Fetch compiled text
	 * @return string
	 */
	public function fetch() {
		return (string)$this->_smarty->fetch($this->_template);
	}

	/**
	 * Remove data assigned to writer
	 * @return DDM_Writer
	 */
	public function reset() {
		$this->_smarty = null;
		$this->init();
		return $this;
	}
}
