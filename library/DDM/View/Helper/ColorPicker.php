<?php

/**
 * @see ZendX_JQuery_View_Helper_UiWidget
 */
require_once "ZendX/JQuery/View/Helper/UiWidget.php";

/**
 * jQuery Colorpicker View Helper
 *
 */
class ZendX_JQuery_View_Helper_ColorPicker extends ZendX_JQuery_View_Helper_UiWidget
{
    /**
     * Create a jQuery UI Widget Date Picker
     *
     * @link   http://acko.net/dev/farbtastic
     * @param  string $id
     * @param  string $value
     * @param  array  $params jQuery Widget Parameters
     * @param  array  $attribs HTML Element Attributes
     * @return string
     */
    public function colorPicker($id, $value = null, array $params = array(), array $attribs = array())
    {
    	$attribs = $this->_prepareAttributes($id, $value, $attribs);

    	$width = '190px';
    	if( !empty($params['width']) ) {
    		$width = $params['width'];
    	}

        $js = "$('#picker_".$attribs['id']."').farbtastic('#".$attribs['id']."');";
        if( $width !== false ) {
        	$js .= "$('#".$attribs['id']."').css('width','". $width ."');";
        }

		$this->jquery->AddJavascriptFile(noCacheFile('/js/lib/jQueryPlugins/farbtastic/farbtastic.js'));
        $this->jquery->addStylesheet('/js/lib/jQueryPlugins/farbtastic/farbtastic.css');
        $this->jquery->addOnLoad($js);

        $text = $this->view->formText($id, $value, $attribs);
        $text = '<div id="picker_'. $attribs['id'] .'"></div>' . $text;
        return $text;
    }

}
