<?php

// http://zendframework.com/manual/en/zend.db.table.html

class DDM_Db_Table extends Zend_Db_Table_Abstract {

	const HASH_MD5 = 'md5';

    /**
     * Execute a select statement
     *
     * @param string | Zend_Db_Select
     * @return array
     */
    public function runSelect($select)
    {
    	if( $select instanceof Zend_Db_Select ) {
        	$query = $select->__toString();
    	} else {
    		$query = $select;
    	}
        //$query = preg_replace('/^SELECT /', 'SELECT SQL_CALC_FOUND_ROWS ', $query);
        return array(
            'data' => $this->_db->fetchAll($query),
            //'total' => $this->_db->fetchOne("SELECT FOUND_ROWS()")
        );
    }

    /**
     * Get a select statement
     *
     * @return DDM_Db_Select
     */
    public function getSelect($params = null)
    {
        return new DDM_Db_Select($this->_db);
    }

    /**
     * Get the referenced table for a foreign key
     *
     * @param string $name
     * @return string $table
     */
    public function getReferencedTable($name)
    {
        if (!isset($this->_metadata[$name]['REFERENCED_TABLE_NAME'])) {
            return null;
        } else {
            return $this->_metadata[$name]['REFERENCED_TABLE_NAME'];
        }
    }

	/**
	 * Get the metadata about the table
	 *
	 * @return array
	 */
	public function getMetaData() {
		return $this->_metadata;
	}

	/**
	 * Get the names of the fields
	 *
	 * @return array
	 */
	public function getFieldNames() {
		return $this->_cols;
	}

	/**
	 * Insert data
	 *
	 * @param unknown_type $data
	 */
	public function insert(array $data ) {

		// throw out fields that don't belong in this model
		$fields = $this->getFieldNames();
		foreach($data as $key => $value) {
			if( !in_array($key, $fields) ) {
				unset($data[$key]);
			}
		}

		// let the parent finish up now that the data is pruned
		parent::insert($data);
	}

	/**
     * Save a table row with specified data.
     *
     * @param array $bind Column-value pairs.
     * @return int The number of affected rows.
     */
    public function save(array $bind)
    {

    	// all keys are primary?
    	$allFieldsAreKeys = false;
    	if( count($this->_primary) == count($this->_metadata) ) {
    		$allFieldsAreKeys = true;
    	}

        // extract and quote col names from the array keys
        $cols = array();
        $vals = array();
        $updateVals = array();
        $updateBind = array();
        foreach ($bind as $col => $val) {
            $cols[] = "`$col`";
            if ($val instanceof Zend_Db_Expr) {
                $vals[] = $val->__toString();
                unset($bind[$col]);
            } else {
                $vals[] = '?';
            }
            if( ( !in_array($col, $this->_primary) || $allFieldsAreKeys ) &&
            	( !isset($this->_insertOnlyColumns) || !in_array($col, $this->_insertOnlyColumns) )
            ) {
            	$updateVals[] = "`$col` = ?";
            	$updateBind[] = $bind[ $col ];
            }
        }

        $sql = "INSERT INTO `$this->_name` "
             . ' (' . implode(', ', $cols) . ') '
             . 'VALUES (' . implode(', ', $vals) . ')';

        /* Only add on duplicate key update if there are updateVals */
		if(count($updateVals) > 0) {
        	$bind = array_merge($bind, $updateBind);
			$sql .= "\n"
             . 'ON DUPLICATE KEY UPDATE '
             . '' . implode(', ', $updateVals) . '';
		}

		// handy for debug
		//if( $this instanceof Models_DS_GroupMembership ) {
    		//ppr( array_values($bind)); echo $sql; exit;
		//}

        // execute the statement and return the number of affected rows
        $statement = $this->_db->query($sql, array_values($bind));
        $lastId = $this->_db->lastInsertId();
        //if($this instanceof Models_DS_ContentTask  ) {
        	//echo "last = $lastId"; exit;
        //}
        return $lastId;
    }


    /**
     * encrypt given data
     * @param string $data
     * @return string
     */
    public function encrypt($data){
    	//TODO: Encrypt data
    	return $data;
    }

    public function decrypt($data){
    	//TODO: Decrypt data
    	return $data;
    }

    /**
     * hash the data using predefined methods
     *
     * @param string $method
     * @param string $data
     * @return string
     */
    public function hash($method, $data){
    	if(defined('self::'.$method)){
    		$prefix = strtolower(preg_replace('/HASH_/','',$method)).':';
    		if(preg_match('/^'.$prefix.'/', $data)){
    			$hashed_data = preg_replace('/'.$prefix.'/','',$data);
    		}else{
    		  $function = constant('self::'.$method);
    		  if(method_exists($this,$function)){
    		  	$hashed_data = $this->$function($data);
    		  }else{
    		    $hashed_data = $function($data);
    		  }
    		}
    	}else{
    		$hashed_data = '';
    	}
    	return $hashed_data;
    }

