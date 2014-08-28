<?php

use Assetic\FilterManager;
use Assetic\Factory\AssetFactory;
use Assetic\AssetManager;
use Assetic\Asset\AssetCollection;
use Assetic\AssetWriter;

/*
 * Assetic View Helper
 *
 * Useful for applying filters, such as SASS and YuiCompressor, and combining
 * asset groups and returning a single url to use in templates.
 */
class DDM_View_Helper_Assetic
{
    /**
     * Used by _getFilterManager() to hold internal FilterManager instance.
     * @var FilterManager
     */
    protected $_filterManager;

    /**
     * Used by _getAssetFactory() to hold internal AssetFactory instance.
     * @var AssetFactory
     */
    protected $_assetFactory;

    /**
     * Used by _getAssetManager() to hold internal AssetManager instance.
     * @var AssetManager
     */
    protected $_assetManager;

    /**
     * Used by _getAssetWriter() to hold internal AssetWriter instance.
     * @var AssetWriter
     */
    protected $_assetWriter;

    /**
     * Used by _wroteAssetGroup() to keep track of which asset groups have been written.
     * @var array Associative array with asset group name as key
     */
    protected $_wroteAssetGroup;

    /**
     * Used by _getBinPath() to hold all bin paths found.
     * @var array
     */
    protected $_binPaths;

    /**
     * Used by _emptyConfig() to hold empty Zend_Config instance.
     * @var Zend_Config
     */
    protected $_emptyConfig;

    /**
     * Used by _getConfig() to hold Assetic configs.
     * @var Zend_Config
     */
    protected $_config;

    /**
     * Called by Zend Framework when loading view helper
     * @return DDM_View_Helper_Assetic
     */
    public function Assetic()
    {
        return $this;
    }

    /**
     * Defines a filter.
     *
     * @param string $alias The alias used to identify the filter instance.
     * @param array $configs An optional associative array of configurations
     * for the filter instance. Overrides config file and defaults. For
     * constructor arguments use 'arguments' key. If alias not class name, use
     * 'class' key for class name.
     * @param boolean $overwrite Prevents filter from being redefined. Defaults false.
     * @return DDM_View_Helper_Assetic
     */
    public function defineFilter($alias, array $configs = array(), $overwrite = false)
    {
        $alias = str_replace('?', '', $alias);
        $filterManager = $this->_getFilterManager();
        $cleanAlias = $this->_cleanFilterAlias($alias);

        if ($filterManager->has($cleanAlias) && $overwrite !== true) {
            return $this;
        }

        $defaults = $this->_getConfig('filter')->get($alias, $this->_emptyConfig())->toArray();
        $configs = array_merge($defaults, $configs);

        $className = (isset($configs['class'])) ? $configs['class'] : $alias;
        $arguments = (isset($configs['arguments'])) ? $configs['arguments'] : array();

        $reflectionClass = new ReflectionClass(sprintf('Assetic\\Filter\\%sFilter', ucfirst($className)));
        $filter = $reflectionClass->newInstanceArgs($arguments);

        foreach ($configs as $key => $value) {
            if (in_array($key, array('class', 'arguments'))) {
                continue;
            }

            $method = 'set' . ucfirst($key);
            $filter->$method($value);
        }

        $filterManager->set($cleanAlias, $filter);

        return $this;
    }

    /**
     * Adds asset to specified asset group.
     *
     * @param string $assetGroupName Name of asset group.
     * @param array|string $inputs Array of file, glob, http, reference strings for AssetFactory.
     * @param array|string $filters Optional array of filter aliases for AssetFactory.
     * Prepend ! to exclude a filter, or ? to exclude only in debug.
     * @param array $options Optional array of AssetFactory options.
     * @return DDM_View_Helper_Assetic
     */
    public function addAsset($assetGroupName, $inputs, $filters = array(), array $options = array())
    {
        if (!isset($options['debug'])) {
            $options['debug'] = $this->_isDebug($assetGroupName);
        }

        $debug = $options['debug'];

        foreach ($filters as $alias) {
            if ($debug && '?' == substr($alias, 0, 1)) {
                continue;
            }
            $this->defineFilter($alias);
        }

        $asset = $this->_getAssetFactory()->createAsset($inputs, $this->_cleanFilterAliases($filters), $options);
        $this->_getAssetGroup($assetGroupName)->add($asset);
        unset($this->_wroteAssetGroup[$assetGroupName]); // make sure _writeAssetGroup() doesn't skip writting
        return $this;
    }

