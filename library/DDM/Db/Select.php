<?php
class DDM_Db_Select extends Zend_Db_Select
{
    protected $joins = array();
    protected $joiningTables = array();
    protected $joinData = array();
    protected $columnDeps = array();

    public function columns() {
        $result = call_user_func_array('parent::columns', func_get_args());

        $columns = $this->getPart(Zend_Db_Select::COLUMNS);
        $deps = array();
        for($i = 0; $i < count($columns); $i++) {
            $table = $columns[$i][0];
            $col = $columns[$i][1];
            $alias = $columns[$i][2];

            $colName = $alias ?: $col;
            if($colName && isset($this->columnDeps["$colName"])) {
                $deps += $this->columnDeps[$colName];
            }
        }

        if(count($deps)) {
            $this->joinTables($this->buildDepTree($deps));
        }

        return $result;
    }

    /**
     * Conditional order statement
     *
     * @param unknown_type $statement
     * @param unknown_type $name
     * @param unknown_type $value
     */
    public function orderIf($statement, $params, $name, $value = '')
    {
        if (!is_array($params)) {
            return $this;
        }
        if (trim($name) == '') {
            return $this;
        }
        if (!array_key_exists($name, $params)) {
            return $this;
        }
        $val = trim($params[$name]);
        if ($val == '') {
            return $this;
        }
        if ($value && ($value != $val)) {
            return $this;
        }
        return parent::order($statement);
    }

    /**
     * Conditional where statement
     *
     * @param string $statement
     * @param string|array $name
     * @param string $value (optional)
     * @return DDM_Db_Select
     */
    public function whereIf($statement, $params, $name = '', $value = '')
    {
        if (is_array($params)) {
            if (trim($name) == '') {
                return $this;
            }
            if (!array_key_exists($name, $params)) {
                return $this;
            }
            $val = trim($params[$name]);
                if ($val == '') {
                return $this;
            }
        } else {
            $val = trim($params);
            if (!$val) {
                return $this;
            }
        }
        if ($value && ($value != $val)) {
            return $this;
        }
        return parent::where($statement, $val);
    }

    /**
     * Conditional where IN statement
     *
     * @param string $statement
     * @param array $values
     * @param string $name (optional)
     * @return DDM_Db_Select
     */
    public function whereIn($statement, $params, $name = '')
    {
        if ((!is_array($params)) || (count($params) < 1)) {
            return $this;
        }
        $out = '';
        foreach ($params as $n => $v) {
            if (is_array($v)) {
                $v = $v[$name];
            }
            if ($out) {
                $out .= ',';
            }
            $out .= "'" . mysql_escape_string($v) . "'";
        }
        $statement = str_replace("?", '(' . $out . ')', $statement);
        return parent::where($statement);
    }


    public function setJoins($joins) {
        $joinTables = array();
        foreach($joins as $join) {
            $joinKey = $join[1];

            if(is_array($joinKey)) {
                $a = array_keys($joinKey);
                if(!is_numeric($a[0])) {
                    $joinKey = $a[0];
                } else {
                    $joinKey = $joinKey[$a[0]];
                }
            }

            $joinTables[$joinKey] = $join;
        }

        $this->joinData = $joinTables;

        return $this;
    }

    /**
     * Used to set column dependencies for conditional table joins
     *
     * @param array $deps [description]
     */
    public function setColumnDeps($deps) {
        foreach($deps as $col => $dep) {
            if(!is_array($dep)) $deps[$col] = array($deps[$col]);
        }
        $this->columnDeps = $deps;

        return $this;
    }

    /**
     * Builds a table dependency tree given a list of top-level required tables
     *
     * @param  (array|string) $required  The top level dependencies
     * @return array                     The dependency tree
     */
    private function buildDepTree($required) {
        $depends = is_array($required) ? $required : array($required);
        $depTree = array();
        for($i = 0; $i < count($depends); $i++) {
            $joinKey = $depends[$i];

            // The table may already be joined to so we don't want to throw an error if it's not in our
            // 'joinData', but it could be a problem.
            if(!isset($this->joinData[$joinKey])) continue;

            $join = $this->joinData[$joinKey];
            $joinOn = $join[2];

            // Find join dependencies - if joining to this table requires joining to another, then do it
            $matches = array();
            preg_match_all('/([\w]+)`?\./', $joinOn, $matches);
            $depends += $matches[1];

            $deps = array();
            foreach($matches[1] as $dep) {
                if(!isset($depTree[$dep])) $depTree[$dep] = array();
                if($dep == $joinKey || isset($deps[$dep])) continue;
                $deps[$dep] = &$depTree[$dep];
            }

            $depTree[$joinKey] += $deps;
        }

        return $depTree;
    }

