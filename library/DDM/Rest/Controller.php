<?php

abstract class DDM_Rest_Controller extends DDM_Controller_Action
{
    protected $requestStartTime;
    protected $responseFormat;
    protected $response = array('error' => null, 'warning' => null, 'notice' => null, 'data' => null, 'timestamp' => null);
    protected $verbosity;
    protected $returnType;  // Valid values are 'single' or 'list'

    protected $paramMap = array(); // Map request parameters to fields
    protected $fieldMap = array(); // Map fields to response parameters
    protected $orderFields = null;
    protected $requiredFields = array();
    protected $requireIdOnUpdate = true;
    protected $requireIdOnDelete = true;

    protected $id;
    protected $fields = array();
    protected $orderBy = array();
    protected $saveData = array();
    protected $paramTypes = array();
    protected $paramTypeMap = array(
        'd' => 'date',
        'i' => 'int',
        's' => 'string',
        'n' => 'null',
        'b' => 'bool'
    );

    // Setting any of these fields only sets a default - they will be overridden if given in the request
    protected $offset;
    protected $limit;
    protected $page;

    public function __construct(Zend_Controller_Request_Abstract $request,
                                Zend_Controller_Response_Abstract $response,
                                array $invokeArgs = array())
    {
        // Parse url-encoded POST data properly
        if(stripos($request->getHeader('Content-Type'), 'urlencoded') !== false) {
            $_POST = parse_qs(urldecode(file_get_contents('php://input')));
        }

        call_user_func_array('parent::__construct', func_get_args());

        // Make sure the action is set right - Zend doesn't always seem to set this right
        if(in_array($reqMethod = strtolower($request->getParam('_method', '')), array('get', 'post', 'put', 'delete', 'head'))) {
            $request->setActionName($reqMethod);
            $_SERVER['REQUEST_METHOD'] = strtoupper($reqMethod);

            $_POST = array_merge($_REQUEST, $_POST);
        }

        $acceptTypes = explode(',', $request->getHeader('Accept'));
        $requestedWith = strtolower($request->getHeader('X-Requested-With'));
        $this->responseFormat = strtolower($request->getParam('format')) ?: ($requestedWith == 'xmlhttprequest' ? 'json' : null);
        $this->verbosity = $request->getParam('verbosity') ?: 1;

        // Determine response format
        if(!$this->responseFormat) {
            foreach($acceptTypes as $acceptType) {
                if(preg_match('~text/html~', $acceptType)) {
                    $this->responseFormat = 'html';
                }
                elseif(preg_match('~(application/json|text/javascript)~', $acceptType)) {
                    $this->responseFormat = 'json';
                }
                elseif(preg_match('~(application/xml|text/xml)~', $acceptType)) {
                    $this->responseFormat = 'xml';
                }

                if($this->responseFormat) {
                    break;
                }
            }

            if(!$this->responseFormat) {
                $this->responseFormat = 'html';
            }
        }

        switch($this->responseFormat) {
            case 'jsonp':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->layout()->disableLayout();

                $request->setParam('format', 'jsonp');

                $this->_response->setHeader('Content-Type', 'application/javascript', true);
                break;
            case 'json':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->layout()->disableLayout();

                $request->setParam('format', 'json');

                $this->_response->setHeader('Content-Type', 'application/json', true);
                break;
            case 'xml':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->layout()->disableLayout();

                $request->setParam('format', 'xml');

                $this->_response->setHeader('Content-Type', 'text/xml', true);
                break;
            case 'debug':
                $this->_helper->viewRenderer->setNoRender(true);
                $this->view->layout()->disableLayout();
                break;
        }


        // Set the field map
        $this->fieldMap = $this->getFieldMap();
    }

    public function getFieldMap()
    {
        return new DDM_FieldMap();
    }

