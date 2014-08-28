<?php
class DDM_Tree
{
    private $_nodes;
    private $_acl;
    private $_roles;
    private $_controller;
    private $_action;
    private $_email;

    public function __construct(Zend_Acl $acl)
    {
        $this->_nodes = array();
        $this->_acl = $acl;

        $userInfo = new Zend_Session_Namespace('Zend_Auth');

        if (isset($userInfo->storage->roles) && count($userInfo->storage->roles)) {
            $this->_roles = $userInfo->storage->roles;
        } else {
            $this->_roles = array('Guest');
        }
        $this->_controller = '';
        $this->_action = '';

        $this->userInfo = new Zend_Session_Namespace('Zend_Auth');
        $this->_email = isset($this->userInfo->storage->email) ? $this->userInfo->storage->email : '';
    }

    public function node($id, $parent, $title, $controller = '', $action = '', $validate = 'access')
    {
        if (!isset($this->_nodes[$id])) {
            $this->_nodes[$id] = array();
        }
        if ($title == ':email') {
            $title = $this->_email;
        }
        $this->_nodes[$id]['title'] = $title;
        $this->_nodes[$id]['controller'] = $controller;
        $this->_nodes[$id]['action'] = $action;
        $this->_nodes[$id]['parent'] = $parent;
        $this->_nodes[$id]['validate'] = $validate;
        $this->_nodes[$id]['access'] = $this->isAllowed($controller, $action);
        $this->_nodes[$id]['selected'] = false;

        if (!isset($this->_nodes[$parent])) {
            $this->_nodes[$parent] = array();
        }
        $this->_nodes[$parent]['children'][] = $id;

        return $this;
    }
    public function isAllowed($controller, $action)
    {
        if ($controller && $action) {
            foreach ($this->_roles as $role) {
                if ($this->_acl->isAllowed($role, $controller, $action)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function render($name, $level = 1, $class = '', $minimum = 0)
    {
        $buttons = false;
        $counter = 0;
        $out = '';
        $div_id = '';
        $front = Zend_Controller_Front::getInstance();
        $controller = $front->getRequest()->getControllerName();
        $action = $front->getRequest()->getActionName();

        if (substr($class, 0, 1) == '#') {
            $div_id = ' id="' . substr($class, 1) . '"';
            $class = '';
        }

        foreach ($this->_nodes as $id => &$node) {
            if (isset($node['controller']) && ($node['controller'] == $controller) && ($node['action'] == $action)) {
                $this->setAllSelected($id);
            }
        }
        $node_id = $this->findName($name);
        $children =  $this->findLevel($node_id, $level);

        $total = count($children);
        if( $total ) {
	        foreach ($children as $child) {
	            $itemClass = array();
	            $n = $this->_nodes[$child];

	            if (!$this->isDisplay($n)) {
	                continue;
	            }
	            if (!$counter++) {
	                $itemClass[] = 'first';
	            }
	            if ($counter == $total) {
	                $itemClass[] = 'last';
	            }
	            if ($n['selected']) {
	                $itemClass[] = 'selected';
	            }
	            $link = '/' . $n['controller'] . '/' . $n['action'];
	            if (count($itemClass)) {
	                $out .= "<li class=\"" . join(' ', $itemClass) . "\">";
	            } else {
	                $out .= "<li>";
	            }
	            if (is_array($n['title'])) {
	                $title = '<h2>' . $n['title'][0] . '</h2>';
                    $title .= '<p>' . $n['title'][1] . '</p>';
                    $buttons = true;
	            } else {
	                $title = $n['title'];
	            }
                if (substr($title, 0, 1) == '/') {
                    $title = "<img src=\"{$title}\" />";
                }
	            $out .= "<a href=\"{$link}\">{$title}</a></li>\n";
	        }
        }
        if ($class != '') {
            if ($buttons) {
                $class .= '-big';
            }
            $class = " class=\"$class\"";
        }
        if ($counter >= $minimum) {
            return "<ul{$class}{$div_id}>" . $out . "</ul>";
        }
    }

    public function isDisplay($node)
    {
        if (($node['validate'] == 'access') && (!$node['access'])) {
            return false;
        }
        if (($node['validate'] == 'login') && ($this->_email)) {
            return false;
        }
        if (($node['validate'] == 'logout') && (!$this->_email)) {
            return false;
        }
        return true;
    }

    public function findLevel($node, $level)
    {
    	$children = null;
    	if( isset( $this->_nodes[$node]['children'] ) ) {
    		$children = $this->_nodes[$node]['children'];
    	}
        if ($level > 1 && count($children) ) {
            foreach ($children as $child) {
                if ($this->_nodes[$child]['selected']) {
                    return $this->findLevel($child, $level - 1);
                }
            }
            return array();
        }
        return $children;
    }

    public function findName($name)
    {
        if ($name) {
            foreach ($this->_nodes as $id => $node) {
                if (isset($node['title']) && ($node['title'] == $name)) {
                    return $id;
                }
            }
        }
    }
    public function setAllSelected($id)
    {
        if ($id) {
            $this->_nodes[$id]['selected'] = true;
            $this->setAllSelected($this->_nodes[$id]['parent']);
        }
    }
}
