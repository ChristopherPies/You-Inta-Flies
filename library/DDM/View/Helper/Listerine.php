<?php
/**
 * A list helper to render a head, body and footer.
 * Body data is mapped to column names and may have a closure to format, combine, tweak, etc. the data before being output.
 *
 * TODO
 * - default css, allow for classes to be set before render
 * TODO
 * - if the list is using a form, pagination will only work of the form fields
 *   are set to persist.  We may need to come up with a better way
 *
 */

class DDM_View_Helper_Listerine extends Zend_View_Helper_Abstract {

    /**
     *
     * @var type string
     */
    protected $pageVarName = '';

    /**
     * What do do when data is empty? null = render nothing or set to a template to be used instead of h,b & f.
     *
     * @var string
     */
    protected $noDataTemplate = 'no-data.phtml';

    /**
     * Default head template
     *
     * @var string
     */
    protected $headTemplate = 'header.phtml';

    /**
     * Default body template
     *
     * @var string
     */
    protected $bodyTemplate = 'body.phtml';

    /**
     * Default footer template
     *
     * @var string
     */
    protected $footerTemplate = 'footer.phtml';

    /**
     * Default pagination template
     *
     * @var string
     */
    protected $paginationTemplate = 'pagination.phtml';

    /**
     * Array of data for the header template
     *
     * @var unknown_type
     */
    protected $headerData = null;

    /**
     * Body data
     *
     * @var array | Zend_Db_Table_Rowset
     */
    protected $bodyData = array();

    /**
     * Data for the footer template
     *
     * @var array
     */
    protected $footerData = array();

    /**
     * Map fields to output columns
     *
     * @var array
     */
    protected $map;

    /**
     * Url to fetch data from
     *
     * @var string
     */
    protected $updateUrl = null;

    /**
     * ID for the list
     *
     * @var string
     */
    protected $id = "listerine";

    /**
     * class for the list
     *
     * @var string
     */
    protected $class = 'table-bordered table-striped table cf';

    /**
     * whether to auto update the list
     *
     * @var boolean
     */
    protected $autoUpdate = false;

    /**
     * how often to update the list in seconds
     *
     * @var int
     */
    protected $updateIncrement = 60;

    /**
     * The id of the form this list uses
     * If you pass in a form with the key "form" to headerData
     * the formId will be populated automatically
     *
     * @var string
     */
    protected $formId = null;

    /**
     * Whether to use ajax for pagination rather than reloading the page
     * Note that the url will be changed even when using ajax
     *
     * @var boolean
     */
    protected $ajaxPagination = true;

    /**
     * Whether to include a totals row in at the end of the result set
     * This total row will account for all data in the set regardless of pagination
     *
     * @var boolean
     */
    protected $showTotalsRow = false;

    /**
     * The total row class for the tr
     *
     * @var string
     */
    protected $totalClass = 'listerine_total';

    /**
     * a function to run after the list updates (js)
     *
     * @var string
     */
    protected $updateOnCompleteFunction;

    /**
     * A function to process data before rendering
     * @var array
     */
    protected $preRenderFunction = array();

    /**
     *
     * @var boolean
     */
    protected $csvExportButton = false;


    protected $itemsPerPage = 20;
    protected $currentPage = 1;
    protected $pageRange = 7;
    protected $select;
    /**
     * set after render is called
     *
     * @var int
     */
    protected $totalItemCount;

    /**
     * Whether to display the total results above the list
     *
     * @var boolean
     */
    protected $displayTotalResults = false;

    /**
     * The page range for the paginator class
     *
     * @param int $pageRange
     */
    public function setPageRange($pageRange) {
        $this->pageRange = $pageRange;
    }

