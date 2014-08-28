<?php

class DDM_View_Helper_TagAutoComplete extends Zend_View_Helper_FormElement
{
    public $tags = array();

    /**
     * Render the form element
     *
     * @param string $name
     * @param string $value
     * @param array $attribs
     * @param array $options
     * @return string
     */
    public function TagAutoComplete($name, $value = null, $attribs = null, array $options = null)
    {
        $info = $this->_getInfo($name, $value, $attribs);
        $id = $info['id'];

        $this->view->HeadScript()->appendFile(noCacheFile(
            '/js/lib/tagit/tagit.js'
        ));
        $this->view->headLink()->appendStylesheet(
            '/css/lib/tagit/tagit-simple-blue-old.css'
        );

        // Tagit options
        if(!isset($this->tags[$id])) {
            $this->tags[$id] = array();
        }

        $tagit = array_merge(array(
                'minLength' => 1,
                'triggerKeys' => array('enter', 'comma', 'tab')
            ),
            isset($attribs['tagit']) ? $attribs['tagit'] : array()
        );

        if(isset($tagit['tagSource'])) {
            $tagit['tagSource'] = $this->tagSource($tagit['tagSource']);
        }
        if(isset($tagit['triggerKeys'])) {
            $tagit['triggerKeys'] = $this->tagTriggerKeys($tagit['triggerKeys']);
        }
        $initialTags = array_merge($this->tags[$id], isset($tagit['initialTags']) ? $tagit['initialTags'] : array());
        $initialTags = array_merge($options, isset($initialTags) ? $initialTags : array());
        unset($tagit['initialTags']);

        $tagit_options = '';
        //this foreach makes the picker allow spaces
        foreach($tagit as $key => $val) {
            $tagit_options .= $key . ': ' . $val . ",\n";
        }
        $ulStyle = null;
        if(!empty($attribs['ul_style'])) {
            $ulStyle = $attribs['ul_style'];
        }
        return $this->tagOptions($id, $name, $options,$ulStyle)
            . "<script type=\"text/javascript\">\n"
            . "$(document).ready(function() { $('#{$id}').tagit({"
            . $tagit_options
            //. $this->tagSource($attribs) . ", "
            //. $this->tagTriggerKeys($attribs) . ", "
            . $this->tagInitialTags($initialTags) . ", "
            . "select: true }); });\n"
            . "</script>\n";
    }

    /**
     * Generate the unordered list of options
     *
     * @param int $id
     * @param array $options
     * @return string
     */
    private function tagOptions($id, $name, $options,$ul_style='')
    {
        $out = "<ul class=\"tagit\" style=\"{$ul_style}\" id=\"{$id}\" name=\"{$name}\">";
        foreach ($options as $id => $value) {
            $out .= "<li>{$value}</li>";
        }
        $out .= "</ul>\n";
        
        return $out;
    }

    /**
     * Specify the tag source
     * 1. array('item1','item2','item3')
     * 2. "http://" or "/path"
     * 3. "item1,item2,item3"
     *
     * @param array $tag
     * @return string
     */
    private function tagSource($source)
    {
        //$source = isset($attribs['tagSource']) ? $attribs['tagSource'] : array();

        if (is_array($source)) {
            $out = json_encode($source);
        } else if (($source[0] == '/') || (substr($source, 7) == 'http://')) {
            $source .= (substr($source, -1) != '/') ? '/' : '';
            $out = "'{$source}'";
        } else {
            //$out = '"' . str_replace($source, '"', '\"') . '"';
            $out = $source;
        }

        return $out;
    }

    /**
     * Specify the trigger keys
     *
     * @param array $keys array('enter','space','comma',tab')
     * @return string
     */
    private function tagTriggerKeys(array $keys)
    {
        return "['" . implode("','", $keys) . "']";
    }

    /**
     * Specify the initial tags
     *
     * @param array $tag
     * @return string
     */
    private function tagInitialTags($initialTags)
    {
        foreach($initialTags as &$initialTag) {
            $initialTag = str_replace("'", "\\'", $initialTag);
        }
        unset ($initialTag);
        return "initialTags: ['" . implode("','", $initialTags) . "']";
    }
}
