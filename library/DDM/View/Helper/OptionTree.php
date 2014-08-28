<?php
/**
 * CHANGE TO jquery-optionTree-1.3.js : remove the '!' from line 193
 *
 * Name: Commonly known as 'Cascading Selects' or 'Cascading Dropdowns'
 *
 * Note: DON'T USE THE 'indexed' attribute.  Instead, use 'format' to
 *       set the type of data structure to be passed.  Format will set
 *       the indexed attribute for you.
 *
 * Note: DON'T USE THE 'on_each_change' attribute.  If you want to do
 *       JSON calls to update the options, set 'tree' to a JSON url.
 *
 * Note: DON'T USE THE 'preselect' value.  This will be set using the
 *       element value.
 *
 * Note: DON'T USE THE 'set_value_on' value.  This will be set by the
 *       format, depending on what indexed is set to.
 *
 * Note: The format 'indextree' will only work with statically defined
 *       tree data.  It will not work with tee options pulled dynamically
 *       through a JSON url. This is because I have to re-define the
 *       on_each_update to make the new data format work, and this breaks
 *       the JSON functionality.
 *
 * Example:
 *
 *  $this->addElement('MultiOptionTree', 'DS_topics', array(
 *    'label' => 'Topic',
 *    'attribs' => array(
 *      'empty_value' => 'no value',
 *      'set_value_on' => 'each',
 *      'choose' => "function(level) { return 'Choose level ' + level; }",
 *      'preselect_only_once' => true,
 *      'get_parent_value_if_empty' => true
 *      'tree' => $tree
 *  )));
 *
 *  (or) you can add the tree after the fact
 *
 *  $this->getElement('DS_topics')->setAttrib('tree', $tree);
 */
class DDM_View_Helper_OptionTree extends Zend_View_Helper_FormElement
{
    var $_trees = array();

    /**
     * Default optionTree jQuery options
     * @var array
     */
    var $_defaults = array(
        'choose' => '-- Select --',
        'show_multiple' => false,
        'preselect_only_once' => true, // no reason to change this
        'get_parent_value_if_empty' => true,
        'loading_image' => '',
        'select_class' => '',
        'leaf_class' => 'final',
        'empty_value' => '',
        'on_each_change' => false,
        'format' => 'indextree', // 'indexed' | 'tree' | 'indextree'
        'tree' => '', // $array | 'element_name' | '/callback/url'
        'change' => '', // 'function() { }'
        'node_name' => 'name', // default node column name
        'node_parent' => 'pid', // default node column name
        'node_separator' => ' &raquo; '
    );

    /**
     * Render the form element
     *
     * @param string $name
     * @param string $value
     * @param array $attribs
     * @param array $options
     * @return string
     */
    public function OptionTree($name, $value = null,
        $attribs = null, array $options = null)
    {
        $attr = array_merge($this->_defaults, $attribs);
        $info = $this->_getInfo($name, $value, $attr);

        $this->view->HeadScript()->appendFile(noCacheFile(
            '/js/lib/optionTree/jquery-optionTree-1.3.js'
        ));

        $input = $this->makeInput($info['id'], $info['id'], $value, $options, $attr);
        $input = $this->wrapDiv($input, $info['id'], 'optionTree');

        $optionsName = str_replace(array('[',']'), '', $name) . '_options';
        $treeName = str_replace(array('[',']'), '', $name) . '_tree';

        $this->setPreselect($attr, $options, $name, $value);

        $treeData = $this->makeTree($attr, $treeName);

        $this->setFormat($attr, $treeName, $optionsName);

        $optionsData = $this->makeOptions($optionsName, $attr);

        $change = $this->makeChange($attr['change']);

        $plugin = $this->makePlugin($info['id'], $treeName, $optionsName, $change, $attr);
        $plugin = $this->wrapJSON($attr['tree'], $plugin, $attr);
        $plugin = $this->wrapOnload($plugin, $info['id']);
        $plugin = $this->wrapScript($treeData . $optionsData . $plugin);

        // Add element to page with separator for javascript
        echo '<div id="node-separator" style="display: none;">' . $attr['node_separator'] . '</div>';

        return $input . $plugin;
    }

    /**
     * Set the preselect option
     *
     * @param array $attr
     * @param <type> $name
     * @param <type> $value
     */
    protected function setPreselect(&$attr, $options, $name, $value)
    {
        $values = $this->idToPathValues($attr, $name, $value);
        $name = str_replace(array('[',']'), array('-',''), $name);
        $attr['preselect'] = array($name => $values);
    }

    /**
     * Make an input field
     *
     * @param <type> $id
     * @param <type> $name
     * @param <type> $value
     * @return <type>
     */
    protected function makeInput($id, $name, $value, &$options, &$attr)
    {
        return "<input"
            . " type=\"hidden\""
            . " id=\"{$id}\""
            . " name=\"{$name}\""
            . " value=\"{$value}\" />";
    }

    /**
     * Wrap html in a div
     *
     * @param <type> $html
     * @return <type>
     */
    protected function wrapDiv($html, $id, $class)
    {
        return "<div id=\"{$id}-tree\" class=\"{$class}\">{$html}</div>\n";
    }