    /**
     *
     * @param boolean $csvExportButton
     */
    public function setCsvExportButton($csvExportButton) {
        if(headers_sent()) {
            //TODO we could build in a redirect link the user can pass us if headers are already sent to download stuff
            throw new Exception('You are not able to include a csv export option because headers have already been sent');
        }
        $this->csvExportButton = $csvExportButton;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function getCsvExportButton() {
        return $this->csvExportButton;
    }

    /**
     * set after render is called
     *
     * @return int
     */
    public function getTotalItemCount()
    {
        return $this->totalItemCount;
    }

    /**
     * A View helper to render a list with a header and footer template
     *
     * @return DDM_View_Helper_Listerine
     */
    public function listerine() {
        return $this;
    }


    /**
     * Set an array to map between result field names and output colums. Column may be a string or closure.
     *
     * @param array $map
     * @return DDM_View_Helper_Listerine
     */
    public function setMap($map) {
        $this->map = $map;
        return $this;
    }

    /**
     * Setup a function to run after data is selected, but before render
     * @param string $name
     * @param array $parameters
     * @param object $object
     * @return DDM_View_Helper_Listerine
     */
    public function setPreRenderFunction($name, $dataPosition = 0, $parameters = null, $object = null) {
        $this->preRenderFunction = array(
            'name' => $name,
            'parameters' => $parameters,
            'object' => $object,
            'data_position' => $dataPosition,
        );
        return $this;
    }

    /**
     * Set vars for use in the header template
     * If a form is passed in the formId will be set
     *
     * @param DDM_Db_Select Object | array $data
     * @return DDM_View_Helper_Listerine
     */
    public function setHeaderData($data) {
        $this->headerData = $data;
        if(is_array($data))
        {
            foreach($data as $key=>$value)
            {
                if($key == 'form' && method_exists($value, 'getId'))
                {
                    $this->setFormId($value->getId());
                }
            }
        }
        return $this;
    }

    /**
     * Set vars for use in the footer template
     *
     * @param array $data
     * @return DDM_View_Helper_Listerine
     */
    public function setFooterData($data) {
        $this->footerData = $data;
        return $this;
    }

    /**
     * Set the "no data" template
     *
     * @param array $name
     * @return DDM_View_Helper_Listerine
     */
    public function setNoDataTemplate($name) {
        $this->noDataTemplate = $name;
        return $this;
    }


    /**
     * Set the head template
     *
     * @param array $name
     * @return DDM_View_Helper_Listerine
     */
    public function setHeadTemplate($name) {
        $this->headTemplate = $name;
        return $this;
    }

    /**
     * Set the footer template
     *
     * @param array $name
     * @return DDM_View_Helper_Listerine
     */
    public function setBodyTemplate($name) {
        $this->bodyTemplate = $name;
        return $this;
    }

    /**
     * Set the footer template
     *
     * @param array $name
     * @return DDM_View_Helper_Listerine
     */
    public function setFooterTemplate($name) {
        $this->footerTemplate = $name;
        return $this;
    }

    /**
     * Set URL to fetch data from
     *
     * @param string $url
     * @return DDM_View_Helper_Listerine
     */
    public function setUpdateUrl($url) {
        $this->updateUrl = $url;
        return $this;
    }

    /**
     * Auto update will update your list without you specifying a url
     * by reloading the whole page and replacing the list by id
     * Note that updateUrl will be ignored if this is set to true
     *
     * @param boolean $autoUpdate
     * @return DDM_View_Helper_Listerine
     */
    public function setAutoUpdate($autoUpdate) {
        $this->autoUpdate = $autoUpdate;
        return $this;
    }

    /**
     * A function to run after each list update
     *
     * @param string $jsFunction
     */
    public function setUpdateOnCompleteFunction($jsFunction) {
        $this->updateOnCompleteFunction = $jsFunction;
        return $this;
    }

    /**
     * Sets how often the list will update
     *
     * @param int $increment
     * @return DDM_View_Helper_Listerine
     */
    public function setUpdateIncrement($increment) {
        $this->updateIncrement = $increment;
        return $this;
    }

    /**
     * Gets the update url
     *
     * @return string
     */
    public function getUpdateUrl() {
        return $this->updateUrl;
    }

    /**
     * Gets the update increment
     *
     * @return int
     */
    public function getUpdateIncrement() {
        return $this->updateIncrement;
    }

    /**
     * Gets the auto update value
     *
     * @return boolean
     */
    public function getAutoUpdate() {
        return $this->autoUpdate;
    }

    /**
     * Gets the ajax pagination value
     *
     * @return boolean
     */
    public function getAjaxPagination() {
        return $this->ajaxPagination;
    }

    /**
     * Sets the ajax pagination value
     * Note that even when using ajax the url will be changed
     *
     * @param boolean $ajaxPagination
     * @return DDM_View_Helper_Listerine
     */
    public function setAjaxPagination($ajaxPagination) {
        $this->ajaxPagination = $ajaxPagination;
        return $this;
    }

    /**
     * Set the id for this list
     *
     * @param string $id
     * @return DDM_View_Helper_Listerine
     */
    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    /**
     * Set how many items per page
     *
     * @param int $max
     * @return DDM_View_Helper_Listerine
     */
    public function setItemsPerPage($max) {
        $this->itemsPerPage = (int) $max;
        return $this;
    }

     /**
     * Set current page
     *
     * @param int $page
     * @return DDM_View_Helper_Listerine
     */
    public function setCurrentPage($page) {
        $page = (int) $page;
        if($page < 1) {
            $page = 1;
        }
        $this->currentPage = $page;
        return $this;
    }

    /**
     * Get the name of the "page" var - only set after render
     *
     * @return string
     */
    public function getPageVarName() {
        return $this->pageVarName;
    }

    /**
     * Set the pagination template
     *
     * @param string $template
     * @return DDM_View_Helper_Listerine
     */
    public function setPaginationTemplate($template) {
        $this->paginationTemplate = $template;
        return $this;
    }

    /**
     * Set the class for the list
     *
     * @param string $class
     * @return DDM_View_Helper_Listerine
     */
    public function setClass($class) {
        $this->class = $class;
        return $this;
    }

    /**
     * returns the list's id
     *
     * @return string
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Sets the form id for the form this list uses
     * (Set automatically if you passed a form into the head)
     *
     * @param type $formId
     * @return DDM_View_Helper_Listerine
     */
    public function setFormId($formId) {
        $this->formId = $formId;
        return $this;
    }

    /**
     * returns the form id this list uses as a filter
     *
     * @return string
     */
    public function getFormId() {
        return $this->formId;
    }

    /**
     * sets whether to show a totals row
     *
     * @param boolean $showTotalsRow
     */
    public function setShowTotalsRow($showTotalsRow) {
        $this->showTotalsRow = $showTotalsRow;
        return $this;
    }

    /**
     * Sets the class for the total row (tr)
     *
     * @param string $totalClass
     */
    public function setTotalClass($totalClass) {
        $this->totalClass = $totalClass;
        return $this;
    }

    /**
     * whether to display total above list
     *
     * @param boolean $display
     */
    public function setDisplayTotalResults($display) {
        $this->displayTotalResults = $display;
        return $this;
    }

    /**
     * Process data before rendering
     * @param array $data
     * @return array
     */
    public function preRender($data) {

        // keys: 'name', 'parameters', 'object', 'data_position'
        $name = $this->preRenderFunction['name'];
        $params = array();
        $object = $this->preRenderFunction['object'];

        if ($data instanceof DDM_Db_Select) {
            $this->select = $data;
            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($this->select));
            $paginator->setItemCountPerPage($this->itemsPerPage);
            $paginator->setCurrentPageNumber($this->currentPage);
            $paginator->setPageRange($this->pageRange);
            $count = count($data);
            $data = (array) $paginator->getCurrentItems();
        }

        // slip the data into the params for the function to call before rendering
        if(count($this->preRenderFunction['parameters'])) {
            $count = 0;
            foreach($this->preRenderFunction['parameters'] as $k => $v) {
                if($count++ == $this->preRenderFunction['data_position']) {
                    $params[] = $data;
                }
                $params[] = $v;
            }
        } else {
            $params[] = $data;
        }

        // call the method or function
        if(is_object($object) && $name != '' && method_exists($object, $name)) {
            $data = call_user_func_array(array($object, $name), $params);
        } else if (function_exists($name)) {
            $data = call_user_func_array($name, $params);
        }
        return $data;

    }

