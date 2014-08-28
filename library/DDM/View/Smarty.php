<?php
require_once 'Zend/View.php';
require_once 'Smarty/Smarty.class.php';
require_once 'DDM/View/Smarty/Plugin/Broker.php';
require_once 'DDM/View/Smarty/Plugin/Standard.php';
/**
 * Smarty view
 *
 * @author whitingj
 *
 */
class DDM_View_Smarty extends DDM_View_Abstract
{
    /**
     * Smarty object
     * @var Smarty
     */
    protected $_smarty;

    protected $_plugins;

    /**
     * Constructor
     *
     * Pass it a an array with the following configuration options:
     *
     * scriptPath: the directory where your templates reside
     * compileDir: the directory where you want your compiled templates (must be
     * writable by the webserver)
     * configDir: the directory where your configuration files reside
     *
     * both scriptPath and compileDir are mandatory options, as Smarty needs
     * them. You can't set a cacheDir, if you want caching use Zend_Cache
     * instead, adding caching to the view explicitly would alter behaviour
     * from Zend_View.
     *
     * @see Zend_View::__construct
     * @param array $config ["scriptPath" => /path/to/templates,
     *               "compileDir" => /path/to/compileDir,
     *               "configDir"  => /path/to/configDir ]
     * @throws Exception
     */
    public function __construct($config = array())
    {
        $this->_smarty = new Smarty();
        //smarty object

        if (! array_key_exists ( 'compile_dir', $config )) {
            throw new Exception ( 'compile_dir must be set in $config for ' . get_class ( $this ) );
        } else {
            $this->_smarty->compile_dir = $config ['compile_dir'];
        }

        if (array_key_exists ( 'deprecation_notices', $config )) {
            $this->_smarty->deprecation_notices = $config['deprecation_notices'];
        }

        //compile dir must be set
        $this->_smarty->debugging = true;
        if (array_key_exists ( 'config_dir', $config )) {
            $this->_smarty->config_dir = $config ['config_dir'];
        }
        //configuration files directory

        parent::__construct ( $config );
        //call parent constructor

        $this->_plugins = new DDM_View_Smarty_Plugin_Broker($this);
        $this->registerPlugin(new DDM_View_Smarty_Plugin_Standard());

    }

    /**
     * Set the compile dir (must be writable)
     * @param string $dir
     * @return DDM_View_Smarty
     */
    public function setCompilePath($dir)
    {
        $this->_smarty->compile_dir = $dir;
        return $this;
    }

    /**
     * Return the template engine object
     *
     * @return Smarty
     */
    public function getEngine()
    {
        return $this->_smarty;
    }

    /**
     * register a new plugin
     *
     * @param DDM_View_Smarty_Plugin_Abstract
     */
    public function registerPlugin(DDM_View_Smarty_Plugin_Abstract $plugin,$stackIndex = null)
    {
        $this->_plugins->registerPlugin ( $plugin, $stackIndex );
        return $this;
    }

    /**
     * Unregister a plugin
     *
     * @param string|DDM_View_Smarty_Plugin_Abstract $plugin Plugin object or class name
     */
    public function unRegisterPlugin($plugin)
    {
        $this->_plugins->registerPlugin ( $plugin );
        return $this;
    }

    /**
     * fetch a template, echos the result,
     *
     * @see Zend_View_Abstract::render()
     * @param string $name the template
     * @return void
     */
    protected function _run()
    {
        $this->strictVars ( true );
        $vars = get_object_vars ($this);
        foreach ($vars as $key => $value) {
            if ('_' != substr ($key, 0, 1)) {
                $this->_smarty->assign($key, $value);
            }
        }
        //assign variables to the template engine
        $this->_smarty->assignByRef('this', $this);

        // -- find template path (relative to script paths) --
        $template = func_get_arg(0);
        $paths = $this->getScriptPaths();
        foreach ($paths as $path) {
            $len = strlen($path);
            if (!strncmp($path, $template, $len)) {
                $file = substr($template, $len);
                $this->_smarty->template_dir = $path;
                break;
            }
        }

        //process the template (and filter the output)
        echo $this->_smarty->fetch($file);
    }

    /*
     * Clone the smarty object when cloning the view
     *
     * If you don't clone the smarty object, then partials will inherit their parent copy
     * and will reset it's member data, thus blowing out the rest of the parent's view.
     *
     * We saw this problem when rendering a viewScript decorator for a form
     * element.  The decorator would blow out the parent view data, and any data appearing
     * after the element would be blank.
     */
    function __clone()
    {
    	$this->_smarty = clone $this->_smarty;
    }



}