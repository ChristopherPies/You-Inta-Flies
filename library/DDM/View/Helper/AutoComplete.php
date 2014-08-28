<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category    ZendX
 * @package     ZendX_JQuery
 * @subpackage  View
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license     http://framework.zend.com/license/new-bsd     New BSD License
 * @version     $Id: AutoComplete.php 20752 2010-01-29 11:31:30Z beberlei $
 */

/**
 * @see ZendX_JQuery_View_Helper_UiWidget
 */
require_once "ZendX/JQuery/View/Helper/UiWidget.php";

/**
 * jQuery Autocomplete View Helper
 *
 * @uses 	   Zend_Json, Zend_View_Helper_FormText
 * @package    ZendX_JQuery
 * @subpackage View
 * @copyright  Copyright (c) 2005-2010 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class DDM_View_Helper_AutoComplete extends ZendX_JQuery_View_Helper_AutoComplete
{
    /**
     * Builds an AutoComplete ready input field.
     *
     * This view helper builds an input field with the {@link Zend_View_Helper_FormText} FormText
     * Helper and adds additional javascript to the jQuery stack to initialize an AutoComplete
     * field. Make sure you have set one out of the two following options: $params['data'] or
     * $params['url']. The first one accepts an array as data input to the autoComplete, the
     * second accepts an url, where the autoComplete content is returned from. For the format
     * see jQuery documentation.
     *
     * @link   http://docs.jquery.com/UI/Autocomplete
     * @throws ZendX_JQuery_Exception
     * @param  String $id
     * @param  String $value
     * @param  array $params
     * @param  array $attribs
     * @return String
     */
    public function autoComplete($id, $value = null, array $params = array(), array $attribs = array())
    {
        if( isset($params['valueLabel']) ) {
            $valueLabel = $params['valueLabel'];
        } else {
            $valueLabel = $value;
        }

        $attribs = $this->_prepareAttributes($id, $value, $attribs);

        if (!isset($params['source'])) {
            if (isset($params['url'])) {
                $params['source'] = "'". $params['url'] ."'";
                unset($params['url']);
            } else if (isset($params['data'])) {
                $params['source'] = json_encode($params['data']);
                unset($params['data']);
            } else {
                require_once "ZendX/JQuery/Exception.php";
                throw new ZendX_JQuery_Exception(
                    "Cannot construct AutoComplete field without specifying 'source' field, ".
                    "either an url or an array of elements."
                );
            }
        }

         // default min length
         if( !isset($params['minLength']) ) {
                $params['minLength'] = 2;
         }

         if( isset($params['url']) ) {
                $params['dataType'] = 'json';
         }
         
         if( isset($params['keyForElement'])) {
             $keyForElement = $params['keyForElement'];
         }
         else{
             $keyForElement = 'label';
         }
             

         // add a hidden field, on change we'll put the id in it. populate the hidden field's value with the value and the visible with the label
         $js = " 
            $(document).ready(function(){
                    $('input#". $attribs['id'] ."').each(function() {  
                        if(!$(this).hasClass('ui-autocomplete-input'))
                        {
                            var autoCompelteElement = this;
                            var formElementName = $(this).attr('name');
                            var hiddenElementID  = $(this).attr('id') + '_autocomplete_hidden';
                            /* change name of orig input */
                            $(this).attr('name', formElementName + '_autocomplete_label');
                            /* create new hidden input with name of orig input */
                            $(this).after(\"<input type='hidden' name='\" + formElementName + \"' id='\"+ hiddenElementID + \"' value='". $value ."' />\");
                            $(this).val('".$valueLabel."');
                            $(this).autocomplete({source:". $params['source'] .",
                                select: function(event, ui) {
                                ".((!empty($params['select']))?$params['select']:"
                                    var selectedObj = ui.item;
                                    //alert('hi');
                                    //debugger;
                                    $(autoCompelteElement).val(selectedObj.$keyForElement);
                                    $('#'+hiddenElementID).val(selectedObj.value);
                                    return false;")."
                                },
                                focus: function(event, ui) {
                                    var selectedObj = ui.item;
                                    $(autoCompelteElement).val(selectedObj.$keyForElement);
                                    $('#'+hiddenElementID).val(selectedObj.value);
                                    return false;
                                }
                            });
                            $(this).keyup(function(e) {
                                var charLength = $(this).val().length;
                                if( charLength < ". $params['minLength'] ." ) {
                                        //alert('Handler for .keyup() called.' + charLength);
                                        $('#'+hiddenElementID).val('');
                                }
                                if(e.keycode == 13) {
                                    // do nothing
                                }
                            });
                            //override the _renderItem function so html is processed rather than displayed as text
                            $(this).data('autocomplete')._renderItem = function( ul, item ) {
                                var string = '<li></li>';  //I have no idea how.  I have no idea why.  But this renders to  var string = '<ul><li></li>'; Thus the line below....
                                string = '<li></li>';
                                return $( string )
                                .data( 'item.autocomplete', item )
                                .append( '<a>' + item.label + '</a>' )
                                .appendTo( ul );

                            }; 
        ";
        if(empty($params['hideErrors']))
        {
            $js.="
                            $(this).blur(function(){
                                if($('#'+hiddenElementID).val() == '')
                                {
                                    $(this).val('');
                                    $(this).attr('placeholder','Nothing selected');
                                    $(this).parent('div.control-group').addClass('error');
                                }
                                else
                                {
                                    $(this).parent('div.control-group').removeClass('error');
                                }
                            });
            ";
        }
        $js.="
                        }
                    });
            });";
        $return = $this->view->formText($id, $value, $attribs);
        $return .= $this->view->inlineScript(Zend_View_Helper_HeadScript::SCRIPT,$js);
        
        return $return;
    }

     protected function getHandleCount($params)
     {
         if(version_compare($this->jquery->getUiVersion(), "1.7.0") >= 0) {
                if( isset($params['values']) ) {
                return count($params['values']);
                }
                return 0;
         } else {
             return count($params['handles']);
         }
    }
}