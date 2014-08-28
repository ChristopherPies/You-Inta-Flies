<?php

require_once(PROJECT_ROOT . '/library/functions/php/functions.php');
/**
 * DDM_Controller_Action
 *
 */
class DDM_Controller_Action extends Zend_Controller_Action
{
    /**
     * Commonly used user data
     * - ['id'] = int
     * - ['roles'] = array()
     * - ['request'] = array()
     * - ['preferredFirstName'] = array()
     * - ['lastName'] = array()
     * * @var array
     */
    protected $userId = null;
    protected $userRoles = null;
    protected $requestParams = null;
    protected $preferredFirstName = null;
    protected $lastName = null;

    /**
     *
     * @var DDM_View_Interface
     */
    public $view;

    /**
     * Writer instance
     * @var DDM_Writer
     */
    //public $writer;

    /**
     * Constuctor
     * @param Zend_Controller_Request_Abstract $request
     * @param Zend_Controller_Response_Abstract $response
     * @param array $invokeArgs
     */
    public function __construct(Zend_Controller_Request_Abstract $request,
                                Zend_Controller_Response_Abstract $response,
                                array $invokeArgs = array())
    {
        parent::__construct($request, $response, $invokeArgs);

        // ============================
        // -- Fix request parameters --
        // ============================
        // PHP doesn't know how to parse querystrings right. See `parse_qs()` doc in DDM/Functions.php for details

        // The request object contains the variables internally - we want to force it to pull from $_GET/$_POST/$_REQUEST again next time
        foreach($_REQUEST as $k => $v) {
            $this->_request->setParam($k, null);
        }

        // Remove current $_GET vars from $_REQUEST
        $_REQUEST = array_diff_key($_REQUEST, $_GET, $_POST);
        $_GET = parse_qs(urldecode($_SERVER['QUERY_STRING']));
        // Zend forms don't like this - they depend on PHP's way off overriding instead of converting keys with the same name to an array
        //if(stripos($request->getHeader('Content-Type'), 'urlencoded') !== false) {
        //    $_POST = parse_qs(urldecode(file_get_contents('php://input')));
        //}

        // We want to respect the PHP ini setting for precedence as we merge things again for $_REQEUST
        $request_order = ini_get('request_order');
        for($i = 0; $i < strlen($request_order); $i++) {
            $char = $request_order[$i];
            if($char == 'G') {
                $_REQUEST = array_merge($_REQUEST, $_GET);
            } elseif($char == 'P') {
                $_REQUEST = array_merge($_REQUEST, $_POST);
            }
        }

        // =============
        // -- End fix --
        // =============


        //$this->view->setEscape('stripslashes');
    }

    /**
     * Redirect to HTTPS
     *
     * @return unknown
     */
    public function goHttps() {

        $bootstrap = $this->getFrontController()->getParam('bootstrap');
        $options = $bootstrap->getOptions();
        $ssl = $options['ssl'];
        $transport = getCurrentTransport();
        $headers = getallheaders();
        if($transport == 'http' && (empty($headers['X-lb']) || empty($headers['X-SSL-cipher'])) && !empty($ssl['enabled'])) {
            $qs = $_SERVER['QUERY_STRING'];
            $url = 'https://' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ($qs ? ('?' . $qs) : '');
            return $this->_redirect($url);
        }
    }

    /**
     * Redirect to HTTP
     *
     * @return unknown
     */
    public function noHttps() {
        $transport = getCurrentTransport();
        if($transport == 'https') {
            $qs = $_SERVER['QUERY_STRING'];
            $url = 'http://' .$_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . ($qs ? ('?' . $qs) : '');
            return $this->_redirect($url);
        }
    }