    public function preDispatch()
    {
        parent::preDispatch();

        $this->convertParams();

        $this->id = $this->getArrayParam('id');
        if(count($this->id) == 1) {
            $this->id = $this->id[0];
        }

        $this->returnType = $this->_request->getParam('returnType') ?: ( ($this->id && !is_array($this->id) ) || $this->_request->isPut() || $this->_request->isPost() ? 'single' : 'list');
        $fields = $this->_request->getParam('idsOnly') !== null ? array('id') : ($this->getArrayParam('fields') ?: array_keys($this->fieldMap->getAliases()));
        $this->fields = $this->fieldMap->mapFieldNames($fields, 'aliases', true);

        $this->paramMap = $this->fieldMap->mapFields($this->_request->getParams(), 'aliases');

        // Don't allow setting the ID on a POST request - it is determined by the server
        if($this->_request->isPost() && $this->id) {
            $this->badRequest('You cannot set the ID on a POST request');
        }

        // Require ID on PUT requests
        if($this->requireIdOnUpdate && $this->_request->isPut() && !$this->id) {
            $this->badRequest('An ID is required on a PUT request');
        }

        // Require ID on DELETE requests
        if($this->requireIdOnDelete && $this->_request->isDelete() && !$this->id) {
            $this->badRequest('An ID is required on a DELETE request');
        }

        // Prepare saveData if this is a POST or PUT
        if($this->_request->isPost() || $this->_request->isPut()) {
            $this->saveData = $this->getSaveData();
            $this->saveData['id'] = $this->id;
        }

        // Parse and map 'orderBy'
        $orderBy = $this->getArrayParam('orderBy') ?: (is_array($this->orderBy) ? $this->orderBy : array());
        if($this->orderFields === null) {
            $orderBy = array();
        }

        $this->orderBy = array();
        $invalidOrderFields = array();
        foreach($orderBy as $field) {
            $matches = array();
            preg_match('/([\w]*)(?:[:](asc|desc))?/i', $field, $matches);
            $field = $matches[1];
            $dir = isset($matches[2]) ? strtoupper($matches[2]) : 'ASC';

            if(in_array($field, $this->orderFields)) {
                $this->orderBy[] = $this->fieldMap->getFieldFromAlias($field) . ' ' . $dir;
            }
            else {
                $invalidOrderFields[] = $field;
            }
        }
        if($invalidOrderFields) {
            $this->invalidOrderFields($invalidOrderFields);
        }
        $this->_request->setParam('orderBy', null);

        // Populate offset and limit fields, if given -- page can be used to calculate the offset, if a limit is given
        $limit = $this->_request->getParam('limit');
        if(is_numeric($limit)) {
            $this->limit = (int)$limit;
        } elseif($limit) {
            $this->invalidLimit($limit);
        }

        $this->page = $page = $this->_request->getParam('page');
        if(is_numeric($page)) {
            if($this->limit) {
                $this->offset = (int)$page * $this->limit;
            } else {
                $this->invalidLimit(null);
            }
        } elseif($page) {
            $this->invalidPage($page);
        }

        $offset = $this->_request->getParam('offset');
        if(is_numeric($offset)) {
            $this->offset = (int)$offset;
        } elseif($offset) {
            $this->invalidOffset($offset);
        }
    }

    public function dispatch($action)
    {
        $this->requestStartTime = microtime(true);

        try {
            return parent::dispatch($action);
        } catch(DDM_Db_Exception_Validation $e) {
            $this->validationErrors($this->fieldMap->mapFields($e->getValidationErrors(), 'aliases', true));
        } catch(Exception $e) {
            if($e instanceof DDM_Rest_Exception_BadRequest) {
                $this->badRequest($e->getMessage());
            } else {
                $this->serverError($e->getMessage(), true);
            }
        }
    }

