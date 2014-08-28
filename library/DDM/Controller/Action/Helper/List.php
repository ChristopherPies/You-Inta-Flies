<?php
/**
 * List Action Controller Helper
 * @author abunker
 *
 * This should be moved to DC. Even in DC we plan to move away from it.
 * A controller helper isn't the right place for list logic.
 * See Listerine instead.
 */
class DDM_Controller_Action_Helper_List extends Zend_Controller_Action_Helper_Abstract
{
    public $pluginLoader;

    protected $request;
    protected $controller;
    protected $controllerName;
    protected $actionName;

    protected $modelName;
    protected $models = array();
    protected $select = null;
    protected $callback = null;

    protected $class = '';
    protected $views = array();
    protected $view;
    protected $viewRenderer;
    protected $viewScript;
    protected $viewDefault = true;
    protected $searchOnEmpty = true;
    protected $countTerms = 0;

    protected $didRender = 0;

    protected $noRefresh = false;

    private $_filter;

    protected static $cssCount = 1;

    protected $db;

    /**
     * Constructor
     *
     */
    public function __construct($db = null)
    {
        $this->pluginLoader = new Zend_Loader_PluginLoader();
        $task = new Models_DS_Task();
        if($db === null) {
            $db = $task->getAdapter();
        }
        $this->db = $db;
    }

