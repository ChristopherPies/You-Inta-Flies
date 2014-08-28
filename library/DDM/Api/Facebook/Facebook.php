<?php
require_once 'src/facebook.php';

class DDM_Api_Facebook_Facebook extends Facebook
{
    /**
     * Authenticate with the appId and secret from your ini file
     * 
     * @throws Exception
     */
    public function __construct()
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options = $bootstrap->getOptions();
        $facebook = $options['facebook'];
        if(empty($facebook['appId']) || empty($facebook['secret'])) {
            throw new Exception("Facebook API requires facebook.appId and facebook.secret in your ini file");
        }
        parent::__construct(array(
            'appId' => $facebook['appId'],
            'secret' => $facebook['secret']
        ));
    }
    
    /**
     * Gets user info from the api
     * 
     * @param string $facebookUserId (optional)
     * @param string $urlAppend (optional)
     * @return array
     */
    public function getUserData($facebookUserId=null,$urlAppend='?fields=id,name,first_name,last_name,link,username,location,email,website,verified') {
        $userId = (empty($facebookUserId)) ? $this->getUser() : $facebookUserId;
        return $this->api($userId.$urlAppend);
    }
}
?>