    /**
     * Automatically render $this->responseData to JSON
     */
    public function postDispatch()
    {
        // Fix response data string encoding
        $this->response = DDM_Classes_Encoding::toUTF8($this->response);

        if($this->responseFormat == 'debug') {
            $this->response['runtime'] = microtime(true) - $this->requestStartTime;
        }

        // For the client to make sure it always has the latest data it is important that it knows that the read request didn't
        // even begin reading until after a write request has completely finished writing. Thus, GET (read) requests must be
        // timestamped before the actual read begins (start of the request) while POST, PUT, and DELETE (write) requests must
        // be timestamped after the entire write has finished (end of the request). This allows a client to avoid race conditions
        // when reading data after an update or insert.
        $this->response['timestamp'] = $this->_request->isGet() ? $this->requestStartTime : microtime(true);

        if($this->verbosity < 1) {
            unset($this->response['error']);
        }
        if($this->verbosity < 2) {
            unset($this->response['warning']);
        }
        if($this->verbosity < 3) {
            unset($this->response['notice']);
        }

        // Map fields
        if(is_array($this->response['data'])) {
            if($this->returnType == 'single') {
                $this->response['data'] = $this->fieldMap->mapFields($this->response['data'], 'aliases', true);
            }
            elseif($this->returnType == 'list') {
                foreach($this->response['data'] as $i => $data) {
                    $this->response['data'][$i] = $this->fieldMap->mapFields($data, 'aliases', true);
                }

                if($this->_request->getParam('idsOnly') !== null) {
                    $ids = array();
                    foreach($this->response['data'] as $i => $data) {
                        $ids[] = $data['id'];
                    }
                    $this->response['data'] = $ids;
                }
            }
        }

        // Format response based on output type
        if($this->responseFormat == 'json' || $this->responseFormat == 'jsonp') {
            if($this->_request->getParam('suppress_response_codes')) {
                $this->response['httpCode'] = $this->_response->getHttpResponseCode();
                $this->_response->setHttpResponseCode(200);
            }

            $json = json_encode($this->response);
            if($this->responseFormat == 'jsonp') {
                if(empty($_GET['callback'])) {
                    $json = json_encode("Requested response format is 'jsonp' but no 'callback' parameter was given");
                    $callback = 'jsonp_error';
                } else {
                    $callback = $_GET['callback'];
                }
                $this->_response->setBody($callback . '("' . addslashes($json) . '");');
            } else {
                $this->_response->setBody($json);
            }
        }
        elseif($this->responseFormat == 'xml') {
        }
        elseif($this->responseFormat == 'debug') {
            ppr($this->response);
        }
    }

    public function unsetParam($param) {
        $this->_request->setParam($param, null);
        unset($_GET[$param]);
        unset($_POST[$param]);
        unset($_REQUEST[$param]);
    }

    protected function _convertDT($d) {
        // If $d is a number and it's longer than 10 we know it's including something beyond seconds so convert it appropriately
        return $d ? date('Y-m-d H:i:s', is_numeric($d) ? ($d / pow(10, strlen($d) - 10)) : strtotime($d)) : null;
    }

    public function convertParams()
    {
        $converted = array();
        foreach($this->_request->getParams() as $param => $val) {
            preg_match('/(\w+)(?:\.([^:]+))?(?::(\w+))?/', $param, $matches);
            if(empty($matches)) continue;

            $name = $matches[1];
            $key = isset($matches[2]) ? $matches[2] : '';
            $type = isset($matches[3]) ? $matches[3] : '';
            if(isset($this->paramTypeMap[$type])) {
                $type = $this->paramTypeMap[$type];
            }

            if($type && $type != 'string') {
                $val = $this->getArrayParam($param);
                if($val) {
                    for($i = 0; $i < count($val); $i++) {
                        switch($type) {
                            case 'date':
                                $val[$i] = $val[$i] ? $this->_convertDT($val[$i]) : '';
                                break;
                            case 'bool':
                                $val[$i] = (bool)$val[$i];
                                break;
                            case 'int':
                                $val[$i] = (int)$val[$i];
                                break;
                            case 'null':
                                $val[$i] = null;
                                break;
                            default:
                                break;
                        }
                    }
                }

                if(count($val) == 1) {
                    $val = $val[0];
                }
            }

            if($key) {
                $converted[$name][$key] = $val;
                $this->unsetParam($param);
            }

            if($type) {
                $this->paramTypes[$name . '.' . $key] = $type;
            }
        }

        $this->_request->setParams($converted);
    }

