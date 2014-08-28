<?php

require_once ('Zend/View/Helper/Abstract.php');

/**
 * Create the tags necessary for rendering new Google DFP/XFP ads. Requires a
 * network ID, a path, and an ad size. You can set default values in the
 * application.ini using the following keys:
 *
 * dfp.network
 * dfp.path
 * dfp.sizes[]
 *
 * TODO: Add ability to target key-value pairs
 */
class DDM_View_Helper_Dfp extends Zend_View_Helper_Abstract
{
    protected $network;

    protected $path;

    protected $sizes;

    protected $script;

    static protected $count = 0;

    /**
     *
     * @param array $config Override values set in application.ini
     * @return string The Javascript and HTML tags
     */
    public function dfp(array $config = null)
    {
        $this->setDefaultValues();

        // Grab values from application.ini
        $ini = new Zend_Config_Ini(APPLICATION_PATH . '/configs/application.ini', APPLICATION_ENV);
        if ($ini->dfp) {
            if (isset($ini->dfp->network)) {
                $this->network = $ini->dfp->network;
            }

            if (isset($ini->dfp->path)) {
                $this->path = $ini->dfp->path;
            }

            if (isset($ini->dfp->sizes)) {
                $this->sizes = $ini->dfp->sizes->toArray();
            }
        }

        // Set values from config
        if (isset($config['network'])) {
            $this->network = $config['network'];
        }

        if (isset($config['path'])) {
            $this->path = $config['path'];
        }

        if (isset($config['sizes'])) {
            $this->sizes = array_merge($this->sizes, $config['sizes']);
        }

        $divId = $this->generateDivId();

        $sizes = $this->generateSizes();

        // .setTargeting("interests", ["sports", "music", "movies"])
        //googletag.pubads().setTargeting("topic","basketball");

        $this->script = <<< EOF
<div id="$divId">
    <script type="text/javascript">
      googletag.cmd.push(function() {
        googletag.pubads().display('/{$this->network}/{$this->path}', $sizes, "$divId");
      });
    </script>
  </div>
EOF;

        return $this;
    }

    public function setNetwork($network)
    {
        $this->network = $network;
        return $this;
    }

    public function setPath($path)
    {
        $this->path = $path;
        return $this;
    }

    public function addSize($size)
    {
        $this->sizes = array_merge($this->sizes, array($size));
        return $this;
    }

    public function addSizes(array $sizes)
    {
        $this->sizes = array_merge($this->sizes, $sizes);
        return $this;
    }

    public function setSizes(array $sizes)
    {
        $this->sizes = $sizes;
        return $this;
    }

    protected function setDefaultValues()
    {
        $this->setNetwork('');
        $this->setPath('');
        $this->setSizes(array());
        $this->script = '';
    }

    protected function generateDivId()
    {
        $divId = 'div-gtp-ad-' . time() . '-' . self::$count;
        self::$count++;
        return $divId;
    }

    protected function generateSizes()
    {
        $sizeString = '';
        $pieces = array();
        foreach ($this->sizes as $size) {
            list($width, $height) = explode('x', $size);
            $pieces[] = '[' . $width . ', ' . $height . ']';
        }

        $sizeString .= implode(',', $pieces);
        if (count($pieces) > 1) {
            $sizeString = '[' . $sizeString . ']';
        }

        return $sizeString;
    }

    protected function render()
    {
        return $this->script;
    }

    public function __toString()
    {
        return $this->render();
    }
}