<?php
require_once(PROJECT_ROOT . '/library/functions/php/functions.php');

class UserController extends Zend_Controller_Action { //DDM_Controller_Action {

    public function loginAction() {
        //$this->_helper->viewRenderer->setNoRender(true);
        //$this->view->layout()->disableLayout();
        if($this->getRequest()->isPost()) {
            $email = $this->getRequest()->getParam('Auth_email');
            $pwd = $this->getRequest()->getParam('Auth_password');
            $user = new Application_Model_YIF_User();
            $user->setEmail($email);
            $user->setPassword($pwd);
            $userId = $user->processLogin();
            if($userId) {
                $_SESSION['Zend_Auth']['storage']->id = $userId;
                //$message = 'successful';
            } else {
                //$message = 'failed';
            }
            $this->_redirect('/');
            //$this->_forward('index','index',null,array('login_status' => $message));
        }
    }

    public function logoutAction()
    {
        // record the logout time
        $currentUser = null;
        if (!empty($_SESSION['Zend_Auth']['storage']->id) && is_object($_SESSION['Zend_Auth']['storage'])) {
            $currentUser = $_SESSION['Zend_Auth']['storage']->id;
        }
//        if ($currentUser !== null) {
//            $userLog = new Application_Model_YIF_UserLogin();//
//            $userLog->getLastLogin($currentUser);
//            if ($userLog->getUserId() == $currentUser) {
//                $userLog->setLogoutTime(date('Y-m-d H:i:s'));
//                $userLog->save();
//            }
//        }

        $auth = Zend_Auth::getInstance();
        if ($auth->hasIdentity()) {
            $auth->clearIdentity();
        }
        //Remove the cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        //Remove global data
        unset($_SESSION);
        //Kill the session
        session_destroy();
        $this->_redirect('/');
    }

}