    public function getSaveData($params = null, $fieldMap = null)
    {
        $fieldMap = $fieldMap ?: $this->fieldMap;

        if(!$params) {
            $params = array_merge($this->_request->getParam('_jsonData') ?: array(), $_POST);
            if($this->responseFormat == 'debug') {
                $params = array_merge($params, $this->_request->getParams());
            }
        }

        // Filter the given parameters so our saveData contains only valid saveable fields. This will also filter
        // any NULL values (unless emptyType has changed). Empty strings are not NULL, so they will be included.
        $saveData = $fieldMap->filterFields($params, true, false);

        // NULL/empty fields get filtered out, but if they are actually present but empty they should still be included in the save data
        if($fmFields = $fieldMap->getFields()) {
            foreach($fmFields as $field => $defaultVal) {
                if(!array_key_exists($field, $saveData) && array_key_exists($field, $params)) {
                    $saveData[$field] = $params[$field];
                }
            }
        }

        // With the DDM framework it will call the update method instead of the insert method on all DDM_Db_Table classes
        // if the primary key is set at all. If we don't really have a valid 'id' then we should remove it.
        if(array_key_exists('id', $saveData) && !$saveData['id']) {
            unset($saveData['id']);
        }

        if(!$fieldMap->isValid($saveData)) {
            $this->validationErrors($fieldMap->getValidationMessages());
        }
        $saveData = $fieldMap->mapFields($saveData, 'aliases');

        return $saveData;
    }

    /**
     * The index action handles index/list requests; it should respond with a
     * list of the requested resources.
     */
    abstract public function indexAction();

    /**
     * The get action handles GET requests and receives an 'id' parameter; it
     * should respond with the server resource state of the resource identified
     * by the 'id' value.
     */
    abstract public function getAction();

    /**
     * The post action handles POST requests; it should accept and digest a
     * POSTed resource representation and persist the resource state.
     */
    public function postAction()
    {
        $this->methodNotAllowed('POST');
    }

    /**
     * The put action handles PUT requests and receives an 'id' parameter; it
     * should update the server resource state of the resource identified by
     * the 'id' value.
     */
    public function putAction()
    {
        $this->methodNotAllowed('PUT');
    }

    /**
     * The delete action handles DELETE requests and receives an 'id'
     * parameter; it should update the server resource state of the resource
     * identified by the 'id' value.
     */
    public function deleteAction()
    {
        $this->methodNotAllowed('DELETE');
    }

    /**
     * The head action only outputs headers
     */
    public function headAction()
    {
        $this->methodNotAllowed('HEAD');
    }


    // ---------------------------
    // - Field mapping & helpers -
    // ---------------------------

    /**
     * Map fields to result
     *
     * @param array $fieldsArray
     * @param array $map
     * @return array
     */
    public function mapFields(array $fields, array $map)
    {
        $newfields = array();
        foreach($fields as $field => $value) {
            if(isset($map[$field])) {
                $newfields[$map[$field]] = $value;
            }
        }

        return $newfields;
    }

    /**
     * Duplicated for efficiencty. Map multiple rows to results.
     *
     * @param array $fieldsArray
     * @param array $map
     * @return array
     */
    public function mapFieldsArray(array $fieldsArray, array $map)
    {
        $newFieldsArray = array();
        foreach($fieldsArray as $fields) {
            $newFields = array();
            foreach($fields as $field => $value) {
                $newFields[$map[$field]] = $value;
            }

            $newFieldsArray[] = $newFields;
        }

        return $newFieldsArray;
    }

    /**
     * Get a parameter that should be an array. This allows it to be passed as a comma
     * separated list or in the form param[]=val&param[]=val2&param=val3.
     */
    public function getArrayParam($param, $allowScalar = false)
    {
        $result = (($p = $this->_request->getParam($param)) || $p !== null) ? (is_array($p) ? $p : explode(',', $p)) : null;

        if($allowScalar && count($result) == 1) {
            return $result[0];
        }

        return $result;
    }


    // --------------------------
    // - Error handling methods -
    // --------------------------

    // - Direct response modification -