    /**
     * Helper function that recursively joins to tables based on their definition and their dependant tables
     *
     * @param  array $tables     The table tree to join - generated by searchIf()
     * @param  array $tableData  The table data needed to perform the joins. This is identical to the normal calls to
     *                           'join', 'joinLeft' and 'joinRight', except that the first parameter will be the name of
     *                           the method to call (e.g. 'join', 'joinLeft', 'joinRight').
     */
    private function joinTables($tables) {
        foreach($tables as $joinKey => $deps) {
            if(!isset($this->joins[$joinKey]) && isset($this->joinData[$joinKey])) {
                if(isset($this->joiningTables[$joinKey])) throw new Exception('Circular dependencies!');
                if(!isset($this->joinData[$joinKey])) throw new Exception("Missing table data for '" . $joinKey . "'");

                $this->joiningTables[$joinKey] = true;

                // Join to dependencies first
                if(count($deps)) {
                    $this->joinTables($deps);
                }

                $this->joins[$joinKey] = true;
                $join = $this->joinData[$joinKey];
                $joinType = $join[0];
                $join = array_slice($join, 1);

                // Don't select any columns by default
                if(!isset($join[2])) {
                    $join[2] = null;
                }

                call_user_func_array(array($this, $joinType), $join);
                unset($this->joiningTables[$joinKey]);
            }
        }
    }

