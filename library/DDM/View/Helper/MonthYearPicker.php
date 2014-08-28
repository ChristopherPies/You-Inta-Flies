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
class ZendX_JQuery_View_Helper_MonthYearPicker extends ZendX_JQuery_View_Helper_UiWidget
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
    public function monthYearPicker($id, $value = null, array $params = array(), array $attribs = array())
    {
        $attribs = $this->_prepareAttributes($id, $value, $attribs);

        $altfield_id = $attribs['id'];
        $id = $attribs['id'] . '_value';
        $attribs['id'] = $id;
        $attribs['style'] = 'visibility: hidden;';

        $default_params = array(
            'changeYear' => true,
            'changeMonth' => true,
            'showButtonPanel' => false,
            'altField' => '#' . $altfield_id,
            'altFormat' => 'MM yy'
        );
        $params = array_merge($default_params, $params);

        $width = '140px';
        if( !empty($params['inputWidth']) ) {
			$width = $params['inputWidth'];
        }

		$close = '"onClose": function(dateText, inst) {
            var month = $("#ui-datepicker-div .ui-datepicker-month :selected").val();
            var year = $("#ui-datepicker-div .ui-datepicker-year :selected").val();
            $(this).datepicker("setDate", new Date(year, month, 1));}';

        // TODO: Allow translation of DateTimePicker Text Values to get this action from client to server
        $params = ZendX_JQuery::encodeJson($params);
		$params = str_replace('}', ',' . $close . '}', $params);

        $js = sprintf('%s("#%s").datepicker(%s);',
                ZendX_JQuery_View_Helper_JQuery::getJQueryHandler(),
                $attribs['id'],
                $params
        );

        $this->jquery->addOnLoad($js);

        $text = $this->view->formText($id, $value, $attribs);
        $text .= '
            <style>
                .ui-datepicker-calendar {
                    display: none;
                }

                #'. $attribs['id'] .'.hasDatepicker {
                    width: ' . $width .'
                }
            </style>';

        $text .= '
            <script type="text/javascript">
                $(document).ready(function() {
                    var dp = $("#' . $id . '");
                    dp.val("' . date('m/d/Y', strtotime($attribs['value'])) . '");
                    dp.removeAttr("name");
                    dp.after("<input type=\"text\" id=\"' . $altfield_id . '\" name=\"' . $altfield_id . '\""
                        + " value=\"' . date('F Y', strtotime($attribs['value'])) . '\""
                        + " onclick=\"\" />");

                    var hd = $("#' . $altfield_id . '");
                    hd.width(' . str_replace('px', '', $width) . ');
                    hd.focus(function() { dp.focus(); });

                    dpoffset = dp.offset();
                    hd.offset({left: dpoffset.left});
                });
            </script>';

        return $text;
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