    /**
     * Helper function
     *
     * @return DDM_Helper_List
     */
    public function direct()
    {
        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see library/Zend/Controller/Action/Helper/Zend_Controller_Action_Helper_Abstract::preDispatch()
     */
    public function preDispatch()
    {

    }

    /**
     * (non-PHPdoc)
     * @see library/Zend/Controller/Action/Helper/Zend_Controller_Action_Helper_Abstract::postDispatch()
     */
    public function postDispatch()
    {
        $this->render();
    }

    /**
     * (non-PHPdoc)
     * @see library/Zend/Controller/Action/Helper/Zend_Controller_Action_Helper_Abstract::init()
     */
    public function init()
    {
        $this->request = $this->getRequest();
        $this->controller = $this->getActionController();
        $this->controllerName = $this->request->getControllerName();
        $this->actionName = $this->request->getActionName();

        $this->views = array(
            'body' => '/list/body',
            'tail' => '/list/tail'
        );
        $this->view = $this->controller->view;
        $this->view->pages = null;
        $this->view->data = array();
        $this->view->link = $this->controllerName . '/edit/id/:id';
        $this->view->url = '/' . $this->controllerName . '/' . $this->actionName;
        $this->view->params = array(
            'user' => array(
            	'id' => $this->controller->getUserId(),
            	'roles' => $this->controller->getUserRoles()
        ));
        $this->viewRenderer = $this->controller->getViewRenderer();
        $this->viewScript = $this->viewRenderer->getViewScript();
        $this->viewScript = str_replace('.tpl', '', $this->viewScript);
        $this->viewRenderer->setNoRender(true);
        $this->view->id = 'list_' . self::$cssCount++;

        if ($this->request->getParam('format') == null) {
            $this->request->setParam('format', 'html');
        }
        if ($this->request->getParam('page') == null) {
            $this->request->setParam('page', '0');
        }
        if ($this->request->getParam('limit') == null) {
            $this->request->setParam('limit', '20');
        }
        $this->view->headScript()->appendFile( noCacheFile('/js/lib/Helper/list.js') );
        $this->view->headScript()->appendFile( noCacheFile('/js/lib/jQueryPlugins/jquery.ba-throttle-debounce.min.js') );
    }

    public function setSearchOnEmpty($bool)
    {
        $this->searchOnEmpty = (bool) $bool;
    }

    /**
     * Specify the Model to use
     * - you can pass a reference to a model object as $value
     *
     * @param model_object|string $value
     * @return DDM_Helper_List
     */
    public function setModel($value)
    {
        if (is_object($value)) {
            $this->modelName = get_class($value);
            $this->models[$this->modelName] = $value;
        } else {
            $this->modelName = $value;
        }
        return $this;
    }

    /**
     * Set the base url for pagenation links
     *
     * @param string $url
     * @return DDM_Helper_List
     */
    public function setUrl( $url )
    {
    	$this->view->url = $url;
		return $this;
    }

    /**
     * Set a select object or provide a model method name to get select object
     *
     * @param Zend_Db_Select|string $select
     * @param array $params (optional) If it isn't set, then the page request params are sent as $params
     * @param bool $override (optional) Override the default params
     * @return DDM_Helper_List
     */
    public function setSelect($select, $params = null, $override = false)
    {
    	if (is_object($select)) {
    		$this->select = $select;
    	} else if (method_exists($this->loadModel(), $select)) {
    	    $this->select = $this->loadModel()->$select($this->getParams($params, $override));
    	}
    	return $this;
    }

    /**
     * Get the list parameters for the select statement and the view
     * @param array $params (optional)
     * @param bool $override (optional)
     */
    private function getParams($params = null, $override = false)
    {
        if ($override) {
            return $params;
        }
    	return array(
    		'user' => array(
            	'id' => $this->controller->getUserId(),
            	'roles' => $this->controller->getUserRoles()),
    	    'request' => $this->request->getParams(),
    	    'config' => $params
    	);
    }

    /**
     * Set the value of a select param
     *
     * @param string $name
     * @param unknown_type $value
     * @return DDM_Helper_List
     */
    public function setParam($name, $value)
    {
        $this->view->params[$name] = $value;
        return $this;
    }

    /**
     * Set SELECT limit
     *
     * @param int $limit Items Per Page
     * @return DDM_Helper_list
     */
    public function setLimit($limit = 0)
    {
        $this->request->setParam('limit', $limit);
        return $this;
    }

    /**
     * Set form object by name or object reference
     *
     * @param DDM_Form | string $form
     * @param string $class
     * @return DDM_Helper_List
     */
    public function setForm($form, $class="listForm")
    {
        if (is_string($form)) {
            $form = new $form();
        }
        if( $class ) {
			$form->addClass($class);
        }
        $form->process($this->request);
        $this->view->form = $form;

        return $this;
    }

    /**
     * Set a view script
     *
     * - If you don't provide a label, provided script becomes default
     * - Default labels are 'head', 'body', 'tail'.
     * - Additional labels can be inserted after the body.
     *
     * @param strint $script
     * @param string $tag (optional) block label (head|body|tail)
     * @return DDM_Helper_List
     */
    public function setView($script, $tag = 'body')
    {
        if (!$script) {
            if (array_key_exists($tag, $this->views)) {
                unset($this->views[$tag]);
            }
        } else {
            $this->views[$tag] = $script;
            $this->viewDefault = false;
        }
        return $this;
    }

    /**
     * Search on fields, only if the term is set
     *
     * @param array $termsAndFields
     * @param string start
     * @param string end
     * @param int $minLength
     * @return DDM_Helper_List
     */
    public function setSearchIf( $termsAndFields, $start = '%', $end = '%',  $minLength = 3) {

    	if( count($termsAndFields) ) {

			$where = '';
			$wordCount = 0;

			foreach($termsAndFields as $term => $field) {

				$termValueOrig = trim($this->request->getParam($term));

				if( !$termValueOrig ) {
					continue;
				}
				$this->countTerms++;

				$words = preg_split('/ /', $termValueOrig);
				$words = $termValueOrig;

				//echo "look for $termValueOrig in ". ppr($field);

				 // while there are quotes left, make phrases out of the chunks.
				$phrases = array();
	            while ( ($first = strpos($words, '"')) !== false ) {

	            	$second = strpos($words, '"', $first+1);
	            	// if we can't find a match to the quote - rest the words and bail, they only gave us one quote
	            	if( !$second ) {
	            		$words = preg_replace("/\W/", ' ', strtolower($origWords));
	            		$phrases = null;
	            		break;
	            	}

	            	// add each phrase to phrases, remove it from words
                    $phrases[] = substr($words, ($first + 1), ($second - 1) );
	            	$words = trim(substr($words, ($second + 1) ));

	            }

	            // split any words that are not phrases
                if( strlen($words) ) {
                    $words = explode(' ', $words);
                } else {
                    $words = array();
                }

		        // merge in phrases if they exist
		        if( count($phrases) ) {
		        	$words = array_merge($words, $phrases);
		        }

				foreach($words as $termValue) {

					if( !is_numeric($termValue) && strlen($termValue) < $minLength ) {
						continue;
					}

					if($wordCount++) {
						// each term must have a match
						$where .= ' AND ';
					}

					// field could be a single field or an array that we OR together
					if( is_array($field) ) {
						$fieldCount = 0;
						$where .= '(';
						foreach($field as $f) {
							if($fieldCount++) {
								$where .= ' OR ';
							}
							$where .= "{$f} like ". $this->db->quote( $start . $termValue . $end);
						}
						$where .= ')';
					} else {
						if( is_numeric($termValue) ) {
							$where .= "{$field} = {$termValue}";
						} else {
							$where .= "{$field} like ". $this->db->quote( $start . $termValue . $end);
						}

					}
				}
			}

			$where = str_replace(')(', ') OR (', $where);

			if( $where != '') {
				$this->select->where($where);
				if( $this->request->getParam('debugsearch') != null ) {
					echo $this->beautifySQL($this->select); exit;
				}
			}
		}
        return $this;
    }

    /**
     * Add full-text search to a SELECT statement
     *
     * @param string $name
     * @param array $fields
     * @param boolean $wildcardStart
     * @param boolean $wildcardEnd
     * @param int $minLength
     * @return DDM_Helper_List
    */
    public function setSearch($fields, $wildcardEnd = true, $wildcardStart = false, $minLength = 3 )
    {
    	$phrases = null;
		$origWords = $words = trim($this->request->getParam('search'));

        if (is_array($fields) && ($words != null) && trim($words)) {
            $words = preg_replace("/\s/", ' ', strtolower($words));
            // while there are quotes left, make phrases out of the chunks.
            while ( ($first = strpos($words, '"')) !== false ) {

            	$second = strpos($words, '"', $first+1);
            	// if we can't find a match to the quote - rest the words and bail, they only gave us one quote
            	if( !$second ) {
            		$words = preg_replace("/\W/", ' ', strtolower($origWords));
            		$phrases = null;
            		break;
            	}

            	// add each phrase to phrases, remove it from words
                $phrases[] = substr($words, ($first + 1), ($second - 1) );
            	$words = trim(substr($words, ($second + 1) ));

            }

            // split any words that are not phrases
            if( strlen($words) ) {
                $words = explode(' ', $words);
            } else {
            	$words = array();
            }
            // merge in phrases if they exist
            if( count($phrases) ) {
            	$words = array_merge($words, $phrases);
            }

            $this->countTerms = count($words);

            $start = $wildcardStart ? '%' : '';
            $end = $wildcardEnd ? '%' : '';
            $where = '';
            $wordCount = 0;
            $fieldCount = 0;
            $wheres = '';

	    /*
            given field1 and field2 to search for the terms "jazz" and "jim", one of the fields must contain both terms.
            AND (
            	(field1 like '%jazz%' AND field1 like '%jim%')
			 	OR (field2 like '%jazz%' AND field2 like '%jim%')
			 )
			 // see comment below for and/or
			 AND (
            	(field1 like '%jazz%' OR field1 like '%jim%')
			 	OR (field2 like '%jazz%' OR field2 like '%jim%')
			 )
            */
            foreach ($fields as $field) {

            	$wordCount = 0;
            	$wheres = '';

                if (strpos($field, '.') === false) {
                    $field = "`$field`";
                }
                foreach ($words as $word) {
                	// skip short words, unless it is a number (id)
            		if( strlen($word) < $minLength && !is_numeric($word)) {
            			continue(2);
            		}
                    if($wordCount++) {
                        // require each field to have all the terms
                        //$wheres .= ' AND ';
                        // include more results with each term added
                        $wheres .= ' OR ';
                    }
                    $wheres .= "{$field} like ". $this->db->quote( $start . $word . $end);
            	}

            	if( $fieldCount++ ) {
            		$where .= ' OR ';
            	}
				$where .= "($wheres)";

            }

            if ($where) {
                $this->select->where($where);
            }
            if (!array_key_exists('head', $this->views)) {
                $this->views['head'] = '/list/head';
            }
        }
        return $this;
    }

    /**
     * Allows searching on any field using any operator, especially useful for comparisons
     *
     * @param array $fields Array of the form:
     * 	array(
     * 		array($field, $operator, $param[, $callback]),
     * 		array($field, $operator, $param[, $callback])
     *  );
     *
     *  Note that $param must be a single form field name, or an array of form field names in the case of the 'IN' and 'BETWEEN' operators.
     *  If $callback is given then it will be called and passed the value of each form element in the $param array.
     *
     *  EXAMPLES:
     *  	$convertDT = function($d) { return date('Y-m-d H:i:s', strtotime($d)); };
     *      $obj->setSearchCompare(array(
     *          array('first_day', '>=', 'start_date', $convertDT),
     *          array('last_day', '<=', 'end_date', $convertDT)
     *      ));
     *
     *      $obj->setSearchCompare(array(
     *      	array('dbfield', 'BETWEEN', array('form_field1', 'form_field2'))
     *      ));
     *
     *      $obj->setSearchCompare(array(
     *      	array('dbfield', 'IN', array('form_field1', 'form_field2', 'form_field3'))
     *      ));
     *
     * @throws Exception
     * @return DDM_Helper_List
     */
    public function setSearchCompare(array $fields)
    {
        foreach($fields as $field)
        {
            if(!is_array($field) || count($field) < 3) {
                throw new Exception('Each field comparison must be an array in the form: array($field, $operator, $param[, $callback])');
            }

            $fieldName = $field[0];
            $operator = $field[1];
            $param = is_array($field[2]) ? $field[2] : array($field[2]);
            $callback = isset($field[3]) && is_callable($field[3]) ? $field[3] : null;

            $param = array_filter(array_map(array($this->request, 'getParam'), $param), function($arg) { if($arg !== null && $arg !== '') return true; });
            if(empty($param)) {
                return $this;
            }

            if($callback) {
                $param = array_map($callback, $param);
            }

            $where = sprintf('`%s` %s ', $fieldName, $operator);
            switch(strtolower($operator))
            {
                case 'between':
                    if(!is_array($param) || count($param) != 2) {
                        throw new Exception('The BETWEEN operator requires the $param field to be an array with two elements');
                    }
                    $param = array_map(array($this->select->getAdapter(), 'quote'), $param);
                    $this->select->where($where . implode(' AND ', $param));
                    break;
                case 'in':
                    if(!is_array($param) || empty($param)) {
                        throw new Exception('The IN operator requires the $param field to be an array of elements');
                    }
                    $this->select->where($where . '(?)', $param);
                    break;
                default:
                    $this->select->where($where . "?", $param[0]);
            }
        }

        return $this;
    }

    /**
     * Set the row link
     *
     * @param $link
     * @return DDM_Helper_list
     */
    public function setLink($link)
    {
        $this->view->link = $link;
        return $this;
    }

    /**
     *
     * Load a model object
     * @param string $name
     * @return DDM_Db_Table
     */
    public function loadModel($name = '')
    {
        if (!$name) {
            $name = $this->modelName;
        }
        if (!array_key_exists($name, $this->models)) {
            try{
                $this->models[$name] = new $name();
            }catch(Exception $e){
                throw new Exception('Could not instantiate class :' . $name . ' in list helper. Make sure you pass the full class name in an auto-loadable fashion.');
            }
        }
        return $this->models[$name];
    }

    /**
     * Execute the select statement and render the views
     * - You only need to manually execute this function and pass 'true' to it
     * 		if you have called setView(), which overrides the default view, but
     * 		still want to render the default action view.
     *
     * @param bool $enable_default_view Enable the execute of the default action controller view
     * @return DDM_Helper_List
     */
    public function render($enable_default_view = false)
    {
        if ($enable_default_view) {
            $this->viewDefault = $enable_default_view;
        }
        if (!$this->didRender++) {

            if ($this->select == null) {
                $this->setSelect('getSelect');
            }

            // "break" the select, we don't want results in this case
            if( !$this->searchOnEmpty && $this->countTerms == 0) {
                $this->select->where('0=1');
            }

            $paginator = new Zend_Paginator(new Zend_Paginator_Adapter_DbSelect($this->select));
            $paginator->setItemCountPerPage($this->request->getParam('limit', 10));
            $paginator->setCurrentPageNumber($this->request->getParam('page', 1));

            if($this->_filter){
                $paginator->setFilter($this->_filter);
            }

            $this->view->data['data'] = $paginator->getCurrentItems();
            $this->view->data['total'] = $paginator->getTotalItemCount();
            $this->view->pages = $paginator;
            switch ($this->request->getParam('format')) {
                 case 'jsonarray':
                    $this->controller->json( $this->view->data['data']->getArrayCopy() );
                    break;
                case 'json':
                    $this->controller->json($this->view->data);
                    break;
                case 'debug':
                    $this->renderDebug();
                    break;
                case 'partial':
                    $this->renderPartial();
                    break;
                case 'html':
                default:
                    $this->renderHTML();
                    break;
            }
        }
        return $this;
    }

    /**
     * Render the debug view
     *
     */
    private function renderDebug()
    {
        echo "<pre style=\"border: 3px solid #900; padding: 20px; background-color: #fdd; font-size: 18px; font-family: arial;\">\n";
        echo $this->beautifySQL($this->select);
        echo "</pre>\n";
        echo "<pre style=\"border: 3px solid #090; padding: 20px; background-color: #dfd; font-family: arial;\">\n";
        print_r($this->view->data);
        echo "</pre>\n";

        exit;
    }

	/**
	 * Make SQL readable
	 *
	 * @param string $query
	 * @return string
	 */
    private function beautifySQL($query)
    {
        $query = preg_replace("/\s+/", ' ', $query);
        $query = preg_replace("/(DESCRIBE|SELECT| FROM | ON | OUTER JOIN | INNER JOIN | LEFT JOIN | JOIN | WHERE | ORDER BY | AND | OR | GROUP BY | LIMIT )/i", "\n<b style=\"color: #900;\">\$1</b>", $query);
        $query = preg_replace("/( ON | AS )/i", "<span style=\" font-size: 16px; color: #888;\">\$1</span>", $query);
        return trim($query);
    }

    /**
     * Render partial
     *
     */
    private function renderPartial()
    {
        $response = $this->controller->getResponse();
        $this->view->layout()->disableLayout();

        $response->appendBody("<div class=\"list\">");
        if (array_key_exists('body', $this->views)) {
            $response->appendBody("<div class=\"listBody\">");
            $this->controller->render($this->views['body'], null, true);
            $response->appendBody('</div>');
        }
        if (array_key_exists('tail', $this->views)) {
            $response->appendBody("<div class=\"listTail\">");
            $this->controller->render($this->views['tail'], null, true);
            $response->appendBody('</div>');
        }
        $response->appendBody("</div>");
    }

    /**
     * Render the html
     *
     */
    private function renderHTML()
    {
        $response = $this->controller->getResponse();

        $noRefresh = ($this->noRefresh)?'norefresh':'';
        $response->appendBody("<div class=\"list {$noRefresh} {$this->class} \" id=\"{$this->view->id}\" >");

        if (array_key_exists('head', $this->views)) {
            $response->appendBody("<div class=\"listHead\">");
            $this->controller->render($this->views['head'], null, true);
            $response->appendBody('</div>');
            unset($this->views['head']);
        }
        if (array_key_exists('body', $this->views)) {
            $response->appendBody("<div class=\"listBody\">");
            $this->controller->render($this->views['body'], null, true);
            $response->appendBody('</div>');
            unset($this->views['body']);
        }
        if (array_key_exists('tail', $this->views)) {
            $response->appendBody("<div class=\"listTail\">");
            $this->controller->render($this->views['tail'], null, true);
            $response->appendBody('</div>');
            unset($this->views['tail']);
        }
        if (sizeof($this->views)) {
            foreach ($this->views as $tag => $script) {
                $this->controller->render($script, null, true);
            }
        }
        if ($this->viewDefault) {
            $this->controller->render($this->viewScript, null, true);
        }
        $response->appendBody("</div>");
    }
    // -------------------------------------------------------------------------------------------------

    /**
     * Set a filter chain
     *
     * @param Zend_Filter_Interface $filter
     * @return DDM_Helper_List
     */
    public function setFilter($filter){
        $this->_filter = $filter;
        return $this;
    }

    /**
     *
     * Disable auto refreshing
     * @param boolean $noRefresh
     * @return DDM_Helper_List
     */
    public function setNoRefresh($noRefresh = true){
        $this->noRefresh = $noRefresh;
        return $this;
    }

    /**
     *
     * Set class
     * @param string $class
     * @return DDM_Helper_List
     */
    public function setClass($class)
    {
        $this->class = $class;
        return $this;
    }

}