    /**
     * Override preDispatch to add the ability to disable or change the layout of any action
     *
     */
    public function preDispatch()
    {
        $request = $this->getRequest();

        // disable the layout
        $disableLayout = $request->getParam('disableLayout');
        if($disableLayout !== null && $disableLayout !== '0') {
            $this->_helper->layout()->disableLayout();
        }

        // override the layout
        if(isset($_SERVER['APPLICATION_LAYOUT'])) {
            $overrideLayout = $_SERVER['APPLICATION_LAYOUT'];
        } else {
            $overrideLayout = $request->getParam('overrideLayout');
        }
        if($overrideLayout !== null) {
            $this->view->layout()->setLayout( $overrideLayout );
        }

        // Handle JSON encoded POST and PUT vars with the application/json content type
        if(($request->isPost() || $request->isPut()) && (
                (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false) ||
                (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') )) {

            // Try parsing the POST data as JSON and then as a regular querystring if that fails
            $rawdata = file_get_contents('php://input');
            $params = json_decode($rawdata, true);
            if($rawdata && !$params) {
                $params = array();
                parse_str($rawdata, $params);
            }

            if(is_array($params) && count($params)) {
                $request->setParam('_jsonData', $params);
                foreach($params as $key => $val) {
                  $request->setParam($key, $val);
                }
            }
        }
    }

    /**
     * Get the user Id from the session
     *
     * @return int
     */
    public function getUserId()
    {
        $zendAuth = new Zend_Session_Namespace('Zend_Auth');
        if ($this->userId == null && is_object($zendAuth->storage) && property_exists($zendAuth->storage, 'id')) {
            $this->userId = $zendAuth->storage->id;
        }
        return $this->userId;
    }

    public function getUserPreferredFirstName()
    {
        if ($this->preferredFirstName == null) {
            $zendAuth = new Zend_Session_Namespace('Zend_Auth');
            $this->preferredFirstName = $zendAuth->storage->preferred_first_name;
        }
        return $this->preferredFirstName;
    }

    public function getUserLastName()
    {
        if ($this->lastName == null) {
            $zendAuth = new Zend_Session_Namespace('Zend_Auth');
            $this->lastName = $zendAuth->storage->last_name;
        }
        return $this->lastName;
    }
    /**
     * Get the user roles from the session
     *
     * @return array
     */
    public function getUserRoles()
    {
        if ($this->userRoles == null) {
            $user = new Zend_Session_Namespace('User');
            if( !empty($user->storage->roles) ) {
            	$this->userRoles = $user->storage->roles;
            }
        }
        return $this->userRoles;
    }

    /**
     * Call the json action helper, output the json array, and exit.
     *
     * This is needed for the list helper to call the json helper. This is because
     * ->_helper() is private and can't be invoked outside the controller_action class.
     *
     * @param $data
     */
    public function json($data)
    {
        $this->_helper->json($data);
    }

	/**
     * Get the viewRenderer helper
     *
     * This is needed for the list helper to turn off the rendering of views
     *
     * @param view_renderer
     * @return Zend_Controller_Action_Helper_ViewRenderer
     */
    public function getViewRenderer()
    {
		if( is_object($this->_helper) ) {
			return $this->_helper->viewRenderer;
		} else {
			return null;
		}

    }

    /**
     * Run and trap the results of an action
     *
     * @param string $controller
     * @param string $action
     * @return array
     */
    public function callSubAction($controller, $action)
    {
    	$return = array();

        $request = $this->getRequest();
        $response = $this->getResponse();
		$redirector = $this->_helper->redirector;

		/* Tell the redirector not to exit after redirect is called */
		$redirector->setExit(false);

		$render = $this->getViewRenderer()->getNoRender();
		/* what controller/action is THIS action? */
		$originalAction = $request->getActionName();
		$originalController = $request->getControllerName();

		$oldSubAction = $this->view->getSubAction();
		$this->view->setSubAction($controller, $action);
		/* setup and run the target action */
		$contName = ucwords($controller) . 'Controller';
		$methodName = $action . 'Action';
		require_once( APPLICATION_PATH . '/controllers/'. $contName .'.php');

    	$cont = new $contName( $this->getRequest(), $this->getResponse(), $this->getInvokeArgs() );
    	if( !is_object( $cont ) || !method_exists($cont,$methodName) ) {
    		return $return;
    	}

    	$cont->$methodName(); // run it

    	if( $response->getBody() == null ) {
			$cont->render( $controller . '/'. $action, $controller . '_' . $action, true);
    	}
    	/* target action is done */

        $response->clearBody();
    	/* re-enable exit after redirect */
    	$this->getViewRenderer()->setNoRender($render);
    	if($oldSubAction){
    	    list($oldController, $oldAction) = explode('_',$oldSubAction,2);
    	    $this->view->setSubAction($oldController, $oldAction);
    	}
		$redirector->setExit(true);
    }

    /*
     * Get a Zend Resource by name (convenience function)
     *
     * @param string $name
     * @return resource
     */
    public function getResource($name)
    {
        $front = $this->getFrontController();
        $bootstrap = $front->getParam('bootstrap');
        $resource = $bootstrap->getResource($name);

        return $resource;
    }

    /**
     * Set headers to allow this page to be cached for N seconds
     * @param int $seconds
     * @return DDM_Controller_Action
     */
    public function setTtl($seconds) {

        $ts = gmdate("D, d M Y H:i:s", time() + $seconds) . " GMT";
        $this->getResponse()
        ->setHeader("Expires", "$ts", true)
        ->setHeader("Cache-Control", "max-age=public, $seconds", true)
        ->setHeader("Cache-Control", "s-maxage=$seconds", false)
        ->setHeader("Pragma", "cache", true);

        return $this;
    }

}