    /**
     * Display a list of data
     *
     * @param array $data
     * @param array $map
     * @param string $bodyTemplate
     * @param array $headData
     * @param string $headTemplate
     * @param array $footerData
     * @param string $footerTemplate
     * @return string
     */
    public function render($data, $bodyTemplate = null, $headTemplate = null, $footerTemplate = null) {

        $originalData = $data;

        // more than one list per page means we can't call the var for current page "page"
        $this->pageVarName = $pageVar = $this->id . '_page';
        $this->view->pageVar = $pageVar;
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $this->currentPage = $request->getParam($pageVar, 1);
        $debug = ($request->getParam('format') == 'debug');

        if(!empty($_GET['listerineCsv'])) {
            $this->prepareCsv($data);
        }
        // so far pagination only with a Select, not array of data provided
        $paginator = null;
        if (!$debug && $data instanceof Zend_Db_Select) {
            $this->select = $data;
            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($this->select));
            $paginator->setItemCountPerPage($this->itemsPerPage);
            $paginator->setCurrentPageNumber($this->currentPage);
            $paginator->setPageRange($this->pageRange);
            $data = $paginator->getCurrentItems();
            $count = count($data);

            Zend_View_Helper_PaginationControl::setDefaultViewPartial($this->paginationTemplate);
        } else if (!$debug && is_array($data)) {
            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Array($data));
            $paginator->setItemCountPerPage($this->itemsPerPage);
            $paginator->setCurrentPageNumber($this->currentPage);
            $paginator->setPageRange($this->pageRange);
            $data = $paginator->getCurrentItems();
            $count = count($data);

