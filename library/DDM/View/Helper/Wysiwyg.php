<?php

/**
 * @see Zend_Registry
 */
require_once "Zend/Registry.php";

/**
 * @see ZendX_JQuery_View_Helper_UiWidget
 */
require_once "ZendX/JQuery/View/Helper/UiWidget.php";

/**
 * jQuery Date Picker View Helper
 *
 * @uses 	   Zend_View_Helper_FormText
 * @package    ZendX_JQuery
 * @subpackage View
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class DDM_View_Helper_Wysiwyg extends ZendX_JQuery_View_Helper_UiWidget
{
    /**
     * Create a jQuery UI Widget Date Picker
     *
     * @link   http://trentrichardson.com/examples/timepicker/
     * @param  string $id
     * @param  string $value
     * @param  array  $params jQuery Widget Parameters
     * @param  array  $attribs HTML Element Attributes
     * @return string
     */
    public function wysiwyg($id, $value = null, array $params = array(), array $attribs = array())
    {
        $attribs = $this->_prepareAttributes($id, $value, $attribs);
        if( empty($attribs['class']) )
        {
            $attribs['class'] = 'tinymce';
        }

        //$value = html_entity_decode($value);

        $width = 960;
        $height = 300;
        if( !empty($attribs['width']) ) {
        	$width = (int) $attribs['width'];
        }
        if( !empty($attribs['height']) ) {
        	$height = (int) $attribs['height'];
        }
        if( empty($attribs['spellchecker_languages']) ) {
            $attribs['spellchecker_languages'] = "+English=en";
        }
        if(empty($attribs['atd_show_types'])) {
            $attribs['atd_show_types'] = 'Bias Language,Cliches,Complex Expression,Diacritical Marks,Double Negatives,Hidden Verbs,Jargon Language,Passive voice,Phrases to Avoid,Redundant Expression';
        }
        $spellCheck = (empty($attribs['spellcheck'])) ? 'spellchecker' : $attribs['spellcheck'];

        $theme = (!isset($params['theme'])) ? 'advanced' : $params['theme'];
        $plugins = (!isset($params['plugins'])) ? 'inlinepopups,preview,print,paste,fullscreen,noneditable,visualchars,wordcount,'.$spellCheck.',inlinenotes,lengthrestriction,searchreplace,AtD' : $params['plugins'];
        $theme_advanced_buttons1 = (!isset($params['theme_advanced_buttons1'])) ? 'bold,italic,|,pastetext,pasteword,bullist,numlist,|,link,unlink,code,|,preview,|,'.$spellCheck.',|,inlinenotes,|,search,|,charmap' : $params['theme_advanced_buttons1'];
        $theme_advanced_buttons2 = (!isset($params['theme_advanced_buttons2'])) ? '' : $params['theme_advanced_buttons2'];
        $theme_advanced_buttons3 = (!isset($params['theme_advanced_buttons3'])) ? '' : $params['theme_advanced_buttons3'];
        $theme_advanced_buttons4 = (!isset($params['theme_advanced_buttons4'])) ? '' : $params['theme_advanced_buttons4'];
        $setup = (!isset($params['setup'])) ? "function(ed) {

            	// WORDS REMAINING
	            var text = '';
			    var span = document.getElementById('word-count-' + ed.id);
			    var span2 = $('#word-count-' + ed.id);

			    if(span) {

			        var wordlimit = span.innerHTML;
			        var currentCount = 0;
			        ed.onKeyUp.add(function(ed, e) {

			        	// get count from wysiwyg and do the math
			        	currentCount = $('#DS_body-word-count').text();
			            wordcount = wordlimit - currentCount;
			            if( wordcount > 0 ) {
			            	span2.removeClass('go_red');
							span2.addClass('go_green');
			            } else if (wordcount < 0 ) {
							span2.removeClass('go_green');
							span2.addClass('go_red');
			            } else {
							span2.removeClass('go_green');
							span2.removeClass('go_red');
			            }

			            // update words remaining.
			            span.innerHTML = wordcount;

			        });
			    }

				ed.onKeyUp.add(function(ed, e) {
	                  ed.save();
	                  $(ed.getElement()).keyup();
	                } )
	            }" : $params['setup'];
        $setup = empty($setup) ? '""' : $setup;

        $params = ZendX_JQuery::encodeJson($params);

            // disable WYSIWYG on the iPhone
        $js = sprintf('if ((navigator.userAgent.match(/iPhone/i))
		|| (navigator.userAgent.match(/iPod/i))
		|| (navigator.userAgent.match(/iPad/i))
	) {
            // >>>>> insert iphone specific code here <<<<<
        } else {
        $(\'textarea.%s\').tinymce({
            // Location of TinyMCE script
            script_url : \'/js/lib/tinymce/tiny_mce.js\',

            // General options
            theme : "%s",
            plugins : "%s",

            spellchecker_languages : "%s",

            // Theme options
            theme_advanced_buttons1 : "%s",
            theme_advanced_buttons2 : "%s" ,
            theme_advanced_buttons3 : "%s" ,
            theme_advanced_buttons4 : "%s",
            theme_advanced_toolbar_location : "top",
            theme_advanced_toolbar_align : "left",
            theme_advanced_statusbar_location : "bottom",
            theme_advanced_path : false,

            height : "%d",
            width : "%d",

            paste_text_sticky: true,

            atd_rpc_id : "%s",
            atd_rpc_url : "%s",
            atd_css_url : "%s",
            atd_show_types : "%s",

            "formats" : {
                "inlinenotes" : {
                    "inline" : "var"
                }
            },

            oninit : loadMCETooltip,

            content_css : "/css/lib/tinymce_custom_styles.css",

            setup: %s

        });
        }',
            $attribs['class'],
            $theme,
            $plugins,
            $attribs['spellchecker_languages'],
            $theme_advanced_buttons1,
            $theme_advanced_buttons2,
            $theme_advanced_buttons3,
            $theme_advanced_buttons4,
            $height,
            $width,
            @$attribs['atd_rpc_id'],
            @$attribs['atd_rpc_url'],
            @$attribs['atd_css_url'],
            $attribs['atd_show_types'],
            $setup
        );
        $js .= '
        function loadMCETooltip()
        {
            $(".mceButton").tooltip();
        }
        ';

        $this->jquery->setRenderMode(ZendX_JQuery::RENDER_ALL ^ ZendX_JQuery::RENDER_LIBRARY);

        require_once('DDM/Functions.php');

        // use tinymce
        $this->jquery->AddJavascriptFile(noCacheFile('/js/lib/tinymce/jquery.tinymce.js'));

        // use cl ed.
        //$this->jquery->AddJavascriptFile(noCacheFile('/js/lib/cleditor/jquery.cleditor.js'));
        //$this->jquery->AddJavascriptFile('/js/lib/cleditor/clplugins.js');
		//$this->jquery->addStylesheet('/js/lib/cleditor/jquery.cleditor.css');

        $this->jquery->addOnLoad($js);

        return $this->view->formTextarea($id, $value, $attribs);
    }
}