    /**
     * Search on fields, only if the term is set
     *
     * See documentation at: https://sites.google.com/a/deseretdigital.com/ddmwiki/home/development/projects/ddm-framework/ddm_db_select
     *
     * @param array $searchFields
     * @return DDM_Db_Select
     */
    public function searchIf($searchFields)
    {
        $adapter = $this->getAdapter();
        $opMap = array(
            'in' => '=',
            'not in' => '!=',
            'notin' => '!=',
            '!in' => '!=',
            '<>' => '!=',

            '>=' => '>=',
            '>' => '>',
            '<=' => '<=',
            '<' => '<',

            'between',

            'not like' => 'not like',
            'notlike' => 'not like',
            '!like' => 'not like',

            'regex' => 'regexp'
        );

        // Build the field groups and field group options
        $groupFields = array();
        $groupOptions = array();
        if(!isset($searchFields['groupOptions'])) {
            $searchFields['groupOptions'] = array();
        }

        $groupOptions = $searchFields['groupOptions'];
        unset($searchFields['groupOptions']);
        if(!isset($groupOptions['default'])) {
            $groupOptions['default'] = array();
        }

        if(!isset($groupOptions['default']['joinBy'])) {
            $groupOptions['default']['joinBy'] = 'and';
        }

        foreach($groupOptions as $group => &$gOptions) {
            if(!isset($groupFields[$group])) {
                $groupFields[$group] = array();
            }

            // Group join option setup
            if(!isset($gOptions['joinBy'])) {
                throw new Exception("Missing option 'joinBy' in group options for '" . $group . "' group");
            }

            if(strtolower($gOptions['joinBy']) == 'and') {
                $gOptions['joinBy'] = ' AND ';
            } else {
                $gOptions['joinBy'] = ' OR ';
            }

            // Field group setup
            if((!isset($gOptions['fieldGroup']) || !is_string($gOptions['fieldGroup'])) && $group != 'default') {
                $gOptions['fieldGroup'] = 'default';
            }

            if(isset($gOptions['fieldGroup'])) {
                $fg = $gOptions['fieldGroup'];
                if(!isset($groupFields[$fg])) {
                    $groupFields[$fg] = array();
                }

                $groupFields[$fg][$group] = &$groupFields[$group];
            }
        }

        // Build the clauses based on the search field specifications
        foreach($searchFields as $op => $fields) {
            $matches = array();
            preg_match('/(.*?)(LIKE|NOT LIKE|NOTLIKE|!LIKE|REGEXP|!REGEXP|NOT REGEXP|BETWEEN)(.*)/i', $op, $matches);
            if($matches) {
                $start = $matches[1];
                $op = $matches[2];
                $end = $matches[3];
            }
            $op = strtolower($op);
            if(isset($opMap[$op])) {
                $op = $opMap[$op];
            }

            foreach($fields as $field)
            {
                $fieldName = $field[0];
                $param = $field[1];
                $options = isset($field[2]) ? $field[2] : array();

                // Are we ANDing or ORing values together? - AND makes the most sense by default for searching more than one field
                if(!isset($options['valJoin']) || trim(strtolower($options['valJoin'])) == 'and') {
                    $options['valJoin'] = ' AND ';
                } else {
                    $options['valJoin'] = ' OR ';
                }

                if(!isset($options['fieldJoin']) || trim(strtolower($options['fieldJoin'])) == 'or') {
                    $options['fieldJoin'] = ' OR ';
                } else {
                    $options['fieldJoin'] = ' AND ';
                }

                if(!isset($options['fieldGroup'])) {
                    $options['fieldGroup'] = 'default';
                }

                if(!($param || ((is_string($param) || is_int($param)) && (string)$param === '0') || (isset($options['strict']) && $options['strict']))) {
                    continue;
                }
                //if(!$param && ((is_string($param) || is_int($param)) && (string)$param !== '0') && (!isset($options['strict']) || !$options['strict']))

                // Build the table dependency tree for joining
                if(isset($options['depends'])) {
                    //$depends = is_array($options['depends']) ? $options['depends'] : array($options['depends']);

                    $this->joinTables($this->buildDepTree($options['depends']));
                }

                // Build the where clause
                switch($op)
                {
                    case 'regexp':
                    case 'not like':
                    case 'like':
                        if(isset($options['dontParse']) && $options['dontParse']) {
                            $words = is_array($param) ? $param : array($param);
                        } else {
                            $term = is_array($param) ? ('"' . implode('" "', str_replace(array('"', "'"), '', $param)) . '"') : (is_string($param) ? $param : '');

                            // Break string out into words and phrases (denoted by "")
                            $matches = array();
                            preg_match_all('/(?:["](?P<phrases>.*?)["]|(?P<words>[\w]+))/', $term, $matches);
                            $words = array_merge(array_filter(isset($matches['phrases']) ? $matches['phrases'] : array()), array_filter(isset($matches['words']) ? $matches['words'] : array()));
                        }

                        // Field could be a single field or an array that we OR together
                        if(!is_array($fieldName)) {
                            $fieldName = array($fieldName);
                        }

                        // Handle 'wholeWords' option
                        if(isset($options['wholeWords']) && $options['wholeWords']) {
                            $op = 'REGEXP';
                            $start = '[[:<:]]';
                            $end = '[[:>:]]';
                        }

                        $where = array();
                        foreach($words as $word) {
                            $fieldWhere = array();
                            $word = substr($adapter->quote($word), 1, -1);
                            foreach($fieldName as $f) {
                                $fieldWhere[] = sprintf("%s " . strtoupper($op) . " %s", $f, "'" . $start . $word . $end . "'");
                            }
                            $joined = implode(' ' . $options['fieldJoin'] . ' ', $fieldWhere);
                            if(count($fieldWhere) > 1) {
                                $joined = '(' . $joined . ')';
                            }

                            $where[] = $joined;
                        }

                        // Join the conditions
                        $joined = implode(' ' . $options['valJoin'] . ' ', $where);
                        if($joined) {
                            $groupFields[$options['fieldGroup']][] = count($where) > 1 ? '(' . $joined . ')' : $joined;
                        }
                        break;
                    case 'between':
                        $comp = $fieldName;
                        $vals = $param;
                        $reverse = false;

                        if(isset($options['reverse']) && $options['reverse']) {
                            $reverse = true;
                        }

                        if(!$vals || !is_array($vals) || count($vals) != 2) {
                            break;
                        }

                        if(!is_array($comp)) {
                            $comp = array($comp);
                        }

                        $v1 = $vals[0];
                        $v2 = $vals[1];
                        $where = array();
                        foreach($comp as $c) {
                            if(!$reverse) {
                                if(is_null($v1) || $v1 === '' || is_null($v2) || $v2 === '') {
                                    continue;
                                }
                                $where[] = sprintf('%s BETWEEN %s AND %s', $c, $adapter->quote($v1), $adapter->quote($v2));
                            } else {
                                if(is_null($c) || $c === '') {
                                    continue;
                                }
                                $where[] = $adapter->quoteInto(sprintf('? BETWEEN %s AND %s', $v1, $v2), $c);
                            }
                        }
                        if(empty($where)) {
                            break;
                        }

                        // Join the conditions
                        if(!$reverse) {
                            $joined = implode(' ' . $options['fieldJoin'] . ' ', $where);
                        } else {
                            $joined = implode(' ' . $options['valJoin'] . ' ', $where);
                        }

                        if(count($where) > 1) {
                            $joined = '(' . $joined . ')';
                        }

                        $groupFields[$options['fieldGroup']][] = $joined;
                        break;
                    case '!=':
                    case '=':
                    case '>':
                    case '>=':
                    case '<':
                    case '<=':
                        if(!is_array($fieldName)) {
                            $fieldName = array($fieldName);
                        }

                        $where = array();
                        foreach($fieldName as $f) {
                            // NOTE: The following line is commented out because it doesn't quote certain things right,
                            //       for example DATE(t.field) becomes `DATE(t`.`field)`. Is there a better way to handle this?
                            //$f = $adapter->quoteIdentifier($f);

                            // This is a special optimization for '=' and '!=' with lists of values to compare against
                            if(($op == '=' || $op == '!=') && is_array($param) && count($param) != 1) {
                                if(count($param) == 0) {
                                    $where[] = 'false';
                                }
                                else {
                                    $realOp = $op == '!=' ? 'NOT IN' : 'IN';
                                    $where[] = $adapter->quoteInto(sprintf('%s ' . $realOp . ' (?)', $f), $param);
                                }
                            } else {
                                if(!is_array($param)) {
                                    $param = array($param);
                                }

                                $paramWhere = array();
                                foreach($param as $p) {
                                    if(strtolower($p) == 'null') {
                                        $realOp = $op == '!=' ? 'IS NOT NULL' : 'IS NULL';
                                        $paramWhere[] = $f . ' ' . $realOp;
                                    } else {
                                        $p = (is_numeric($p)) ? $p : $adapter->quote($p);
                                        $paramWhere[] = sprintf("%s %s %s", $f, $op, $p);
                                    }
                                }

                                // Join the conditions
                                //  - With explicit '=' we always join by 'OR' because no field will ever exactly equal two different values
                                //  - With explicit '!=' we always join by 'AND' because every field will not equal one OR another value
                                $fieldJoin = $options['valJoin'];
                                if($op == '=') {
                                    $fieldJoin = ' OR ';
                                } elseif($op == '!=') {
                                    $fieldJoin = ' AND ';
                                }
                                $joined = implode(' ' . $fieldJoin . ' ', $paramWhere);

                                // If there were conditions we want to keep them grouped within parentheses
                                if(count($paramWhere) > 1) {
                                    $joined = '(' . $joined . ')';
                                }
                                if($joined) {
                                    $where[] = $joined;
                                }
                            }
                        }
                        $joined = implode($options['fieldJoin'], $where);
                        $groupFields[$options['fieldGroup']][] = count($where) > 1 ? '(' . $joined . ')' : $joined;
                        break;
                    default:
                        throw new Exception("'" . $op . "' is not a supported operator");
                }
            }
        }

        // Put the WHERE clause together based on the field groups
        $processedGroups = array();
        $joinGroup = function($group) use(&$groupFields, &$groupOptions, &$joinGroup, &$processedGroups) {
            // This should be theoretically impossible since every field group can only have one parent group and by creating
            // a circular link it breaks the link with 'default' causing it to never be processed
            if(isset($processedGroups[$group])) {
                throw new Exception("Field group recursion detected: Group '" . $group . "' has already been used");
            }
            $processedGroups[$group] = true;
            $clauses = $groupFields[$group];

            // $groupName will only be something other than an index for nested groups
            foreach($clauses as $groupName => $clause) {
                if(is_array($clause)) {
                    $clauses[$groupName] = $joinGroup($groupName);
                }
            }

            $clauses = array_filter($clauses);
            $joined = implode($groupOptions[$group]['joinBy'], $clauses);
            if(count($clauses) > 1 && $group != 'default') {
                $joined = '(' . $joined . ')';
            }

            return $joined;
        };

        $where = $joinGroup('default');
        if($where) {
            $this->where($where);
        }

        return $this;
    }

