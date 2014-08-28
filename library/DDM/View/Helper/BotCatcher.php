<?php

class DDM_View_Helper_BotCatcher extends Zend_View_Helper_FormHidden
{
    /**
     * @param $name
     * @param null $value
     * @param array $attribs
     * @return string
     */
    public function botCatcher($name, $value = null, array $attribs = null)
    {
        $js = '
        <script type="text/javascript">
            $(document).ready(function(){
                setTimeout(function(){
                    $("#'.$name.'").val("SUBM!TT3D 8Y A HUMAN");
                },1000);
            });
        </script>';
        return parent::formHidden($name, $value, $attribs).$js;
    }
}