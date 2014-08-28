<?php
require_once ('Zend/View/Helper/Abstract.php');

/**
 * @author hhatfield
 *
 *
 */
class DDM_View_Helper_Twitter extends Zend_View_Helper_Abstract
{
    public function twitter($params)
    {
        if(is_string($params)){
            $params = array('url'=>$params);
        }
        $this->view->HeadScript()->appendFile('http://platform.twitter.com/widgets.js');
        echo '<a href="http://twitter.com/share?url='.$params['url'].'" class="twitter-share-button" data-count="none">Tweet</a></script>';
    }
}
?>