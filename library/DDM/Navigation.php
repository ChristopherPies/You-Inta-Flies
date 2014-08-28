<?php
/**
 * DDM_Navigation
 *
 */
class DDM_Navigation 
{
    /**
     * Acl object
     * @var Zend_Acl
     */
    protected $acl;
    /**
     * Render class
     * @var Render
     */
    protected $renderer;
    /**
     * User roles
     * @var array
     */
    protected $roles;
    /**
     * Array of pages to link to
     * @var array
     */
    protected $pages;
    /**
     * Filtered nav items
     * @var array
     */
    protected $items;
    /**
     * User data from session
     * @var Zend_Session_Namespace
     */
    protected $userInfo;
    /**
     * User email address
     * @var string
     */
    public $userEmail;

    /**
     * Enter description here ...
     * @param Zend_Acl $acl
     * @param unknown_type $pages
     */
    public function __construct(Zend_Acl $acl, $pages, DDM_Interface_Render $renderer) 
    {
        $this->userInfo = new Zend_Session_Namespace('Zend_Auth');
        $this->acl = $acl;
        $this->renderer = $renderer;
        if( isset($this->userInfo->storage->roles) && count($this->userInfo->storage->roles) ) {
        	$this->roles = $this->userInfo->storage->roles;
        } else {
        	$this->roles = array('Guest');
        }
        $this->email = isset($this->userInfo->storage->email) ? $this->userInfo->storage->email : null;
        $this->pages = $pages;
    	$this->items = array();

    }

    /**
     * Fetch user nav items
     * @return array
     */
    public function fetch() 
    {
        // The front controller singelton must be called at display time
        // otherwise the controller and action are unknown.
        //
        $front = Zend_Controller_Front::getInstance();
        $controller = $front->getRequest()->getControllerName();
        $action = $front->getRequest()->getActionName();

    	foreach ($this->pages as $page) {
            foreach($this->roles as $role) {
            	if ($this->acl->isAllowed($role, $page['controller'], $page['action'])) {
	        		if ($page['controller'] == $controller && $page['action'] == $action) {
	        		     $page['selected'] = true;
	        		}
	        		if (isset($page['children']) && is_array($page['children'])) {
	        			foreach ($page['children'] as $k => $p) {
	        			    foreach($this->roles as $r) {
	        			        if ($this->acl->isAllowed($role, $p['controller'], $p['action'])) {
				                    if ($p['controller'] == $controller && $p['action'] == $action) {
				                         $p[$k]['selected'] = true;
				                    }
	        			        } else {
	        			        	unset($p[$k]);
	        			        }
	        			    }
	        			}
	        		}
            		$this->items[] = $page;
	        		break;
	        	}
            }
        }
        return (array)$this->items;
    }

    /**
     * Return output
     * @return string
     */
    public function render() 
    {
        return (string)$this->renderer->render($this->fetch());
    }
}