    /**
     * Assigns filters to specified asset group.
     *
     * @param string $assetGroupName Name of asset group.
     * @param string|array $filters String filter alias or array of filter aliases.
     * Prepend ? to exclude filter in debug.
     * @return DDM_View_Helper_Assetic
     */
    public function assignFilters($assetGroupName, $filters)
    {
        if (!is_array($filters)) {
            $filters = array($filters);
        }

        $filters = $this->_uniqueFilterAliases($filters);

        $assetGroup = $this->_getAssetGroup($assetGroupName);
        $filterManager = $this->_getFilterManager();

        $debug = $this->_isDebug($assetGroupName);
        foreach ($filters as $alias) {
            // a tiny bit of work since we can't use the factory for this ...
            if ('?' == substr($alias, 0, 1)) {
                if ($debug) {
                    continue;
                }
                $alias = substr($alias, 1);
            }

            $this->defineFilter($alias);
            $filter = $filterManager->get($this->_cleanFilterAlias($alias));
            $assetGroup->ensureFilter($filter);
        }

        return $this;
    }

    /**
     * Returns a URL with the CSS extension
     *
     * @param string $assetGroupName
     *
     * @return string
     */
    public function getCssUrl($assetGroupName)
    {
        return $this->_getUrl($assetGroupName, 'css');
    }

    /**
     * Returns a URL with JS extension
     *
     * @param string $assetGroupName
     *
     * @return string
     */
    public function getJsUrl($assetGroupName)
    {
        return $this->_getUrl($assetGroupName, 'js');
    }

    /**
     * Returns compiled url for specified asset group.
     *
     * @param string $assetGroupName Name of asset group.
     * @param string $extension
     * @return string
     */
    protected function _getUrl($assetGroupName, $extension)
    {
        $this->_writeAssetGroup($assetGroupName, $extension);
        $targetPath = $this->_getAssetGroup($assetGroupName)->getTargetPath();
        return $this->_getConfig('baseUrl') . '/' . $targetPath;
    }

    /**
     * Returns internal FilterManager instance.
     *
     * @return FilterManager FilterManager instance.
     */
    protected function _getFilterManager()
    {
        if (!isset($this->_filterManager)) {
            $this->_filterManager = new FilterManager();
        }

        return $this->_filterManager;
    }

    /**
     * Returns internal AssetFactory instance.
     *
     * @return AssetFactory
     */
    protected function _getAssetFactory()
    {
        if (!isset($this->_assetFactory)) {
            $root = $this->_getConfig('sourcePath');
            $debug = $this->_getConfig('debug');
            $assetFactory = new AssetFactory($root, $debug);
            $assetFactory->setFilterManager($this->_getFilterManager());
            $assetFactory->setAssetManager($this->_getAssetManager());
            $this->_assetFactory = $assetFactory;
        }

        return $this->_assetFactory;
    }