    /**
     * Returns a formatted sql string
     *
     * @param mixed string|DDM_Db_Select $query
     * @return string
     */
    public function beautifySQL($query=null)
    {
        if(empty($query))
        {
            $query = $this;
        }
        $query = preg_replace("/\s+/", ' ', $query);
        $query = preg_replace("/(DESCRIBE|SELECT| FROM | ON | OUTER JOIN | INNER JOIN | LEFT JOIN | JOIN | WHERE | ORDER BY | AND | OR | GROUP BY | LIMIT )/i", "\n<b style=\"color: #900;\">\$1</b>", $query);
        $query = preg_replace("/( ON | AS )/i", "<span style=\" font-size: 16px; color: #888;\">\$1</span>", $query);
        return trim($query);
    }




    // ==============================
    // -- Extra join functionality --
    // ==============================


    /**
     * Adds a FROM table and optional columns to the query.
     *
     * The first parameter $name can be a simple string, in which case the
     * correlation name is generated automatically.  If you want to specify
     * the correlation name, the first parameter must be an associative
     * array in which the key is the correlation name, and the value is
     * the physical table name.  For example, array('alias' => 'table').
     * The correlation name is prepended to all columns fetched for this
     * table.
     *
     * The second parameter can be a single string or Zend_Db_Expr object,
     * or else an array of strings or Zend_Db_Expr objects.
     *
     * The first parameter can be null or an empty string, in which case
     * no correlation name is generated or prepended to the columns named
     * in the second parameter.
     *
     * @param  array|string|Zend_Db_Expr $name The table name or an associative array
     *                                         relating correlation name to table name.
     * @param  array|string|Zend_Db_Expr $cols The columns to select from this table.
     * @param  string $schema The schema name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function from($name, $cols = '*', $schema = null, $options = null)
    {
        return $this->_join(self::FROM, $name, null, $cols, $schema, $options);
    }

    /**
     * Adds a JOIN table and columns to the query.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function join($name, $cond, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->joinInner($name, $cond, $cols, $schema, $options);
    }

    /**
     * Add an INNER JOIN table and colums to the query
     * Rows in both tables are matched according to the expression
     * in the $cond argument.  The result set is comprised
     * of all cases where rows from the left table match
     * rows from the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinInner($name, $cond, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::INNER_JOIN, $name, $cond, $cols, $schema, $options);
    }

    /**
     * Add a LEFT OUTER JOIN table and colums to the query
     * All rows from the left operand table are included,
     * matching rows from the right operand table included,
     * and the columns from the right operand table are filled
     * with NULLs if no row exists matching the left table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinLeft($name, $cond, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::LEFT_JOIN, $name, $cond, $cols, $schema, $options);
    }

    /**
     * Add a RIGHT OUTER JOIN table and colums to the query.
     * Right outer join is the complement of left outer join.
     * All rows from the right operand table are included,
     * matching rows from the left operand table included,
     * and the columns from the left operand table are filled
     * with NULLs if no row exists matching the right table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinRight($name, $cond, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::RIGHT_JOIN, $name, $cond, $cols, $schema, $options);
    }

    /**
     * Add a FULL OUTER JOIN table and colums to the query.
     * A full outer join is like combining a left outer join
     * and a right outer join.  All rows from both tables are
     * included, paired with each other on the same row of the
     * result set if they satisfy the join condition, and otherwise
     * paired with NULLs in place of columns from the other table.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  string $cond Join on this condition.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinFull($name, $cond, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::FULL_JOIN, $name, $cond, $cols, $schema, $options);
    }

    /**
     * Add a CROSS JOIN table and colums to the query.
     * A cross join is a cartesian product; there is no join condition.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinCross($name, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::CROSS_JOIN, $name, null, $cols, $schema, $options);
    }

    /**
     * Add a NATURAL JOIN table and colums to the query.
     * A natural join assumes an equi-join across any column(s)
     * that appear with the same name in both tables.
     * Only natural inner joins are supported by this API,
     * even though SQL permits natural outer joins as well.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  array|string|Zend_Db_Expr $name The table name.
     * @param  array|string $cols The columns to select from the joined table.
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object.
     */
    public function joinNatural($name, $cols = self::SQL_WILDCARD, $schema = null, $options = null)
    {
        return $this->_join(self::NATURAL_JOIN, $name, null, $cols, $schema, $options);
    }

