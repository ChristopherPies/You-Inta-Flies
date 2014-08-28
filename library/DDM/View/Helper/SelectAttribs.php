<?php
class DDM_View_Helper_SelectAttribs extends Zend_View_Helper_FormElement {
    public function selectAttribs($name, $value = null, $attribs = null, $options = null, $listsep = "<br />\n") {
        $info = $this->_getInfo($name, $value, $attribs, $options, $listsep);
        extract($info); // name, id, value, attribs, options, listsep, disable

        // force $value to array so we can compare multiple values to multiple
        // options; also ensure it's a string for comparison purposes.
        $value = array_map('strval', (array) $value);
        
        // now start building the XHTML.
        // Build the surrounding select element first.
        $xhtml = '<select'
                . ' name="' . $this->view->escape($name) . '"'
                . ' id="' . $this->view->escape($name) . '"'
                . $this->_htmlAttribs($attribs)
                . ">\n  ";

        // build the list of options
        $list = array();
        foreach ($options as $opt_value => $option) {
            $list[] = $this->_build($option, $value);
        }

        // add the options to the xhtml and close the select
        $xhtml .= implode("\n   ", $list) . "\n</select>";

        return $xhtml;
    }

    protected function _build($option, $val) {
        $html = '<option';
        foreach ($option as $attrib => $value) {
            $html .= " $attrib=\"$value\"";
            if($attrib == 'value' && in_array($value, $val)) {
                $html .= ' selected ';
            }
        }
        return $html.">".$option['label']."</option>";
    }
}