    /**
     * Returns asset group.
     *
     * @param string $assetGroupName Name of asset group.
     * @return AssetCollection
     */
    protected function _getAssetGroup($assetGroupName)
    {
        $assetManager = $this->_getAssetManager();

        if ($assetManager->has($assetGroupName)) {
            $assetGroup = $assetManager->get($assetGroupName);
        } else {
            $emptyConfig = $this->_emptyConfig();
            $config = $this->_getConfig('asset')->get($assetGroupName, $emptyConfig);
            $options = $config->get('options', $emptyConfig)->toArray();
            $filters = $this->_uniqueFilterAliases(array_reverse($config->get('filters', $emptyConfig)->toArray()));

            if (!isset($options['debug'])) {
                $options['debug'] = $this->_getConfig('debug');
            }

            $debug = $options['debug'];

            foreach ($filters as $alias) {
                if ($debug && '?' == substr($alias, 0, 1)) {
                    continue;
                }
                $this->defineFilter($alias);
            }

            $assetGroup = $this->_getAssetFactory()->createAsset(array(), $this->_cleanFilterAliases($filters), $options);
            $assetManager->set($assetGroupName, $assetGroup);
        }

        return $assetGroup;
    }

    /**
     * Returns internal AssetManager instance.
     *
     * @return AssetManager
     */
    protected function _getAssetManager()
    {
        if (!isset($this->_assetManager)) {
            $this->_assetManager = new AssetManager();
        }

        return $this->_assetManager;
    }

    /**
     * Returns internal AssetWriter instance.
     *
     * @return AssetWriter
     */
    protected function _getAssetWriter()
    {
        if (!isset($this->_assetWriter)) {
            $this->_assetWriter = new AssetWriter($this->_getConfig('writePath'));
        }

        return $this->_assetWriter;
    }

    /**
     * Writes an asset group.
     *
     * @param string $assetGroupName
     * @param string $extension
     * @return null
     */
    protected function _writeAssetGroup($assetGroupName, $extension)
    {
        $debug = $this->_isDebug($assetGroupName);
        if (!$debug && isset($this->_wroteAssetGroup[$assetGroupName][$extension])) {
            return;
        }
        $this->_wroteAssetGroup[$assetGroupName][$extension] = true;

        $assetGroup = $this->_getAssetGroup($assetGroupName);
        $targetPath = $this->_generateAssetGroupHash($assetGroup) . '.' . $extension;
        $assetGroup->setTargetPath($targetPath);

        $filename = $this->_getConfig('writePath') . '/' . $targetPath;
        if (!$debug && file_exists($filename)) {
            return;
        }
        $this->_getAssetWriter()->writeAsset($assetGroup);
    }

    /**
     * Generates hash for asset group used in setTargetPath().
     *
     * @param AssetCollection $assetGroup
     * @return string
     */
    protected function _generateAssetGroupHash(AssetCollection $assetGroup)
    {
        $hashParts = array(serialize($assetGroup), $assetGroup->getLastModified());
        $hash = substr(md5(implode('', $hashParts)), 0, 7);
        return $hash;
    }

    /**
     * Cleans filter aliases to be compatible with Assetic check_name() in FilterManager
     *
     * @param array|string $aliases
     * @return array|string
     */
    protected function _cleanFilterAliases($aliases)
    {
        if (!is_array($aliases)) {
            return $this->_cleanFilterAlias($aliases);
        }

        $cleanAliases = array();

        foreach ($aliases as $alias) {
            $cleanAliases[] = $this->_cleanFilterAlias($alias);
        }

        return $cleanAliases;
    }

    /**
     * Cleans filter alias to be compatible with Assetic check_name() in FilterManager
     *
     * @param string $alias
     * @return string
     */
    protected function _cleanFilterAlias($alias)
    {
        return str_replace('\\', '', $alias);
    }

    /**
     * Removes duplicate filters aliases made by merging defaults and configs.
     * Counts items with and without debug flag (?) as duplicates.
     *
     * @param array $filters
     * @return array
     */
    protected function _uniqueFilterAliases(array $aliases)
    {
        $found = array();
        $uniques = array();

        foreach ($aliases as $alias) {
            preg_match('/^([\?]{0,1})(.*?)$/', $alias, $matches);
            list($fullMatch, $flag, $search) = $matches;

            if (in_array($search, $found)) {
                continue;
            }

            $found[] = $search;
            $uniques[] = $alias;
        }

        return $uniques;
    }