    /**
     * Populate the {@link $_parts} 'join' key
     *
     * Does the dirty work of populating the join key.
     *
     * The $name and $cols parameters follow the same logic
     * as described in the from() method.
     *
     * @param  null|string $type Type of join; inner, left, and null are currently supported
     * @param  array|string|Zend_Db_Expr $name Table name
     * @param  string $cond Join on this condition
     * @param  array|string $cols The columns to select from the joined table
     * @param  string $schema The database name to specify, if any.
     * @param  array $options Additional options such as forceIndex, useIndex and ignoreIndex
     * @return Zend_Db_Select This Zend_Db_Select object
     * @throws Zend_Db_Select_Exception
     */
    protected function _join($type, $name, $cond, $cols, $schema = null, $options = null)
    {
        if(!is_array($options)) {
            $options = array();
        }

        $beforeKeys = array_keys($this->_parts[self::FROM]);
        $result = parent::_join($type, $name, $cond, $cols, $schema);
        $afterKeys = array_keys($this->_parts[self::FROM]);
        $addedKeys = array_diff($afterKeys, $beforeKeys);

        foreach($addedKeys as $tbl) {
            $this->joinOptions($tbl, $options);
        }

        return $result;
    }

    /**
     * Reset the join options for the given correlation
     * @param  [type] $correlationName The table name or alias if one was provided - whatever name it will be referenced as in the query
     * @return Zend_Db_Select          This Zend_Db_Select object
     */
    public function resetJoinOptions($correlationName) {
        unset($this->_parts[self::FROM][$correlationName]['forceIndex']);
        unset($this->_parts[self::FROM][$correlationName]['useIndex']);
        unset($this->_parts[self::FROM][$correlationName]['ignoreIndex']);

        return $this;
    }