    /**
     * Wrap js in onload event handler
     *
     * @param <type> $js
     * @return <type>
     */
    protected function wrapOnload($js, $id = null)
    {
        return "\$(function() { {$js} });\n";
    }

    /**
     * Wrap js in html with onload event
     *
     * @param <type> $js
     * @return <type>
     */
    protected function wrapScript($js)
    {
        return "<script type=\"text/javascript\">\n{$js}</script>\n";
    }

    /**
     * Wrap in json ajax call
     *
     * @param <type> $url
     * @param <type> $js
     * @return <type>
     */
    protected function wrapJSON($url, $js, &$attr)
    {
        if (is_array($url) || (strpos($url, '/') === false)) {
            return $js;
        } else {
            $attr['on_each_change'] = $url;
            return "\$.getJSON('{$url}', function(tree) { {$js} });\n";
        }
    }

    /**
     * Instantiate the jQuery optionTree plugin
     *
     * @param <type> $id
     * @param <type> $tree
     * @param <type> $options
     * @param <type> $change
     * @return <type>
     */
    protected function makePlugin($id, $tree, $options, $change, $attr)
    {
        if ($attr['format'] == 'indextree') {
            $tree .= '[0]';
        }
        return "\$('#{$id}').optionTree("
            . $tree . ", "
            . $options . ")"
            . $change . ";";
    }

    /**
     * make the tree
     *
     * @param array $tree
     * @return string
     */
    protected function makeTree($attr, &$name)
    {
        $node_pid = $attr['node_parent'];
        $node_name = $attr['node_name'];

        if (is_array($attr['tree'])) {
            $nodes = array();
            foreach ($attr['tree'] as $id => &$item) {
                $pid = intval($item[$node_pid]);
                $nodes[$pid][$id] = $item[$node_name];
            }
            return "var {$name} = " . json_encode($nodes) . ";\n";
        } else {
            $name = "{$attr['tree']}_tree";
        }
    }

    /**
     * make the options
     *
     * @param array $attr
     * @return string
     */
    protected function makeOptions($optionsName, $attr)
    {
        $out = '';
        $filter = array('tree' => 1, 'format' => 1, 'id' => 1,
            'node_name' => 1, 'node_parent' => 1);

        foreach ($attr as $name => $value) {
            if (!isset($filter[$name])) {
                $out .= ($out ? ", " : '');
                if (!(is_string($value)
                    && (substr($value, 0, 9) == 'function('))) {
                        $value = json_encode($value);
                }
                $out .= $name . ': ' . $value;
            }
        }

        return "var {$optionsName} = { " . $out . " }\n";
    }

    /**
     * Add change option to optionTree init
     *
     * @param int $id
     * @param array $attr
     * @return string
     */
    protected function makeChange($change)
    {
        if ($change) {
            return ".change(function() { " . $change . " })";
        }
    }

    /**
     * Set the data format
     */
    protected function setFormat(&$attr, $treeName, $optionsName)
    {
        switch ($attr['format']) {
            case 'indexed':
                $attr['set_value_on'] = 'each';
                $attr['indexed'] = true;
                break;

            case 'tree':
                $attr['set_value_on'] = 'leaf';
                $attr['indexed'] = false;
                break;

            case 'indextree':
                $attr['set_value_on'] = 'each';
                $attr['indexed'] = true;
                if ($attr['on_each_change'] === false) {
                    $attr['on_each_change'] = "function(value, tree, options) { "
                        . "\$(this).optionTree("
                        . $treeName . "[value], "
                        . $optionsName . "); }";
                }
        }
    }

    /**
     * Convert a Node ID to a Path Name
     *
     * @param <type> $tree
     * @param <type> $id
     */
    protected function idToPathName(&$attr, $name, $id)
    {
        $values = $this->idToPathValues($attr, $name, $id);

        $pname = $attr['node_name'];
        $tree = &$this->getTree($attr, $name);

        $path = '';
        foreach ($values as $nid) {
            $path .= ($path ? $attr['node_separator'] : '');
            $path .= $tree[$nid][$pname];
        }
        return $path;
    }

    /**
     * Expand the preselect option from a value to an array
     *
     * @param array $attr
     * @param <type> $name
     * @param <type> $value
     */
    protected function idToPathValues(&$attr, $name, $value)
    {
        if (is_array($value)) {
            return $value;
        }
        $pname = $attr['node_parent'];
        $tree = &$this->getTree($attr, $name);

        return $this->climbTree($tree, $value, $pname);
    }

    /**
     * Recursively build selected node array
     *
     * @param <type> $attr
     * @param <type> $id
     * @return <type>
     */
    protected function climbTree(&$tree, $id, $pname)
    {
        if (($id <  1) || (!isset($tree[$id]))) {
            return array();
        }
        $pid = intval($tree[$id][$pname]);
        $items = $this->climbTree($tree, $pid, $pname);
        $items[] = "{$id}";

        return $items;
    }

    /**
     * Get the Tree (by name or array)
     *
     * @param <type> $attr
     * @param <type> $name
     * @return <type>
     */
    protected function &getTree(&$attr, $name)
    {
        $tree = &$attr['tree'];

        if (!is_array($tree)) {
            $tree = &$this->_trees[$tree];
        } else if (!isset($this->_trees[$name])) {
            $this->_trees[$name] = &$tree;
        }
        return $tree;
    }
}