<?php

class DDM_View_Helper_MultiOptionTree extends DDM_View_Helper_OptionTree
{
    /**
     * Render the form element
     *
     * @param string $name
     * @param string $value
     * @param array $attribs
     * @param array $options
     * @return string
     */
    public function MultiOptionTree($name, $value = null,
        $attribs = null, array $options = null)
    {
        $this->view->HeadScript()->appendFile(noCacheFile(
            '/js/lib/optionTree/multiOptionTree.js'
        ));
        $this->view->HeadScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.form.js'));

        $name = str_replace(array('[', ']'), '', $name);

        return parent::OptionTree($name, $value, $attribs, $options);
    }

    /**
     * Wrap html in a div
     *
     * @param <type> $html
     * @return <type>
     */
    protected function wrapDiv($html, $id, $class)
    {
        return parent::wrapDiv($html, $id, "{$class} multiOptionTree");
    }

    /**
     * Make the Input Element (and add the multi-option selections)
     *
     * @param int $id
     * @return string
     */
    protected function makeInput($id, $name, $value, &$options, &$attr)
    {
        $out = parent::makeInput($id, $name, 0, $options, $attr)
            . "<a href=\"#\" class=\"add\">ADD</a>\n"
            . "<ul class=\"results\" id=\"{$id}-results\" name=\"{$name}\">";

        foreach ($options as $oid => $label) {
            $opath = $this->idToPathName($attr, $name, $oid);
            $out .= "<li><a href=\"#\" class=\"del\">remove</a>{$opath}"
                . "<input type=\"hidden\""
                . " name=\"{$name}[]\""
                . " value=\"{$oid}\" />"
                . "</li>";
        }
        $out .= "</ul>\n";

        return $out;
    }

    /**
     * Wrap js in onload event handler (and add multi-option initialization)
     *
     * @param <type> $js
     * @return <type>
     */
    protected function wrapOnload($js, $id)
    {
        $js .= " $('#{$id}-tree').multiOptionTree();";
        return parent::wrapOnLoad($js);
    }
}