    /**
     * Set join options for a table already joined to
     *
     * @param  string $correlationName The table name or alias if one was provided - whatever name it will be referenced as in the query
     * @param  [type] $options         An array of options identical to what would be provided in the last argument of a from or join call
     * @return Zend_Db_Select          This Zend_Db_Select object
     */
    public function joinOptions($correlationName, $options)
    {
        if(isset($options['forceIndex'])) {
            $forceIndex = is_array($options['forceIndex']) ? $options['forceIndex'] : array($options['forceIndex']);
            if(!empty($forceIndex)) {
                $this->_parts[self::FROM][$correlationName]['forceIndex'] = $forceIndex;
            }
        }

        if(isset($options['useIndex'])) {
            $useIndex = is_array($options['useIndex']) ? $options['useIndex'] : array($options['useIndex']);
            if(!empty($useIndex)) {
                $this->_parts[self::FROM][$correlationName]['useIndex'] = $useIndex;
            }
        }

        if(isset($options['ignoreIndex'])) {
            $ignoreIndex = is_array($options['ignoreIndex']) ? $options['ignoreIndex'] : array($options['ignoreIndex']);
            if(!empty($ignoreIndex)) {
                $this->_parts[self::FROM][$correlationName]['ignoreIndex'] = $ignoreIndex;
            }
        }

        return $this;
    }



    /**
     * Render FROM clause
     *
     * @param string   $sql SQL query
     * @return string
     */
    protected function _renderFrom($sql)
    {
        /*
         * If no table specified, use RDBMS-dependent solution
         * for table-less query.  e.g. DUAL in Oracle.
         */
        if (empty($this->_parts[self::FROM])) {
            $this->_parts[self::FROM] = $this->_getDummyTable();
        }

        $from = array();

        foreach ($this->_parts[self::FROM] as $correlationName => $table) {
            $tmp = '';

            $joinType = ($table['joinType'] == self::FROM) ? self::INNER_JOIN : $table['joinType'];

            // Add join clause (if applicable)
            if (! empty($from)) {
                $tmp .= ' ' . strtoupper($joinType) . ' ';
            }

            $tmp .= $this->_getQuotedSchema($table['schema']);
            $tmp .= $this->_getQuotedTable($table['tableName'], $correlationName);

            if(isset($table['forceIndex'])) {
                $tmp .= ' FORCE INDEX(' . implode(', ', $table['forceIndex']) . ')';
            }
            if(isset($table['useIndex'])) {
                $tmp .= ' USE INDEX(' . implode(', ', $table['useIndex']) . ')';
            }
            if(isset($table['ignoreIndex'])) {
                $tmp .= ' IGNORE INDEX(' . implode(', ', $table['ignoreIndex']) . ')';
            }

            // Add join conditions (if applicable)
            if (!empty($from) && ! empty($table['joinCondition'])) {
                $tmp .= ' ' . self::SQL_ON . ' ' . $table['joinCondition'];
            }

            // Add the table name and condition add to the list
            $from[] = $tmp;
        }

        // Add the list of all joins
        if (!empty($from)) {
            $sql .= ' ' . self::SQL_FROM . ' ' . implode("\n", $from);
        }

        return $sql;
    }
}
