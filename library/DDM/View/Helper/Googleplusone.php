<?php
require_once ('Zend/View/Helper/Abstract.php');

/**
 * @author hhatfield
 *
 *
 */
class DDM_View_Helper_Googleplusone extends Zend_View_Helper_Abstract
{
    public function googleplusone($params)
    {
        if(is_string($params)){
            $params = array('url'=>$params);
        }

        $this->view->HeadScript()->appendFile('https://apis.google.com/js/plusone.js');
        echo '<div class="g-plusone" data-size="medium" data-count="false" data-href="'.$params['url'].'"></div>';
    }
}
?>