    public function error($message)
    {
        if($this->_response->getHttpResponseCode() == 200) {
            throw new Exception('error() called but no HTTP response code was set. You should avoid calling this directly and instead use the appropriate response function to represent the status you desire.');
        }

        $this->response['error'] = $message;
        $this->postDispatch();
        $this->_helper->notifyPostDispatch();
        $this->_response->sendResponse();
        exit;
    }

    public function warning($message)
    {
        if(!is_array($this->response['warning'])) {
            $this->response['warning'] = array();
        }

        $this->response['warning'][] = $message;
    }

    public function notice($message)
    {
        if(!is_array($this->response['notice'])) {
            $this->response['notice'] = array();
        }

        $this->response['notice'][] = $message;
    }

    public function response($data)
    {
        if(is_object($data)) {
            $data = (array) $data;
        }

        $this->response['data'] = $data;
    }


    public function permissionError($message = null)
    {
        $this->_response->setHttpResponseCode(401);
        $this->error($message ? $message : 'You do not have permission to perform the requested action with this resource');
    }


    // - 304 Not Modified -

    public function notModified() {
        $this->_response->setHttpResponseCode(304);
        $this->_response->setBody('');
        $this->_response->sendResponse();
        exit;
    }


    // - 400 Bad Request -

    /**
     * Sets HTTP status code to 400
     *
     * @param type $message
     */
    public function badRequest($message = null)
    {
        $this->_response->setHttpResponseCode(400);
        $this->error($message ? $message : 'Bad Request');
    }

    public function missingRequiredFields($fields)
    {
        if(!is_array($fields)) {
            $fields = array($fields);
        }
        $this->badRequest("The following required fields are missing: " . ("'" . implode("', '", $fields) . "'"));
    }

    public function validationErrors($errors)
    {
        $this->badRequest(array(
            'validationErrors' => $errors
        ));
    }

    public function invalidFields($fields)
    {
        if(!is_array($fields)) {
            $fields = array($fields);
        }
        $this->badRequest("The following field names are not valid: " . ("'" . implode("', '", $fields) . "'"));
    }

    public function invalidOrderFields($fields)
    {
        if(!is_array($fields)) {
            $fields = array($fields);
        }
        $this->badRequest("You cannot order by the following given field names: " . ("'" . implode("', '", $fields) . "'"));
    }

    public function invalidOffset($offset)
    {
        $this->badRequest("Invalid offset '" . $offset . "'");
    }

    public function invalidLimit($limit)
    {
        $this->badRequest("Invalid offset '" . $limit . "'");
    }

    public function invalidpage($page)
    {
        $this->badRequest("Invalid page number '" . $page . "'");
    }


    // - 401 Unauthorized -

    public function unauthorized($message = null)
    {
        $this->_response->setHttpResponseCode(401);
        $this->error($message ? $message : 'Access Denied');
    }

    // - 401 Forbidden -

    public function forbidden($message = null)
    {
        $this->_response->setHttpResponseCode(403);
        $this->error($message ? $message : 'Forbidden');
    }


    // - 404 Not Found -

    public function missingResource($message = null)
    {
        $this->_response->setHttpResponseCode(404);
        $this->error($message ? $message : 'Missing or invalid resource' . ($this->id ? (" '" . $this->id . "'") : ''));
    }


    // - 405 Method Not Allowed -

    public function methodNotAllowed($method)
    {
        $this->_response->setHttpResponseCode(405);
        $this->error("The '" . $method . "' method is not allowed on this resource");
    }

    /**
     * Sets the given message in the response error field only if 'display_errors' is turned on,
     * otherwise it uses a generic message.
     *
     * @param type $message
     */
    public function saveResourceError($message)
    {
        $this->error(ini_get('display_errors') ? $message : 'Error saving data');
    }


    // - 500 Internal Server Error -

    public function serverError($message = null, $useIni = false)
    {
        error_log($message);
        $this->_response->setHttpResponseCode(500);
        $this->error($useIni && !ini_get('display_errors') ? 'Internal Server Error' : $message);
    }
}
