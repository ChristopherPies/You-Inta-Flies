<?php
require_once(PROJECT_ROOT . '/library/functions/php/functions.php');

class IndexController extends Zend_Controller_Action {

//    public function init() {
//        parent::init();
//        //$this->view->layout()->setLayout('front');
//        //$this->view->headLink()->prependStylesheet(noCacheFile('/css/boostrap.css', '', true));//change to layout.css
//        //return $this->_redirect('/user/dashboard');
//    }

    public function indexAction() {
        //$loginForm = new Application_Form_User_Login();
        //$this->view->form = $loginForm;
        if($this->getRequest()->isPost()) {
            if(isset($_SESSION['Zend_Auth']['storage']->id) && !empty($_SESSION['Zend_Auth']['storage']->id)) {
                $this->view->message = "Login successful";
            } else {
                $this->view->message = "Login failed";
            }
        }
    }
}

?>
