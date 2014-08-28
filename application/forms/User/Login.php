<?php

require_once('ZendX/JQuery/Form.php');

class Application_Form_User_Login extends ZendX_JQuery_Form {

    public function __construct()
    {
        parent::__construct();

        $this->addElement(
            'text',
            'Auth_email',
            array(
                'filters' => array('StringTrim','StringToLower'),
                'label' => 'E-mail',
                'autofocus' => '',
                'class' => 'input-medium'
            )
        );
        $this->addElement(
            'password',
            'Auth_password',
            array(
                'filters' => array('StringTrim'),
                'label' => 'Password'
            )
        );
        $this->addElement(
            'submit',
            'Login', array('class' => 'btn btn-primary btn-large'));

        $this->Auth_email->setRequired(true);
        $this->Auth_password->setRequired(true);

        $this->getElement('Auth_email')->addValidator('EmailAddress');

        //$this->setAction('/user/login');
    }

    /**
     * Process the form (save/load)
     *
     * @param Zend_Request_Interface $request
     * @return unknown
     */
//    public function process( $request ) {
//
//        $userId = null;
//        ppr($request); exit;
//        //$data = $request->getParams();
//
//        //setting this value is necessary to make sure we don't get the "not in haystack" error for the state
//
//        //return $userId;
//
//    }

}