    /**
     * Retuns an array of bin paths.
     *
     * @param array|string $bins Names of bin commands.
     * @return array
     */
    protected function _getBinPaths($bins)
    {
        if (!is_array($bins)) {
            $bins = array($bins);
        }

        foreach ($bins as $bin) {
            $binPaths[$bin] = $this->_getBinPath($bin);
        }

        return $binPaths;
    }

    /**
     * Returns bin path.
     *
     * @param string $bin
     * @return string|null
     */
    protected function _getBinPath($bin) {
        if (!isset($this->_binPaths[$bin])) {
            $this->_binPaths[$bin] = $this->_which($bin);
        }

        return $this->_binPaths[$bin];
    }

    /**
     * Returns bin path found using `which` command.
     *
     * @param string $bin
     * @return string|null
     */
    protected function _which($bin)
    {
        /* if $status is ...
         *
        *  0 means good, $binPath found
        *  1 means $binPath not found
        *  127 means `which` command not available on system
        *
        * could have thrown different exceptions, but just return $binPath or null
        */

        $binPath = exec('which ' . $bin, $output, $status);
        return ($status === 0 && file_exists($binPath)) ? $binPath : null;
    }

    /**
     * Returns debug flag.
     *
     * @param string|null $assetGroupName
     * @return boolean
     */
    protected function _isDebug($assetGroupName = null)
    {
        if (is_null($assetGroupName)) {
            return $this->_getConfig('debug');
        }

        $emptyConfig = $this->_emptyConfig();
        return $this->_getConfig('asset')
            ->get($assetGroupName, $emptyConfig)
            ->get('options', $emptyConfig)
            ->get('debug', $this->_getConfig('debug'));
    }

    /**
     * Returns an empty Zend_Config instance.
     *
     * @return Zend_Config
     */
    protected function _emptyConfig()
    {
        if (!isset($this->_emptyConfig)) {
            $this->_emptyConfig = new Zend_Config(array());
        }

        return $this->_emptyConfig;
    }

    /**
     * Reads application config file.
     *
     * @return Zend_Config
     */
    protected function _readConfig()
    {
        $configFile = APPLICATION_PATH . '/configs/application.ini'; // TODO allow custom config file?
        $config = new Zend_Config_Ini($configFile, APPLICATION_ENV);
        $config = $config->get('assetic', $this->_emptyConfig());
        return $config;
    }

    /**
     * Returns Assetic config.
     *
     * @param string $key Optional config key.
     * @return Zend_Config
     */
    protected function _getConfig($key = null)
    {
        if (!isset($this->_config)) {
            $config = $this->_readConfig();
            $defaults = $this->_getDefaults();
            $this->_config = $defaults->merge($config);
            $this->_config->setReadOnly();
        }

        return ($key !== null) ? $this->_config->get($key, $this->_emptyConfig()) : $this->_config;
    }

    /**
     * Returns Assetic helper default configs. Called by _getConfig().
     *
     * @return Zend_Config
     */
    protected function _getDefaults()
    {
        $config = $this->_readConfig();
        $emptyConfig = $this->_emptyConfig();

        // general
        $defaults['sourcePath'] = APPLICATION_PATH . '/../public';
        $defaults['writePath'] = APPLICATION_PATH . '/../public/assetCache';
        $defaults['baseUrl'] = '/assetCache';
        $defaults['debug'] = $config->get('debug', false);

        // filter
        $binPaths = $this->_getBinPaths(array('sass', 'compass'));
        $defaults['filter']['Sass\Scss'] = array(
            'arguments' => array($binPaths['sass']),
            'compass' => $binPaths['compass'],
        );

        if ($defaults['debug']) {
            $debugInfo = $config->get('filter', $emptyConfig)->get('Sass\Scss', $emptyConfig)->get('debugInfo', true);
            $defaults['filter']['Sass\Scss']['debugInfo'] = $debugInfo;
        }

        return new Zend_Config($defaults, true);
    }
}
