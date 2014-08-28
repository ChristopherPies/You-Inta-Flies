<?php

class Application_Model_YIF_User extends Zend_Db_Table_Abstract {

    protected $_yif_data = array();

    public function setId($id) {
        if(isset($id) && !empty($id)) {
            $this->_yif_data['id'] = $id;
        }
    }

    public function setFirstName($firstName) {
        if(isset($firstName) && !empty($firstName)) {
            $this->_yif_data['first_name'] = $firstName;
        }
    }

    public function setLastName($lastName) {
        if(isset($lastName) && !empty($lastName)) {
            $this->_yif_data['last_name'] = $lastName;
        }
    }

    public function setEmail($email) {
        if(isset($email) && !empty($email)) {
            $this->_yif_data['email'] = $email;
        }
    }

    public function setPassword($password) {
        if(isset($password) && !empty($password)) {
            $this->_yif_data['password'] = $password;
        }
    }

    public function setUsername($username) {
        if(isset($username) && !empty($username)) {
            $this->_yif_data['username'] = $username;
        }
    }

    public function getFullName() {
        if(isset($this->_yif_data['first_name'])) {
            if(isset($this->_yif_data['last_name'])) {
                $fullName = $this->_yif_data['first_name'] . ' ' . $this->_yif_data['last_name'];
            } else {
                $fullName = $this->_yif_data['first_name'];
            }
        } else if (isset($this->_yif_data['last_name'])) {
            $fullName = $this->_yif_data['last_name'];
        } else {
            $fullName = null;
        }
        return $fullName;
    }

    public function getId()
    {
        if( isset($this->_yif_data['id']) ) {
            return $this->_yif_data['id'];
        } else {
            return null;
        }
    }

    public function processLogin() {
        if(isset($this->_yif_data['email']) && !empty($this->_yif_data['email'])) {
            if(isset($this->_yif_data['password']) && !empty($this->_yif_data['password'])) {
                //check if there is a matching email and password is correct
                $selectEmail = $this->_db->select();
                $selectEmail->from(array('u' => 'user'), array('u.id'))
                    ->where('u.email = ?', $this->_yif_data['email'])
                    ->orWhere('u.username = ?', $this->_yif_data['email'])
                    ->where('u.password = ?', $this->_yif_data['password']);
                $result = $this->getAdapter()->fetchAll($selectEmail);
//                if(!$result) {
//                    $result = false;
//                }
//                ppr($result); exit;
                return $result;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }
}