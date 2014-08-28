<?php
require_once ('Zend/View/Helper/Abstract.php');

/**
 * @author hhatfield
 *
 *
 */
class DDM_View_Helper_Facebook extends Zend_View_Helper_Abstract
{
    public function facebook($type, $params)
    {
        if(is_string($params)){
            $params = array('url'=>$params);
        }
        switch($type) {
            case 'like':
                echo '<iframe src="http://www.facebook.com/plugins/like.php?href='.urlencode($params['url']).'&amp;send=false&amp;layout=button_count&amp;width=90&amp;show_faces=false&amp;action=like&amp;colorscheme=light&amp;font&amp;height=21" 
                    scrolling="no" frameborder="0" style="border:none; overflow:hidden; width:90px; height:21px;" allowTransparency="true"></iframe>';
                break;
            default:
                break;
        }
    }
}
?>