    public function getHash($method, $value, $use_prefix = true){
    	if($use_prefix){
	        if(defined('self::'.$method)){
	            $prefix = strtolower(preg_replace('/HASH_/','',$method)).':';
	            return $prefix.$value;
	        }
    	}else{
    		return $value;
    	}
    }

    public function md5($data){
    	$salt = 'Salt is yummy';
    	return md5($salt.$data);
    }

    /**
     * Get all fields as an array
     * @param boolean $set_values_only
     * @return array
     */
    public function getAll($set_values_only = true)
    {
    	$data = array();
    	$dataSource = ($set_values_only) ? $this->_ddm_data : $this->_cols;

		$filter = new Zend_Filter_Word_UnderscoreToCamelCase();
		foreach($dataSource as $key => $col){
			$column_name = ($set_values_only) ? $key : $col;
			$funcName = $filter->filter('get_'.$column_name);
			// See http://framework.zend.com/issues/browse/ZF-6938 - the filter doesn't like numbers
            $funcName = str_replace('_', '', $funcName);
			if( is_callable( array($this, $funcName), false ) ) {
				$data[$column_name] = $this->$funcName();
			}
		}
		return $data;
    }

    /**
     * Sets properties that have corresponding key names in the passed in array
     *
     * @param array $data
     */
    public function setAll($data = null)
    {
        $all = $this->getAll();
        if(is_array($data)) {
            $all = array_merge($all, $data);
        }

        $data = $this->cleanAll($all);

        if( is_array($data) ) {
            $filter = new Zend_Filter_Word_UnderscoreToCamelCase();
            foreach($this->_cols as $col){
                if( array_key_exists($col, $data) ) {
                    $funcName = $filter->filter('set_'.$col);
                    // See http://framework.zend.com/issues/browse/ZF-6938 - the filter doesn't like numbers
                    $funcName = str_replace('_', '', $funcName);
					if( is_callable( array($this, $funcName), false ) ) {
						$this->$funcName($data[$col]);
					}
                }
            }
        }
    }