            Zend_View_Helper_PaginationControl::setDefaultViewPartial($this->paginationTemplate);
        }

        $totalsRow = array();
        if(!$debug && $this->showTotalsRow && !empty($this->map))
        {
            $pages = $paginator->getPages();
            for($i = $pages->first; $i <= $pages->last; $i++)
            {
                $items = $paginator->getItemsByPage($i);
                foreach($items as $item)
                {
                    foreach($this->map as $column)
                    {
                        if(!empty($column['showTotal']) && $column['showTotal'] === true)
                        {
                            if(!isset($totalsRow[$column['label']]))
                            {
                                $totalsRow[$column['label']] = '';
                            }
                            $col = (isset($column['totalColumn'])) ? $column['totalColumn'] : $column['column'];
                            if ($col instanceof Closure) {
                                $eval = array_map($col, array($item));
                                $value = $eval[0];

                            } else {
                                $value = $item[$col];
                            }
                            if(is_numeric($value))
                            {
                                $totalsRow[$column['label']] += $value;
                            }
                        }
                    }
                }
            }
        }

        $this->totalItemCount = 0;
        if(is_object($paginator)) {
            $this->totalItemCount = $paginator->getTotalItemCount();
        }

        // html we'll return
        $return = '';

        // js for ajax
        $this->view->headScript()->appendFile(noCacheFile('/js/lib/Helper/Listerine.js'));
        $this->view->headScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/jquery.ba-throttle-debounce.min.js') );
        $this->view->headScript()->appendFile(noCacheFile('/js/lib/jQueryPlugins/showLoading/js/jquery.showLoading.min.js') );
        $this->view->headLink()->appendStylesheet(noCacheFile('/js/lib/jQueryPlugins/showLoading/css/showLoading.css') );

        // TODO - this isn't very generic, fix it.
        //$this->view->headLink()->appendStylesheet('/css/bootstrap-table.css');

        // add our script path
        $this->view->addScriptPath(PROJECT_ROOT . '/library/DDM/View/Helper/Listerine/');

        if($bodyTemplate) {
            $this->bodyTemplate = $bodyTemplate;
        }
        if($headTemplate) {
            $this->headTemplate = $headTemplate;
        }
        if($footerTemplate) {
            $this->footerTemplate = $footerTemplate;
        }

        // massage the data before handing it off to the templates
        if(count($this->preRenderFunction)) {
            $data = $this->preRender($data);
        }

        $this->bodyData = $data;
        $count = count($this->bodyData);

        //used by Listerine.js to update the list
        $return .= '
            <form style="margin:0px;">
                <input type="hidden" data-for="'.$this->id.'" class="listerineUpdateUrl" value="'.$this->updateUrl.'" />
                <input type="hidden" data-for="'.$this->id.'" class="listerineAutoUpdate" value="'.$this->autoUpdate.'" />
                <input type="hidden" data-for="'.$this->id.'" class="listerineUpdateIncrement" value="'.$this->updateIncrement.'" />
                <input type="hidden" data-for="'.$this->id.'" class="listerineFormId" value="'.$this->formId.'" />
                <input type="hidden" data-for="'.$this->id.'" class="onReloadListerineFunction" value="'.$this->updateOnCompleteFunction.'" />
            </form>
            ';

        $keys = null;
        if((is_array($this->bodyData) && isset($this->bodyData[0]) && is_array($this->bodyData[0])) || ($this->bodyData instanceof ArrayIterator && isset($this->bodyData[0]) && is_array($this->bodyData[0]))) {
        	$keys = array_keys($this->bodyData[0]);
        }
        if($debug) {
            if(is_array($originalData))
            {
                $originalData = print_r($originalData,true);
            }
            else if($originalData instanceof DDM_Db_Select) {
                $originalData = $originalData->beautifySQL();
            }
            return '<pre class="alert alert-error" style="display:block; clear:both;">'.$originalData.'</pre>';
        } else {
            if($count) {

                if ($this->headTemplate != '') {
                    $return .= $this->view->partial($this->headTemplate, array(
                        'data' => $this->headerData,
                        'map' => $this->map,
                        'id' => $this->id,
                        'class' => $this->class,
                        'paginator' => $paginator,
                        'keys' => $keys,
                        'displayTotalResults' => $this->displayTotalResults,
                        'totalItemCount' => $this->totalItemCount,
                        'csvExportButton' => $this->csvExportButton
                    ));

                }

                if ($this->bodyData instanceof Zend_Db_Table_Rowset || is_array($this->bodyData) || $this->bodyData instanceof ArrayIterator ) {
                    foreach($data as $row) {
                        $return .= $this->view->partial($this->bodyTemplate, array(
                            'data' => $row,
                            'map' => $this->map,
                            'paginator' => $paginator,
                            'headerData' => $this->headerData
                        ));
                    }
                }


                if ($this->footerTemplate != '') {
                    $return .= $this->view->partial($this->footerTemplate, array(
                        'data' => $this->footerData,
                        'paginator' => $paginator,
                        'pageVar' => $pageVar,
                        'map' => $this->map,
                        'showTotalsRow' => $this->showTotalsRow,
                        'totalsRow' => $totalsRow,
                        'totalClass' => $this->totalClass
                    ));
                }

            } else {
                if($this->noDataTemplate != '') {
                    $return .= $this->view->partial($this->noDataTemplate, array(
                        'data' => $this->headerData,
                        'id' => $this->id,
                        'displayTotalResults' => $this->displayTotalResults,
                        'totalItemCount' => $this->totalItemCount
                    ));
                }
            }
        }

        return $return;

    }

    /**
     * prepares the data for csv export
     *
     * @param mixed $data
     */
    public function prepareCsv($data) {
        $canCsv = false;
        $obStatus = ob_get_status();
        if(!empty($obStatus)) { //output buffering - end and begin csv
            ob_end_clean();
        }
        if(!headers_sent()) {
            $canCsv = true;
        }
        if($canCsv) {
            if($data instanceof Zend_Db_Select) {
                $DDM_Db_Table = new DDM_Db_Table();
                $data = $DDM_Db_Table->getAdapter()->fetchAll($data);
            }
            // massage the data before handing it off to the templates
            if(count($this->preRenderFunction)) {
                $data = $this->preRender($data);
            }
            $csvData = array();
            if(!empty($this->map)) {
                foreach($data as $index => $row) {
                    $csvData[$index] = array();
                    foreach($this->map as $column) {
                        $col = $column['column'];
                        if ($col instanceof Closure) {
                            $eval = array_map($col, array($row));
                            $csvData[$index][$column['label']] = $eval[0];

                        } else {
                            $val = '';
                            if(!empty($row[$col])) {
                                $val = $row[$col];
                            }
                            $csvData[$index][$column['label']] = $val;
                        }
                    }
                }
            } else {
                $csvData = $data;
            }
            foreach($csvData as &$row) {
                foreach($row as &$value) {

                    $value = trim(html_entity_decode(strip_tags(str_replace(array('<br>', '<br />', '&nbsp;'), array("\n", "\n", " "), $value)), ENT_QUOTES));
                }
                unset($value);
            }
            unset($row);
            $id = ($this->getId()) ? $this->getId() : 'list';
            $this->makeCsv($csvData, $id);
        }
    }

    /**
     * Generates a CSV file and presents it to the user for download
     *
     * @param array     $data
     * @param string    $name The name to give the CSV file
     */
    public function makeCsv($data, $name)
    {
        $fp = fopen("php://output",'w');
        fputcsv($fp, array_keys($data[0]));

        header("Content-type: text/csv");
        header("Cache-Control: no-store, no-cache");
        header('Content-Disposition: attachment; filename="' . date('Y-m-d') . '_' . $name . '_report.csv"');

        foreach($data as $index => $row)
        {
            fputcsv($fp, $row);
        }

        exit;
    }

}