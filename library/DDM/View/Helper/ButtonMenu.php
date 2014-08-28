<?php

class DDM_View_Helper_ButtonMenu extends Zend_View_Helper_FormButton {

    /**
     * Generates a 'button' element.
     *
     * @access public
     *
     * @param string|array $name If a string, the element name.  If an
     * array, all other parameters are ignored, and the array elements
     * are extracted in place of added parameters.
     *
     * @param mixed $value The element value.
     *
     * @param array $attribs Attributes for the element tag.
     *
     * @return string The element XHTML.
     */
    public function buttonMenu($name, $value = null, $attribs = null)
    {
        $class = 'btn btn-large dropdown-toggle';
        if(!empty($attribs['class'])) {
            $class = $attribs['class'].' dropdown-toggle';
        }
        if(!empty($attribs['menu'])) {
            $attribs['content'] .= ' </button><button class="'.$class.'">&nbsp;<span class="caret"></span>';
        	$menu = $attribs['menu'];
        	unset($attribs['menu']);
        	$buttonHtml = parent::formButton($name, $value, $attribs);
        } else {
            // no menu? Just use the parent
            return parent::formButton($name, $value, $attribs);
        }

        $info    = $this->_getInfo($name, $value, $attribs);
        extract($info); // name, id, value, attribs, options, listsep, disable, escape

        // Get content
        $content = '';
        if (isset($attribs['content'])) {
            $content = $attribs['content'];
            unset($attribs['content']);
        } else {
            $content = $value;
        }

        // Ensure type is sane
        $type = 'button';
        if (isset($attribs['type'])) {
            $attribs['type'] = strtolower($attribs['type']);
            if (in_array($attribs['type'], array('submit', 'reset', 'button'))) {
                $type = $attribs['type'];
            }
            unset($attribs['type']);
        }

        // build the element
        if ($disable) {
            $attribs['disabled'] = 'disabled';
        }

        $content = ($escape) ? $this->view->escape($content) : $content;

        /*
        <div class="btn-group">
            <button class="btn dropdown-toggle btn-warning" data-toggle="dropdown">Story</button>
            <button><span class="caret"></span></button>
            <ul class="dropdown-menu">
                    <li><a href="/request/edit/id/25893"><i class="icon-edit"></i> Edit Story</a></li>
                    <li><a href="/request/history/requestId/25893"><i class="icon-time"></i> Story History</a></li>

                    <li class="divider"></li>
                    <li><a href="/request/delete/id/25893"><i class="icon-trash"></i> Delete Story</a></li>
             </ul>
	    </div>
        */

        $xhtml = '';
        $xhtml = '<span class="btn-group">';
        $xhtml .= "\n\t$buttonHtml\n\t";

        if(count($menu) > 0) {
            $xhtml .= '<ul class="dropdown-menu">' . "\n";
            foreach($menu as $author) {
                foreach($author as $linkName => $link) {
                    $xhtml .= "\t\t<li><a href='". $link ."'>". $linkName . "</a></li>\n";
                }
            }
            $xhtml .= "\t</ul>\n";
        }
        $xhtml .= "</span>\n";

        return $xhtml;
    }

}