    /**
     * Clean and return all data, according to defined filters in the model or the defaults
     * @param array $data
     * @return array
     */
    public function cleanAll($data) {

        foreach($this->_cols as $col) {

            if(empty($data[$col])) {
                continue;
            }

            if(is_object($data[$col])) {
                continue;
            }

            // default filters
            $filters = array('DDM_Filter_StripTagsAndContents', 'Zend_Filter_StripTags', 'Zend_Filter_StringTrim');

            // defined filters for this column
            if(property_exists($this, '_col_filters')) {
                if(array_key_exists($col, $this->_col_filters) && $this->_col_filters[$col] === null) {
                    // we are told not to do anything with this column
                    $filters = array();
                } else if(isset($this->_col_filters[$col])) {
                    // an array of filters and options
                    $filters = $this->_col_filters[$col];
                }
            }

            // apply each filter to this column of data
            if(count($filters)) {
                foreach($filters as $fName => $options) {
                    if(is_numeric($fName)) {
                        $fName = $options;
                        $options = null;
                    }
                    // create and apply the filter
                    $filter = new $fName($options);
                    if(is_object($data[$col])) {
                      // nothing
                    } else if(is_array($data[$col])) {
                        if(count($data[$col])) {
                            foreach($data[$col] as $key => $contents) {
                                if(is_object($contents)) {
                                    continue;
                                } else if(is_array($contents)) {
                                    $data[$col][$key] = $this->cleanAll($contents);
                                } else {
                                    $data[$col][$key] = $filter->filter($contents);
                                }
                            }
                        }
                    } else {
                        $data[$col] = $filter->filter($data[$col]);
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Get data based on a given set of fields and search filters
     *
     * @param array|DDM_Db_Select $select
     * @param array $fieldMap
     * @param array $fields
     * @param int $offset
     * @param int $limit
     * @param string|array $order
     * @param array $filters
     * @return array
     */
    public function _get($selects, array $fieldMap = null, $fields = null, $offset = null, $limit = null, $order = null, $filters = null)
    {
        if($selects instanceof DDM_Db_Select) {
            $union = $selects->getPart(DDM_Db_Select::SQL_UNION);
            if(empty($union))
            {
                $union = array($selects);
            }
            else {
                $union = array_map(function($item){return $item[0];},$union);
            }
        } else if(!is_array($selects)) {
            return;
        }

        if(!$fieldMap) {
            $fieldMap = array();
        }

        // Map requested fields to their actual names or expressions
        if(!is_array($fields) || empty($fields)) {
            return;
        }

        $select = $this->getSelect();

        $fields = array_map(function($field) use($fieldMap) {
            if(isset($fieldMap[$field])) {
                $fieldVal = $fieldMap[$field];

                $matches = array();
                preg_match('/^(?:.+\.[`]?)?([^`]+)/', $fieldVal, $matches);
                if(count($matches) == 2) {
                    return $fieldMap[$field] . ($matches[1] != $field ? ' AS ' . $field : '');
                }
                else {
                    throw new Exception("The field '" . $field . "' is not valid");
                }
            }
            else {
                return $field;
            }
        }, $fields);

        $fieldsDiff = array();

        if($order) {
            if(is_string($order)) {
                $order = array($order);
            }

            $orderFields = array();

            $order = array_map(function($item) use($fieldMap,&$orderFields,$union) {
                $matches = array();
                preg_match('/^[`]?([\w]+)[`]?(?:[\s]+(ASC|DESC))?$/', $item, $matches);
                if(!$matches) {
                    // Just match the whole thing
                    $matches = array_merge(array(''), preg_split("/(ASC|DESC)/", $item, -1, PREG_SPLIT_DELIM_CAPTURE));
                }

                if(empty($matches)) {
                    throw new Exception("Malformed field given to order by. Given field is '" . $item . "'");
                }

                $field = $matches[1];
                $dir = isset($matches[2]) ? $matches[2] : 'ASC';

                if(isset($fieldMap[$field])) {
                    $field = $fieldMap[$field];
                }

                if(count($union) > 1) {
                    $orderField = preg_replace("/^[a-zA-Z0-9_]+\(/", '', $field);
                    $orderField = str_replace(')','',$orderField);
                    $orderField = array_map(function($item){return trim($item);},explode(',', $orderField));
                    $orderFields = array_merge($orderFields,$orderField);
                    $field = preg_replace("/(?:[`]?[\w]+[`]?\.[`]?)?([\w])/", "$1", $field);
                } else {
                    $orderFields []= $field;
                }

                return $field . ' ' . $dir;

            }, $order);
            if(count($union) > 1) {
                $fieldsDiff = array_map(function($item){$item = explode('.',$item); return array_pop($item);},array_diff($orderFields, $fields));
                $fields = array_unique(array_merge($fields,$orderFields));
            }
        }

        foreach($union as $unionSelect) {
            // Select the requested fields
            $unionSelect->reset(Zend_Db_Select::COLUMNS);
            $unionSelect->columns($fields);

            // Add search filters limit and sorting
            if($filters) {
                // Map searchIf filters
                foreach($filters as $op => &$filterSet) {
                    foreach($filterSet as &$filter) {
                        $filterFields = &$filter[0];
                        if(!is_array($filterFields)) {
                            $filterFields = isset($fieldMap[$filterFields]) ? $fieldMap[$filterFields] : $filterFields;
                        } else {
                            foreach($filterFields as $index => $field) {
                                $filterFields[$index] = isset($fieldMap[$field]) ? $fieldMap[$field] : $field;
                            }
                        }
                    }
                }

                $unionSelect->searchIf($filters);
            }
        }
        if(count($union) > 1) {
            $select->union($union);
        } else {
            $select = $union[0];
        }

        if($order) {
            $select->order($order);
        }
        if($limit) {
            $select->limit($limit, $offset);
        }

        $result = $this->_db->fetchAll($select);
        foreach($result as &$row) {
            foreach($fieldsDiff as $field) {
                unset($row[$field]);
            }
        }
        return $result;
    }


    /**
     * Given an array of data and a field map, this will map the keys to the actual database column names and save it
     *
     * @param array $fieldMap
     * @param array $data
     */
    public function _set(array $data, $fieldMap = null, $constraints = null)
    {
        if(!$fieldMap) {
            $fieldMap = array();
        }

        if(!($fieldMap instanceof DDM_FieldMap)) {
            $fieldMap = new DDM_FieldMap(array(
                'aliases' => $fieldMap
            ));
        }

        if($constraints) {
            $fieldMap->setConstraints($constraints);
        }

        /*$mappedData = array();
        foreach($data as $field => $value) {
            if(isset($fieldMap[$field])) {
                $mappedData[$fieldMap[$field]] = $value;
            } else {
                // NOTE: We can't currently do anything about this other than just filter out invalid names
                //       because there is no way currently for a controller to get access to the list of valid
                //       fields to filter itself.
                //throw new Exception("'" . $field . "' is not a valid field name");
            }
        }*/

        // Set values. Default values are populated first, then the model data is loaded, NULL values are filtered out and then
        // the data is merged, overwriting any default values, then finally any explicitly set values override those.
        $currentData = array();
        $mappedData = $fieldMap->mapFields($data, 'aliases');
        if(isset($mappedData['id']) && $this->loadOne($mappedData['id'])) {
            $currentData = array_filter($this->getAll(), function($item) {
                return $item !== null;
            });
        }
        $data = array_merge($fieldMap->filterFields($data, false, false), $currentData, $mappedData);

        $this->setAll($data);
        $data = $this->getAll();
        if(!$fieldMap->isValid($data)) {
            throw new DDM_Db_Exception_Validation($fieldMap->getValidationMessages());
        }
    }
}
