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
class ZendX_JQuery_View_Helper_DateTimePicker extends ZendX_JQuery_View_Helper_UiWidget
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
    public function dateTimePicker($id, $value = null, array $params = array(), array $attribs = array())
    {
        $attribs = $this->_prepareAttributes($id, $value, $attribs);

        $params = array_merge($attribs, $params);

        if(!isset($params['dateFormat']) && Zend_Registry::isRegistered('Zend_Locale')) {
            $params['dateFormat'] = self::resolveZendLocaleToDatePickerFormat();
        }

        // default minute increment
        if(!isset($params['stepMinute']) ) {
            $params['stepMinute'] = 15;
        }

        // 12/24 hour default
        if(!isset($params['ampm']) ) {
            $params['ampm'] = true;
        }

        // default hour
        if(!isset($params['hour']) ) {
        	$params['hour'] = 12;
        }

        if(!isset($params['minute']) ) {
        	$params['minute'] = 0;
        }

        // TODO: Allow translation of DateTimePicker Text Values to get this action from client to server
        $params = ZendX_JQuery::encodeJson($params);

        $js = sprintf('%s("#%s").datetimepicker(%s);',
                ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $attribs['id'],
                $params
        );

	$this->jquery->AddJavascriptFile(noCacheFile('/js/lib/jQueryPlugins/jquery-ui-timepicker-addon-0.9.7.js'));
        $this->jquery->addStylesheet('/css/lib/timepicker.css');
        $this->jquery->addOnLoad($js);

        return $this->view->formText($id, $value, $attribs);
    }

    /**
     * A Check for Zend_Locale existance has already been done in {@link datePicker()}
     * this function only resolves the default format from Zend Locale to
     * a jQuery Date Picker readable format. This function can be potentially buggy
     * because of its easy nature and is therefore stripped from the core functionality
     * to be easily overriden.
     *
     * @return string
     */
    public static function resolveZendLocaleToDatePickerFormat($format=null)
    {
        if($format == null) {
            $locale = Zend_Registry::get('Zend_Locale');
            if( !($locale instanceof Zend_Locale) ) {
                require_once "ZendX/JQuery/Exception.php";
                throw new ZendX_JQuery_Exception("Cannot resolve Zend Locale format by default, no application wide locale is set.");
            }
            /**
             * @see Zend_Locale_Format
             */
            require_once "Zend/Locale/Format.php";
            $format = Zend_Locale_Format::getDateFormat($locale);
        }

        $dateFormat = array(
            'EEEEE' => 'D', 'EEEE' => 'DD', 'EEE' => 'D', 'EE' => 'D', 'E' => 'D',
            'MMMM' => 'MM', 'MMM' => 'M', 'MM' => 'mm', 'M' => 'm',
            'YYYYY' => 'yy', 'YYYY' => 'yy', 'YYY' => 'yy', 'YY' => 'y', 'Y' => 'yy',
            'yyyyy' => 'yy', 'yyyy' => 'yy', 'yyy' => 'yy', 'yy' => 'y', 'y' => 'yy',
            'G' => '', 'e' => '', 'a' => '', 'h' => '', 'H' => '', 'm' => '',
            's' => '', 'S' => '', 'z' => '', 'Z' => '', 'A' => '',
        );

        $newFormat = "";
        $isText = false;
        $i = 0;
        while($i < strlen($format)) {
            $chr = $format[$i];
            if($chr == '"' || $chr == "'") {
                $isText = !$isText;
            }
            $replaced = false;
            if($isText == false) {
                foreach($dateFormat AS $zl => $jql) {
                    if(substr($format, $i, strlen($zl)) == $zl) {
                        $chr = $jql;
                        $i += strlen($zl);
                        $replaced = true;
                    }
                }
            }
            if($replaced == false) {
                $i++;
            }
            $newFormat .= $chr;
        }

        return $newFormat;
    }
}
