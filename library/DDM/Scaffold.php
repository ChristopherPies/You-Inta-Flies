<?php

/**
 * Scaffold will generate two files per table per type (model, form).
 * The base will always be overwritten, but the sub will only be written the first time.
 *
 * http://zendframework.com/manual/en/zend.codegenerator.html
 *
 */
class DDM_Scaffold {

	protected $defaultDb;
	protected $targetDbs;
	protected $projectRoot;
	protected $paths = array();
	protected $db;
	protected $tables;
	protected $namespaces = array();
	protected $dirForNamespace = true;
	protected $formClass = 'DDM_Form';
    protected $modelClass = 'DDM_Db_Table';

	public function __construct( $dbType = 'db', $key = 'params', $options = array()) {

		$application = new Zend_Application(
    		APPLICATION_ENV,
    		APPLICATION_PATH . '/configs/application.ini'
		);
		$appOpts = $application->getOptions();
		$dbParams = $appOpts['resources'][$dbType][$key];
		$this->defaultDb = $dbParams['dbname'];
		$dbParams['dbname'] = 'information_schema';

		if( !empty( $appOpts['resources'][$dbType][$key]['adapter']) ) {
			$adapter = $appOpts['resources'][$dbType][$key]['adapter'];
		} else {
			$adapter = $appOpts['resources']['db']['adapter'];
		}

		if(isset($options['form_class'])) {
		    $this->formClass = $options['form_class'];
		}

        if(isset($options['model_class'])) {
            $this->modelClass = $options['model_class'];
        }

		$this->db = Zend_Db::factory($adapter, $dbParams );

	}

	/**
	 * Get an array of tables in this database
	 *
	 * @param string $db
     * @param array $targetTables
	 * @return array
	 */
	public function getTables( $db, $targetTables = null ) {

		$sql = "SELECT *
			FROM information_schema.TABLES
			WHERE TABLE_SCHEMA = '$db'";

        if (!is_null($targetTables)) {
            $sql .= " AND TABLE_NAME IN ('" . implode("','", $targetTables) . "')";
        }

		$tables = $this->db->fetchAll( $sql );
		return $tables;

	}

	 /**
     * Get the indexes for a table
     *
     * @param string $database
     * @param string $table
     *
     * @return array
     */
    protected function getIndexes($database, $table) {
        $sql = "SELECT *
        FROM `STATISTICS`
        WHERE `TABLE_SCHEMA` = '$database'
        AND `TABLE_NAME` = '$table'
            ORDER BY INDEX_NAME, SEQ_IN_INDEX";
        $indexes = $this->db->fetchAll($sql);
        return $indexes;
    }

	/**
	 * Get columns in a table
	 *
	 * @param string $db
	 * @param string $table
	 * @return array
	 */
	public function getColumns( $db, $table ) {
		$sql = "SELECT *
			FROM information_schema.COLUMNS
			WHERE TABLE_SCHEMA = '$db'
			AND TABLE_NAME = '$table'";

		$cols = $this->db->fetchAll( $sql );

		// get values for enums
		foreach( $cols as &$c ) {
			if( $c['DATA_TYPE'] == 'enum' ) {
				$this->parseEnumValues( $c );
			}

		}

		return $cols;

	}

	/**
	 * Parse enum info
	 *
	 * @param array $c
	 */
	public function parseEnumValues( &$c ) {
		$end = strlen( $c['COLUMN_TYPE'] );
		$list = substr( $c['COLUMN_TYPE'], 5, $end - 6 );
		$parts = explode(',', $list);
		$list = array();
		foreach($parts as $p) {
			$list[] = str_replace('\'', '', $p);
		}
		$c['VALUES'] = $list;
	}

	/**
	 * Get the keys for a table
	 *
	 * @param string $db
	 * @param string $table
	 * @return array
	 */
	public function getKeys( $db, $table ) {
		$sql = "SELECT *
			FROM information_schema.KEY_COLUMN_USAGE
			WHERE TABLE_SCHEMA = '$db'
			AND TABLE_NAME = '$table'
			AND REFERENCED_COLUMN_NAME IS NOT NULL
			ORDER BY COLUMN_NAME, ORDINAL_POSITION";

		$keys = $this->db->fetchAll( $sql );
		return $keys;
	}

	/**
	 * Should we create a folder for each namespace(database)
	 *
	 * @param boolean $flag
	 */
	public function setNsFolderFlag( $flag ) {
		$this->dirForNamespace = (boolean) $flag;
	}

    /**
     * Set the namespace manually instead of just the first letter(s) of the db.
     *
     * @param type $databaseName
     * @param type $namespace
     * @return DDM_Scaffold
     */
    public function setNamespace($databaseName, $namespace) {
        $databaseName = strtolower($databaseName);
        $this->namespaces[$databaseName] = $namespace;
        return $this;
    }

	/**
	 * Generate Models (maybe more later)
     * $targetDbNames can be an array of arrays where you pass in table names
     * for each db.
	 *
	 * @param string $applicationPath
	 * @param string|array $targetDbNames
	 * @param array $paths
	 */
	public function generate( $projectRoot, $targetDbNames = null, $paths = null ) {

		// where should we write all the different types of files?
		if( $paths === null ) {
			$this->paths = array('controller' => 'Controllers/', 'model' => 'Models/', 'view' => 'Views/', 'form' => 'Forms/', 'generated' => 'library/Generated/', 'application' => 'application/', 'constants' => 'Constants/' );
		} else {
			$this->paths = $paths;
		}

		// use the project's db if one is not specified
		if( $targetDbNames == null && $this->defaultDb != '' ) {
			$targetDbNames = $this->defaultDb;
		}

		if( !is_dir( $projectRoot . $this->paths['generated'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'], 0775);
		}

		if( !is_dir( $projectRoot . $this->paths['application'] . $this->paths['model'] ) ) {
			mkdir( $projectRoot . $this->paths['application'] . $this->paths['model'], 0775);
		}

		if( !is_dir( $projectRoot . $this->paths['application'] . $this->paths['form'] ) ) {
			mkdir( $projectRoot . $this->paths['application'] . $this->paths['form'], 0775);
		}

		//echo $projectRoot . $this->paths['generated'] . $this->paths['model']; exit;

		if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['model'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'] . $this->paths['model'], 0775);
		}
		if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['form'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'] . $this->paths['form'], 0775);
		}
		if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['view'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'] . $this->paths['view'], 0775);
		}
		if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['controller'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'] . $this->paths['controller'], 0775);
		}



		$this->projectRoot = $projectRoot;

		// force it to be an array so we can always treat it as such
		if( !is_array($targetDbNames) ) {
			$targetDbNames = array($targetDbNames => null);
		}
		$this->targetDbs = $targetDbNames;

		foreach( $targetDbNames as $targetDbName => $targetTables) {
            if (is_int($targetDbName)) {
                $targetDbName = $targetTables;
                $targetTables = null;
            }

			$ns = $this->makeNsName($targetDbName);
			if( $this->dirForNamespace === true ) {
				if( !is_dir( $projectRoot . $this->paths['application'] . $this->paths['model'] . '/' . $ns ) ) {
					mkdir( $projectRoot . $this->paths['application'] . $this->paths['model'] . '/' . $ns, 0775);
				}
				if( !is_dir( $projectRoot . $this->paths['application'] . $this->paths['form'] . '/' . $ns ) ) {
					mkdir( $projectRoot . $this->paths['application'] . $this->paths['form'] . '/' . $ns, 0775);
				}
				if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['model'] .'/'. $ns ) ) {
					mkdir( $projectRoot . $this->paths['generated'] . $this->paths['model'] .'/'. $ns, 0775);
				}
				if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['form'] .'/'. $ns ) ) {
					mkdir( $projectRoot . $this->paths['generated'] . $this->paths['form'] .'/'. $ns, 0775);
				}
				if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['view'] .'/'. $ns ) ) {
					mkdir( $projectRoot . $this->paths['generated'] . $this->paths['view'] .'/'. $ns, 0775);
				}
				if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['controller'] .'/'. $ns ) ) {
					mkdir( $projectRoot . $this->paths['generated'] . $this->paths['controller'] .'/'. $ns, 0775);
				}
			}

            if (is_array($targetTables)) {
                $tables = $this->getTables( $targetDbName, $targetTables );
            } else {
                $tables = $this->getTables( $targetDbName );
            }
			if( count($this->tables) ) {
				$this->tables = array_merge($this->tables, $tables);
			} else {
				$this->tables = $tables;
			}

			// all tables across more than on db
			foreach( $this->tables as &$table ) {

				$tableName = $table['TABLE_NAME'];
				$targetDbName = $table['TABLE_SCHEMA'];

				// save the NS to this table info
                $ns = $this->makeNsName($targetDbName);
				$table['namespace'] = $ns;

				// the table name converted for use in file and class names.
				$classNamePartial = $this->makeClassName($tableName);
				$table['classNamePartial'] = $classNamePartial;

				// get the cols for this table
				$cols = $this->getColumns( $targetDbName, $tableName );
				$table['COLUMNS'] = $cols;

				// get the cols for this table
                $idxs = $this->getIndexes( $targetDbName, $tableName );
                $table['INDEXES'] = $idxs;

				// get the keys for this table
				$keys = $this->getKeys( $targetDbName, $tableName );
				$table['KEYS'] = $keys;


				// this generates a lot of stuf that we already have, but in a format that Zend_Db_Table wants
				// add REFERENCED_TABLE_NAME to the metadata
				$metadata = $this->db->describeTable($tableName, $targetDbName);
				if( count($keys) ) {
					foreach($keys as $k) {
						if( isset( $metadata[ $k['REFERENCED_COLUMN_NAME'] ] ) ) {
							$metadata[ $k['COLUMN_NAME'] ]['REFERENCED_TABLE_NAME'] = $k['REFERENCED_TABLE_NAME'];
						}
					}
				}
				$table['zend_describe_table'] = $metadata;

				// get a sorted array of fields that make up the PK
				$pks = array();
				foreach( $metadata as $c ) {
					if( isset($c['PRIMARY']) && $c['PRIMARY'] == 1 ) {
						$pks[ ($c['PRIMARY_POSITION'] - 1) ] = $c['COLUMN_NAME'];
					}
				}
				$table['PRIMARY_COLUMNS'] = $pks;
				//ppr($table); exit;

			}
			unset($table); // nuke the & from above

		}

        //ppr($this->tables); exit;

		/* generate stuff now that we have all the info we need */
		foreach( $this->tables as $table ) {

			// Make Models
			$result = $this->makeModel( $table );
			if( !$result ) {
				echo "Unable to generate model for ". $table['TABLE_NAME'];
				exit;
			}

			// Make Forms
			$result = $this->makeForm( $table );
			if( !$result ) {
				echo "Unable to generate form for ". $table['TABLE_NAME'];
				exit;
			}


		}

		/* Automatically generate any needed constants */
		$this->generateConstants( $projectRoot, $paths );
	}

	/**
	 * Generate Constants
	 *
	 * @param string $applicationPath
	 * @param array $paths
	 */
	public function generateConstants( $projectRoot, $paths = null ) {
		/* Make sure we have a constants.cfg file where expected */
		if(!file_exists(APPLICATION_PATH . '/configs/constants.cfg')) { return false; }
		include(APPLICATION_PATH . '/configs/constants.cfg');

		/* Make sure the included file created the constants variable and it's an array */
		if(!isset($constants) || !is_array($constants)) { return false; }

		// where should we write the constants?
		if( $paths === null ) {
			$this->paths = array('generated' => 'library/Generated/', 'constants' => 'Constants/' );
		} else {
			$this->paths = $paths;
		}

		if( !is_dir( $projectRoot . $this->paths['generated'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'], 0775);
		}

		if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['constants'] ) ) {
			mkdir( $projectRoot . $this->paths['generated'] . $this->paths['constants'], 0775);
		}

		$this->projectRoot = $projectRoot;

		/* Loop through all databases in the constants array */
		foreach( $constants as $dbName => $tables ) {
			if(!is_array($tables)) { continue; }

			$ns = $this->makeNsName($dbName);
			if( $this->dirForNamespace === true ) {
				if( !is_dir( $projectRoot . $this->paths['generated'] . $this->paths['constants'] .'/'. $ns ) ) {
					mkdir( $projectRoot . $this->paths['generated'] . $this->paths['constants'] .'/'. $ns, 0775);
				}
			}

			foreach($tables as $tableName => $options) {
				if(!is_array($options)) { continue; }

				$result = $this->makeConstants($dbName, $tableName, $options);
				if(!$result) {
					echo 'Unable to generate constants class for '. $tableName;
					exit;
				}
			}
		}
	}

	/**
	 * make a DB name into a string we can use for the namespace
	 * The Result will be unique to this runtime
	 *
	 * @param string $name
	 * @param int $lettersToUse
	 * @return string
	 */
	public function makeNsName( $name, $lettersToUse = 1 ) {
		if( isset($this->namespaces[$name]) ) {
			return $this->namespaces[$name];
		}

		$parts = explode('_', $name );
		$newWord = '';



		foreach($parts as $p) {
			$newWord .= substr($p, 0, $lettersToUse );
		}
		$newWord = strtoupper($newWord);
		if( !in_array($newWord, $this->namespaces) ) {
			$this->namespaces[$name] = $newWord;
			return $newWord;
		} else {
			if( strlen($name) == $lettersToUse ) {
				echo "Unable to determin a namespace name for $name ";
				exit;
			}
			return $this->makeNsName($name, $lettersToUse++);
		}

	}

	/**
	 * convert a table name into something we can use for a class or file name
	 *
	 * @param string $name
	 * @param boolean $upperCaseFirst
	 * @return string
	 */
	public function makeClassName( $name, $upperCaseFirst = true ) {

		$parts = explode('_', $name );
		$first = true;
		$newWord = '';
		foreach($parts as $p) {

			$p = trim( ucfirst( $p ) );
			if( $first ) {
				if( $upperCaseFirst === false ) {
					$p = strtolower($p);
				}
				$first = false;
			}

			$newWord .= $p;
		}
		return $newWord;

	}

	/**
	 * Generate a model for a table
	 *
	 * @param array $tableInfo
	 * @return boolean
	 */
	protected function makeModel( $tableInfo ) {

		// application/model/DS/Article.php		Model_DS_Article
		// application/form/DS/Article.php    Form_DS_Article

		//$tableInfo['namespace']

		if( $this->dirForNamespace === true ) {
			$className = $tableInfo['classNamePartial'];
			$baseClassName = $className;
			$baseFile = $this->projectRoot . $this->paths['generated'] . $this->paths['model'] . $tableInfo['namespace'] . '/'. $baseClassName . '.php';
			$subFile = $this->projectRoot . $this->paths['application'] . $this->paths['model'] . $tableInfo['namespace'] .'/'. $className . '.php';
		} else {
			$className = $tableInfo['namespace'] . '_'. $tableInfo['classNamePartial'];
			$baseClassName = 'Base_'. $className;
			$baseFile = $this->projectRoot . $this->paths['generated'] . $this->paths['model'] . $baseClassName . '.php';
			$subFile = $this->projectRoot . $this->paths['application'] . $this->paths['model'] . $className . '.php';
		}

		// should we write the sub class?
		$writeSub = false;
		if( !file_exists($subFile) ) {
			$writeSub = true;
		}

		/* sample var data */
		// rootClassName = DS_Article.php
		// BaseFile = /var/www/zf/library/generated/models/Base_DS_Article.php
		// SubFile = /var/www/zf/application/models/DS_Article.php

		//echo $baseFile ."<BR>"; echo $subFile ."<BR>"; exit;

		// comments
		@$baseDocBlock = "$baseClassName\n\nGenerated class file for table ". $tableInfo['TABLE_SCHEMA'] . '.' . $tableInfo['TABLE_NAME'] . "\nAny changes here will be overridden.\n";
		$subDocBlock = "Model for ". $tableInfo['TABLE_SCHEMA'] . '.' . $tableInfo['TABLE_NAME'] . "\nThis is where you can add and override functions";

		$baseMethods = array();
		$baseProperties = array();
		$insertOnlyColumns = array();

		// PK getter
		$pkGetter = array(
				'name' => 'getPrimaryKeys',
				'body' => "return \$this->_primary;",
				'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'Get an array of primary keys',
                )),
        );

        $baseMethods[$pkGetter['name']] = $pkGetter;

        // make related getters
        $hasPrimary = false;
        foreach( $tableInfo['KEYS'] as $key ) {

            if( $key['CONSTRAINT_NAME'] == 'PRIMARY') {
                $hasPrimary = true;
                continue;
            }

            $relatedType = $this->makeClassName( $key['REFERENCED_TABLE_NAME'] );

            //$relatedType = $this->makeClassName( str_replace( $key['REFERENCED_COLUMN_NAME'], '', $key['COLUMN_NAME']) );
            //echo "$relatedType <br>"; ob_flush();

            if( $this->dirForNamespace === true ) {
                $relatedNS = $this->makeNsName( $key['REFERENCED_TABLE_SCHEMA'] );
                $relatedClass = $relatedType;
                $relatedParam = $this->makeClassName( $key['REFERENCED_TABLE_NAME'] .'_'. $key['REFERENCED_COLUMN_NAME'], false );
                $relatedRequire = ""; // APPLICATION_PATH . '/". $this->paths['model'] ."$relatedNS/$relatedClass.php'";
                $relatedClass =  ucwords( str_replace( '/', '_', $this->paths['model'] ) ) . $relatedNS . '_'. $relatedClass;
            } else {
                $relatedNS = $this->makeNsName( $key['REFERENCED_TABLE_SCHEMA'] );
                $relatedClass = $relatedNS . '_'. $relatedType;
                $relatedParam = $this->makeClassName( $key['REFERENCED_TABLE_NAME'] .'_'. $key['REFERENCED_COLUMN_NAME'], false );
                $relatedRequire = ""; //APPLICATION_PATH . '/". $this->paths['model'] ."/$relatedClass.php'";
            }

            $relatedBody = "if( !\$this->get". $this->makeClassName($key['COLUMN_NAME']) ."() )\n{\n";
            $relatedBody .= "    return false;\n}\n";
            //$relatedBody .= "require_once( $relatedRequire );\n";
            $relatedBody .= "\$obj = new ". $relatedClass . "();\n";
            $relatedBody .= "    \$obj->loadOne( \$this->get". $this->makeClassName($key['COLUMN_NAME']) ."() );";
            $relatedBody .= "\n    return \$obj;";
            $tmp = array(
                'name' => 'get'. $relatedType,
                'body' => $relatedBody,
                'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                    'shortDescription' => "Get the related $relatedType from ". $key['REFERENCED_TABLE_SCHEMA'] .'.'. $key['REFERENCED_TABLE_NAME'],
                    'tags'             => array(
                        new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                            'datatype'  => $relatedClass,
                        )),
                    ))),
            );

            /* TODO - setting the name as the key is a hack to make sure we generate unique function names.
            * For a case to break it generate deseret_studio.task_depends (both fields relate to the same table)
            */
            $baseMethods[ 'get'. $relatedType ] = $tmp;

        }

        $indexByName = array();

        if(isset($tableInfo['INDEXES']) && count($tableInfo['INDEXES'])) {
            foreach( $tableInfo['INDEXES'] as $key ) {
                $indexByName[ $key['INDEX_NAME'] ][] = $key;
            }
        }

        foreach( $indexByName as $indexName => $fields ) {

            $count = count($fields);
            $current = 1;
            $tmpFields = array();

            while($current <= $count) {

                $i = 1;
                $tmpFields = array();
                foreach( $fields as $key ) {

                    $tmpFields[] = $key['COLUMN_NAME'];
                    $paramName = $this->makeClassName(join($tmpFields, ', $'), false);
                    $paramNameStr = $this->makeClassName(join($tmpFields, ' and '), false);
                    $loadByName = 'loadBy' . $this->makeClassName( join($tmpFields, '_and_') );

                    $loadByBody = '$select = parent::getSelect();';
                    $loadByBody .= "\n\$select->from('". $key['TABLE_NAME']."');";
                    foreach($tmpFields as $f) {
                        $allowNull = $tableInfo['zend_describe_table'][$f]['NULLABLE'];
                        // if the param is null and the field allows for it, look for a null
                        if( $allowNull) {
                            $tmpParamName = $this->makeClassName($f, false);
                            $loadByBody .= ";
if( \$$tmpParamName === null )
{
    \$select->where('`$f` IS NULL');
} else {
    \$select->where('`". $f . "` = ?');
}";
                        } else {
                            $loadByBody .= "\n\$select->where('`". $f . "` = ?');";
                        }
                    }
                    $loadByBody .= "\nreturn \$this->_db->fetchAll(\$select, array(\$$paramName));";

                    $loadByFn = array(
                        'name' => $loadByName,
                        'body' => $loadByBody,
                        'visibility'   => 'public',
                        'parameters' => array( array('name' => $paramName )),
                        'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                            'shortDescription' => "Load by ". $paramNameStr,
                            'tags'             => array(
                                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                                    'datatype'  => 'array',
                                )),
                            ))),
                    );

                    $baseMethods[ $loadByName ] = $loadByFn;

                    if( $i++ == $current ) {
                        break;
                    }

                }
                array_unique($tmpFields);
                //echo join($tmpFields, '_and_') . "<BR>";
                $current++;

            }

        }

        // Get Table name
        $getModel = array(
            'name' => 'getTableName',
            'visibility'   => 'public',
            'body' => "// get the table name
return \$this->_name;",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'Get the table name',
                'tags'             => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                        'paramName' => 'return',
                        'datatype'  => 'string'
                    )),
                ),
                )),
        );
        $baseMethods[$getModel['name']] = $getModel;

        $modelClassName = ucwords(str_replace('/', '', $this->paths['model'])) .'_'. $tableInfo['namespace'] . '_'. $tableInfo['classNamePartial'];

        $arrayToObjectsBody = "\$objs = array();
if(is_array(\$data)) {
    \$useKey = false;
    if(\$key === null) {
        \$keys = \$this->_primary;
        \$key = array_pop(\$keys);
    }
    if(!empty(\$data[0]) && isset(\$data[0][\$key] ) ) {
        \$useKey = true;
    }
    \$count = count(\$data);
    for(\$i = 0; \$i < \$count; \$i++) {
        if(\$useKey) {
            \$id = \$data[\$i][\$key];
        } else {
            \$id = \$data[\$i];
        }
        \$o = new $modelClassName();
        \$o->loadOne(\$id);
        \$objs[\$id] = \$o;
    }
}
return \$objs;";

        $arrayToObjectsFn = array(
            'name' => 'arrayToObjects',
            'body' => $arrayToObjectsBody,
            'visibility'   => 'public',
            'parameters' => array(
                array('name' => 'data' ),
                array('name' => 'key' )
            ),
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => "Retun objects from an array of ids",
                'tags'             => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Param(array('datatype'  => 'array',)),
                    new Zend_CodeGenerator_Php_Docblock_Tag_Param(array('datatype'  => 'string',)),
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array('datatype'  => 'array',)),
                ))),
        );
        $baseMethods[$arrayToObjectsFn['name']] = $arrayToObjectsFn;

        // make getters & setters
        foreach( $tableInfo['COLUMNS'] as $field ) {

            $name = $this->makeClassName( $field['COLUMN_NAME'] );
            $param = $this->makeClassName( $field['COLUMN_NAME'] , false );
            $phpType = $this->fieldTypeToVarType( $field['DATA_TYPE'] );

            if( $field['COLUMN_KEY'] == 'PRI' ) {

                $hasPrimary = true;

                $parmName = $this->makeClassName( $field['COLUMN_NAME'],false);
                $loadOne = array(
                    'name' => 'loadOne',
                    'visibility'   => 'public',
                    'parameters' => array( array('name' => $parmName ) ),
                    'body' => "// load by primary key
\$rs = \$this->find( \$".$parmName.");
if( !\$rs->current() ) {
    return array();
}
\$row = \$rs->getRow(0);
\$data = \$row->toArray();
\$this->setAll(\$data);
return \$data;",
                    'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                        'shortDescription' => 'Load by primary key (into this object)',
                        'tags'             => array(
                            new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                                'paramName' => 'return',
                                'datatype'  => 'array'
                            )),
                        ),
                        )),
                );
                if( !isset($baseMethods['loadOne']) ) {
                    $baseMethods['loadOne'] = $loadOne;
                }

            }

            $extraSetter = null;
            switch ($field['DATA_TYPE']) {
                case 'datetime':
                    $extraSetter .= "if( !\$$param ) {
        return;
    }\n";
                    $extraSetter .= "    if(\$$param != '0000-00-00 00:00:00') {
        \$$param = date('Y-m-d H:i:s', strtotime( \$$param ) );\n    }\n    ";
                    break;
                case 'date':
                    $extraSetter .= "if( !\$$param ) {
        return;
    }\n";
                    $extraSetter .= "    if(\$$param != '0000-00-00') {
        \$$param = date('Y-m-d', strtotime( \$$param ) );\n    }\n    ";
                    break;
                case 'time':
                    $extraSetter .= "if( !\$$param ) {
        return;
    }\n";
                    $extraSetter .= "    if(\$$param != '00:00:00') {
        \$$param = date('H:i:s', strtotime( \$$param ) );\n    }\n    ";
                    break;
                case 'timestamp':
                $extraSetter .= "if( !\$$param ) {
        return;
    }\n";
                    $extraSetter .= "    if(\$$param != '0000-00-00 00:00:00') {
        \$$param = date('Y-m-d H:i:s', strtotime( \$$param ) );\n    }\n    ";
                    break;
                case 'year':
                    $extraSetter .= "if( !\$$param ) {
        return;
    }\n";
                    $extraSetter .= "    if(\$$param != '0') {
        \$$param = date('Y', strtotime( \$$param ) );\n    }\n    ";
                    break;
                default:
                    break;

            }

            $hash = '';
            $encrypt = $hash = false;
            if( $field['IS_NULLABLE'] == 'YES' ) {
                $setterBody = "\$value = null;
if( \$$param !== null ) {
    $extraSetter\$value = ($phpType) \$$param;
}\n";
                $setterBody .= "\$this->_ddm_data['". $field['COLUMN_NAME'] . "'] =  \$value;";
                $setterBody .= "\nreturn \$this;";
            } else {
                $setterBody = "$extraSetter\$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = ($phpType) \$$param;";
                $setterBody .= "\nreturn \$this;";
            }

            $getterBody = "if( isset(\$this->_ddm_data['". $field['COLUMN_NAME'] . "']) ) {
    return \$this->_ddm_data['". $field['COLUMN_NAME'] . "'];
} else {
    return null;
}";
            if($field['COLUMN_COMMENT']){
                $comments = preg_split('/\s/',$field['COLUMN_COMMENT']);
                foreach($comments as $comment){
                    if(strstr($comment,'HASH')){
                        $phpType = 'string';
                        $setterBody = "\$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = ($phpType) \$this->hash('".$comment."',\$$param);";
                        $getterBody = "return \$this->getHash('".$comment."',\$this->_ddm_data['". $field['COLUMN_NAME'] . "'], \$use_prefix);";
                        $hash = true;
                        continue;
                    }
                    if(strstr($comment,'ENCRYPT')){
                        $encrypt = true;
                        continue;
                    } else if(strstr($comment,'SERIALIZED_DATA')){

                        $setterBody = "if( !isSerialized(\$$param) ) {
    \$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = serialize(\$$param);
} else {
    \$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = \$$param;
}";
                        $getterBody = "if( isset(\$this->_ddm_data['". $field['COLUMN_NAME'] . "']) ) {
    return unserialize(\$this->_ddm_data['". $field['COLUMN_NAME'] . "']);
} else {
    return array();
}";
                    } else if(strstr($comment,'BOOLEAN') || strstr($comment,'BOOL')){
                        // If we cast to a boolean and then an int, PHP leans toward true too often.
                        $setterBody = "if( \$$param === true || \$$param == 1 || \$$param === 'true' || \$$param === 'TRUE' ) {
    \$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = 1;
} else {
    \$this->_ddm_data['". $field['COLUMN_NAME'] . "'] = 0;
}";
                        $getterBody = "if( isset(\$this->_ddm_data['". $field['COLUMN_NAME'] . "']) ) {
    return (boolean) \$this->_ddm_data['". $field['COLUMN_NAME'] . "'];
} else {
    return null;
}";
                    } else if(strstr($comment,'IP_ADDRESS')) {
                        // Convert IP Addresses to unsigned integars for storage in database and then back for getting */
                        $setterBody = "\$this->_ddm_data['". $field['COLUMN_NAME'] ."'] = sprintf( '%u', ip2long(\$$param) );";
                        $getterBody = "if( isset(\$this->_ddm_data['" . $field['COLUMN_NAME'] . "']) ) {
    return long2ip( \$this->_ddm_data['" . $field['COLUMN_NAME'] . "'] );
} else {
    return null;
}";
                    }

                    /* Create a list of columns that are INSERT_ONLY */
                    if(strstr($comment, 'INSERT_ONLY')) {
                        $insertOnlyColumns[] = $field['COLUMN_NAME'];
                    }
                }
            }

            $colComment = '';
            if( isset( $field['COLUMN_COMMENT'] ) && $field['COLUMN_COMMENT'] != '' ) {
                $colComment = ' (' .$field['COLUMN_COMMENT'].')';
            }

            $setter = array(
                'name' => 'set'. $name,
                'parameters' => array( array('name' => $param ) ),
                'body' => $setterBody,
                'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                        'shortDescription' => 'Set the '. $name .' property' . $colComment,
                        'tags' => array(
                            new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                                'paramName' => $name,
                                'datatype'  => $phpType
                                )
                            ),
                            new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                                'datatype'  => $className,
                                )
                            ),
                        ),
                    )
                ),
            );

            $baseMethods[$setter['name']] = $setter;

            // expose the column info
            $getter = array(
                'name' => 'get'. $name,
                'body' => $getterBody,
                'docblock' => "Get $param "
            );
            if($hash){
                $getter['parameters'] = array( array('name' => 'use_prefix', 'defaultValue' => true ) );
            }
            $baseMethods[$getter['name']] = $getter;

        }

        // is the pk set?
        $pkCheck = array(
            'name' => 'pkIsSet',
            'body' => "if( count(\$this->_primary) == 0 ) {
    return null;
}
\$allKeysHaveValues = true;
foreach( \$this->_primary as \$c ) {
    if( empty(\$this->_ddm_data[\$c]) ) {
        \$allKeysHaveValues = false;
    }
}
return \$allKeysHaveValues;",
            'docblock' => "Is the primary key(s) set?"
        );
        $baseMethods[$pkCheck['name']] = $pkCheck;

        $pkFieldName = '';
        foreach($tableInfo['COLUMNS'] as $c ) {
            if( $c['COLUMN_KEY'] == 'PRI' ) {
                $pkFieldName = $this->makeClassName( $c['COLUMN_NAME']);
            }
        }

        // save - we don't care if it is insert or update
        $save = array(
            'name' => 'save',
            'parameters' => array( array('name' => 'data', 'defaultValue' => null, 'type'=>'array') ),
            'body' => "\$this->setAll(\$data);
\$keys = array_keys(\$this->_primary);
\$id = @\$this->_ddm_data[\$this->_primary[\$keys[0]]];
\$key = parent::save(\$this->_ddm_data);
if(!\$key && \$id) {
    \$key = \$id;
}
if(method_exists(\$this, 'getId') && !\$this->getId() && \$key > 0) {
    \$this->setId(\$key);
}

return \$key;
",
            'docblock' => "Save the data"
        );
        $baseMethods[$save['name']] = $save;

        // cache metadata in the class
        $meta = ( $tableInfo['zend_describe_table'] );
        $baseProperties[] = array(
                'name'         => '_metadata',
                'visibility'   => 'protected',
                'defaultValue' => $meta,
                'docblock' => 'Meta data cache to avoid describes at run time'
            );

        // table property
        $baseProperties[] = array(
                'name'         => '_name',
                'visibility'   => 'protected',
                'defaultValue' => $tableInfo['TABLE_NAME'],
                'docblock' => 'Table name'
            );

         // AI property
        $baseProperties[] = array(
                'name'         => '_sequence',
                'visibility'   => 'protected',
                'defaultValue' => $hasPrimary,
                'docblock' => 'Does this table have Auto Increment?'
            );

        // Columns
        $cols = array();
        foreach($tableInfo['COLUMNS'] as $field){
            $cols[]=$field['COLUMN_NAME'];
        }
        $baseProperties[] = array(
                'name'         => '_cols',
                'visibility'   => 'protected',
                'defaultValue' => $cols,
                'docblock' => 'Fields in db'
            );

        $baseProperties[] = array(
                'name'         => '_ddm_data',
                'visibility'   => 'protected',
                'defaultValue' => array(),
                'docblock' => 'Data array used in getters/setters'
            );

         // Primary Columns
        $baseProperties[] = array(
                'name'         => '_primary',
                'visibility'   => 'protected',
                'defaultValue' => $tableInfo['PRIMARY_COLUMNS'],
                'docblock' => 'Fields that make up the Primary Key'
            );

         // Insert Only Columns
        $baseProperties[] = array(
                'name'         => '_insertOnlyColumns',
                'visibility'   => 'protected',
                'defaultValue' => $insertOnlyColumns,
                'docblock' => 'Fields that can only be set on an insert'
            );

        $baseProperties[] = array(
                'name'         => '_metadataCacheInClass',
                'visibility'   => 'protected',
                'defaultValue' => true,
                'docblock'     => 'we have the data'
        );

        if( $this->dirForNamespace ) {
            $modelDirName = ucwords( str_replace( '/', '', $this->paths['model'] ) );
            $baseClassName = 'Generated_'. $modelDirName . '_'. $this->makeNsName( $tableInfo['TABLE_SCHEMA'] ) . "_$className";
        }

        $base = new Zend_CodeGenerator_Php_Class();
        $base->setName( $baseClassName );
        $base->setDocblock( $baseDocBlock );
        $base->setAbstract( true );
        $base->setMethods( $baseMethods );
        $base->setProperties( $baseProperties );
        $base->setExtendedClass( $this->modelClass );

        $baseCode = $base->generate();
        //$baseCode = "require_once('DDM/Db/Table.php');\n\n" . $baseCode;

        $this->writeCode( $baseFile, $baseCode );

        //ppr($baseCode);
        //ppr($baseCode); exit;

        if( $writeSub ) {
            if( $this->dirForNamespace === true ) {
                $modelDirName = ucwords( str_replace( '/', '', $this->paths['model'] ) );
                $subClassName = $modelDirName . '_'. $tableInfo['namespace'] . '_'. $className;
            }
            $subMethods = array();
            $sub = new Zend_CodeGenerator_Php_Class();
            $sub->setName( $subClassName );
            $sub->setDocblock( $subDocBlock );
            $sub->setExtendedClass( $baseClassName );
            $subClassName = $className . '.php';

            // a place to put logic that happens at insert, update or save - we only support a key with a single column right now
            if( $hasPrimary === true ) {
                $save = array(
                    'name' => 'save',
                    'parameters' => array( array('name' => 'data', 'defaultValue' => null, 'type'=>'array') ),
                    'body' => "if( \$this->pkIsSet() || (isset(\$data[ \$this->_primary[0]]) && \$data[ \$this->_primary[0]] != '' ) ) {
    return \$this->update( \$data );
} else {
    return \$this->insert( \$data );
}",
                    'docblock' => "save data"
                );
                $subMethods[] = $save;

                // a place to put logic that happens at insert
                $insert = array(
                    'name' => 'insert',
                    'parameters' => array( array('name' => 'data', 'defaultValue' => null, 'type'=>'array') ),
                    'body' => "return parent::save( \$data );",
                    'docblock' => "Insert data"
                );
                $subMethods[] = $insert;

                // a place to put logic that happens at update
                $update = array(
                    'name' => 'update',
                    'parameters' => array(
                            array('name' => 'data', 'defaultValue' => null, 'type'=>'array'),
                            array('name' => 'fooVar', 'defaultValue' => null),
                        ),
                    'body' => "return parent::save( \$data );",
                    'docblock' => "Update data"
                );
                $subMethods[] = $update;
            }


            $sub->setMethods( $subMethods );
            $subCode = $sub->generate();
            if( $this->dirForNamespace === true ) {
                //$subCode = "require_once('generated/". $this->paths['model'] . $tableInfo['namespace'] ."/$className.php');\n\n" . $subCode;
            } else {
                //$subCode = "require_once('generated/". $this->paths['model'] . "$className.php');\n\n" . $subCode;
            }

            //echo $subFile;
            //echo $subCode; exit;
            $this->writeCode( $subFile, $subCode );
        }

        return true;

    }

    /**
     * Generate a model for a table
     *
     * @param array $tableInfo
     * @return boolean
     */
    protected function makeForm( $tableInfo ) {

        $splitFields = array();

        if( $this->dirForNamespace === true ) {
            $className = $tableInfo['classNamePartial'];
            $baseClassName = $className;
            $baseFile = $this->projectRoot . $this->paths['generated'] . $this->paths['form'] . $tableInfo['namespace'] . '/'. $baseClassName . '.php';
            $subFile = $this->projectRoot . $this->paths['application'] . $this->paths['form'] . $tableInfo['namespace'] .'/'. $className . '.php';
            // make class and path names.
            $modelClassName = ucwords(str_replace('/', '', $this->paths['model'])) .'_'. $tableInfo['namespace'] . '_'. $tableInfo['classNamePartial'];
            $modelFile = 'APPLICATION_PATH . \'/'. $this->paths['model'] . $tableInfo['namespace'] .'/'. $className . '.php\'';

            //$subRequire = "require_once('generated/". $this->paths['form'] . $tableInfo['namespace'] ."/$className.php');\n\n";

            $className = ucwords(str_replace('/', '', $this->paths['form'])) .'_'. $tableInfo['namespace']. '_'. $className;

        } else {
            $className = $tableInfo['namespace'] . '_'. $tableInfo['classNamePartial'] . '_Form';
            $baseClassName = 'Base_'. $className;
            $baseFile = $this->projectRoot . $this->paths['generated'] . $this->paths['form'] . $baseClassName . '.php';
            $subFile = $this->projectRoot . $this->paths['application'] . $this->paths['form'] . $className . '.php';

            // make class and path names.
            $modelClassName = $tableInfo['namespace'] . '_'. $tableInfo['classNamePartial'];
            $modelFile = 'APPLICATION_PATH . \'/' . $this->paths['model'] . $modelClassName . '.php\'';

            //$subRequire = "require_once('generated/". $this->paths['form'] . "$baseClassName.php');\n\n";
        }

        /*
        echo "<br>$className <br>";
        echo "1 $baseFile <br>";
        echo "2 $subFile <br>";
        echo "3 $modelClassName <br>";
        echo "4 $modelFile <br>";
        echo "5 $subRequire <br>";
        */
        //exit;


        // should we write the sub class?
        $writeSub = false;
        if( !file_exists($subFile) ) {
            $writeSub = true;
        }

        $codeCreateElements = '';
        $ns = $this->makeNsName( $tableInfo['TABLE_SCHEMA']);
        $baseMethods = array();
        $baseProperties = array();
        $validateFunctions = array();

        foreach( $tableInfo['COLUMNS'] as $field ) {

            $htmlFieldName = $ns . '_' . $field['COLUMN_NAME'];
            $validators = array();
            $required = false;
            $filters = array('StringTrim');
            $label = null;
            $maxLength = null;

            $keys = array();
            foreach($tableInfo['KEYS'] as $key) {
                $keys[$key['COLUMN_NAME']] = $key;
            }

            /* validators
            array( 'alnum',    array('regex', false, '/^[a-z]/i') )
              */

            /* filter
            array('StringToLower')
            */

            $element = null;
            $isBoolean = false;
            $numeric = false;

            if( $field['COLUMN_KEY'] == 'PRI' ) {

                // Hide the PK
                $element = 'Zend_Form_Element_Hidden';

                // auto increment must be a number
                if( $field['EXTRA'] == 'auto_increment' ) {
                    //$validators[] = 'num';
                }
                $label = false;

            } else if( strpos($field['COLUMN_COMMENT'], 'HASH' ) !== false || $field['COLUMN_NAME'] == 'password' ) {
                // Use password if the comment says it is hashed or the name is password
                $element = 'Zend_Form_Element_Password';
                $maxLength = 100; // we'll hash it anyway, so who cares...

            } else if( $field['COLUMN_NAME'] == 'email' ) {
                // add validation for email
                $element = 'Zend_Form_Element_Text';
                $validators[] = 'EmailAddress';
                $filters[] = 'StringToLower';
                $maxLength = $field['CHARACTER_MAXIMUM_LENGTH'];

            } else {

                // special cases are handled above, now we just guess based on field type and lengths
                switch( $field['DATA_TYPE'] ) {
                    case 'int':
                        $maxLength = 11;
                    case 'smallint':
                        $maxLength = 6;
                    case 'tinyint':
                        $maxLength = 4;
                    case 'mediumint':
                        $maxLength = 8;
                    case 'bigint':
                        $maxLength = 20;
                    case 'float':
                    case 'decimal':
                    case 'double':
                        $numeric = true;

                        if($field['COLUMN_COMMENT']){
                            $comments = preg_split('/\s/',$field['COLUMN_COMMENT']);
                            foreach($comments as $comment) {
                                if( strstr($comment,'BOOLEAN') !== false ) {
                                    $element = 'Zend_Form_Element_Checkbox';
                                    $isBoolean = true;
                                    $options = array(1 => 1);
                                }
                            }
                        }

                        if( $maxLength == null ) {
                            $maxLength = 15;
                        }
                        if( $element == null ) {
                            $element = 'Zend_Form_Element_Text';
                        }

                        if( ($field['COLUMN_KEY'] == 'MUL' || isset($keys[$field['COLUMN_NAME']])) && substr($field['COLUMN_NAME'], -3) == '_id' ) {
                            if( isset( $tableInfo['KEYS'] ) ) {
                                foreach( $tableInfo['KEYS'] as $key ) {
                                    if( $key['COLUMN_NAME'] == $field['COLUMN_NAME'] ) {

                                        $selectFunctionName = $field['COLUMN_NAME'];
                                        $selectFunctionModel = ucwords( str_replace('/', '_', $this->paths['model']) ) . $ns . '_' . $this->makeClassName( $key['REFERENCED_TABLE_NAME']);
                                        $selectFunctionRequire = 'APPLICATION_PATH . \'/' . $this->paths['model'] . $ns . '/' . $this->makeClassName( $key['REFERENCED_TABLE_NAME']) . '.php\'';
                                        $selectFunctionName = ucwords( str_replace('_', ' ', $selectFunctionName) );
                                        $validateFunctionName = 'validate' . str_replace( ' ', '', $selectFunctionName);
                                        $selectFunctionName = 'populate' . str_replace( ' ', '', $selectFunctionName);
                                        $selectFunctionOptionKey = $key['REFERENCED_COLUMN_NAME'];
                                        $selectFunctionOptionValue = $key['REFERENCED_COLUMN_NAME'];

                                        // types for name
                                        $typesForName = array('char', 'varchar');

                                        // find the "name" field on the related table
                                        foreach( $this->tables as $t ) {
                                            if( $t['TABLE_NAME'] != $key['REFERENCED_TABLE_NAME'] ) {
                                                continue;
                                            }
                                            foreach($t['COLUMNS'] as $pos => $c ) {
                                                if( $c['COLUMN_NAME'] == $selectFunctionOptionKey ) {
                                                    continue;
                                                }
                                                if( in_array($c['DATA_TYPE'], $typesForName ) ) {
                                                    $selectFunctionOptionValue = $c['COLUMN_NAME'];
                                                    break;
                                                }
                                            }

                                        }

                                        //echo $selectFunctionName . '<br>';    echo $selectFunctionModel . '<br>'; echo $selectFunctionRequire . '<br>';
                                        //$obj = new Models_DS_Request();
                                        //$el = $this->getElement('DS_request_id');
                                        //Zend_Form_Element::setOptions( );

                                        // method to populate the select box
                                        //'body' => "require_once(". $selectFunctionRequire . ");
                                        $pop = array(
                                            'name' => $selectFunctionName,
                                            'parameters' => array(
                                                array('name' => 'keyField', 'defaultValue' => $selectFunctionOptionKey ),
                                                array('name' => 'valueField', 'defaultValue' => $selectFunctionOptionValue )
                                            ),
                                            'body' => "
\$obj = new $selectFunctionModel();
\$ele = \$this->getElement('". $ns . '_'. $field['COLUMN_NAME'] . "');
\$sql = \"SELECT `\$keyField`, `\$valueField` FROM `\". \$obj->getTableName() . \"` ORDER BY `\$valueField`\";
\$rows = \$obj->getAdapter()->fetchAll(\$sql);
if( count(\$rows) ) {
    \$tmp = array();
    foreach(\$rows as \$r) {
        \$tmp[ \$r[\$keyField ] ] = \$r[\$valueField];
    }
    \$ele->addMultiOptions( \$tmp );
}
",
                                            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                                                'shortDescription' => 'Populate the ' . $ns . '_'. $field['COLUMN_NAME'] . 'field',
                                                )),
                                        );
                                        $baseMethods[] = $pop;

                                        // validate function for fields with a FK
                                        $validateFunctions[ $field['COLUMN_NAME'] ] = $validateFunctionName;
                                        $validate = array(
                                            'name' => $validateFunctionName,
                                            'parameters' => array(
                                                array('name' => 'value'),
                                            ),
                                            'body' => "
\$obj = new $selectFunctionModel();
if( count(\$obj->loadOne( \$value ) ) ) {
    return true;
}
return false;
",
                                            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                                                'shortDescription' => 'Validate the ' . $ns . '_'. $field['COLUMN_NAME'] . 'field',
                                                )),
                                        );
                                        // not used yet
                                        //$baseMethods[] = $validate;

                                        //echo "added $selectFunctionName ";

                                        break;
                                    }
                                    continue;
                                }
                            }
                            $element = 'Zend_Form_Element_Select';
                        }
                        break;

                    case 'text':
                        $maxLength = 65000;
                        $element = 'Zend_Form_Element_Textarea';
                        break;
                    case 'tinytext':
                        $maxLength = 255;
                        $element = 'Zend_Form_Element_Textarea';
                        break;
                    case 'mediumtext':
                        $maxLength = 16000000;
                        $element = 'Zend_Form_Element_Textarea';
                        break;
                    case 'longtext':
                    case 'blob':
                        //$maxLength = 4200000000;
                        $maxLength = 20000000;
                        $element = 'Zend_Form_Element_Textarea';
                        break;

                    case 'varchar':
                        $maxLength = 255;
                        if( isset( $field['CHARACTER_MAXIMUM_LENGTH'] ) && $field['CHARACTER_MAXIMUM_LENGTH'] <= 50 ) {
                            $element = 'Zend_Form_Element_Text';
                            $maxLength = $field['CHARACTER_MAXIMUM_LENGTH'];
                        } else {
                            $element = 'Zend_Form_Element_Textarea';
                        }
                        break;

                    case 'char':
                        if( isset( $field['CHARACTER_MAXIMUM_LENGTH'] ) && $field['CHARACTER_MAXIMUM_LENGTH'] <= 50 ) {
                            $maxLength = $field['CHARACTER_MAXIMUM_LENGTH'];
                            $element = 'Zend_Form_Element_Text';
                        } else {
                            $element = 'Zend_Form_Element_Text';
                        }
                        break;

                    case 'enum':
                        $maxLength = null;
                        $element = 'Zend_Form_Element_Select';
                        $options = $field['VALUES']; // TODO - use the real key to get the opts from the array

                        $selectFunctionName = $field['COLUMN_NAME'];
                        $selectFunctionName = ucwords( str_replace('_', ' ', $selectFunctionName) );
                        $selectFunctionName = 'populate' . str_replace( ' ', '', $selectFunctionName);
                        $emptyOption = '';
                        if( $field['IS_NULLABLE'] == 'YES' ) {
                            $emptyOption = "\$ele->addMultiOption('', '--Select--');";
                            $emptyOption .= "\$ele->setRequired(false);";
                        }

                        // method to populate the select box
                        $popEnum = array(
                            'name' => $selectFunctionName,
                            'parameters' => array(
                                array('name' => 'keyField', 'defaultValue' => null ),
                                array('name' => 'valueField', 'defaultValue' => null )
                            ),
                            'body' => "\$ele = \$this->getElement('". $ns . '_'. $field['COLUMN_NAME'] . "');
\$options = ".$this->convertToPhpCodeString($options).";
if( count(\$options) ) {
    \$tmp = array();
    foreach(\$options as \$r) {
        \$tmp[ \$r ] = \$r;
    }
  ". $emptyOption . "
  \$ele->addMultiOptions( \$tmp );

}
",
                            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                                'shortDescription' => 'Populate the ' . $ns . '_'. $field['COLUMN_NAME'] . 'field',
                                )),
                        );
                        $baseMethods[] = $popEnum;
                        break;

                    case 'date':
                        $maxLength = 50;
                        $element = 'ZendX_JQuery_Form_Element_DatePicker';
                        break;

                    case 'datetime':
                        $maxLength = 50;
                        $element = 'DDM_Form_Element_DateTimePicker';
                        break;

                    case 'time':
                        $maxLength = 10;
                        $element = 'DDM_Form_Element_TimePicker';
                        break;

                    case 'timestamp':
                        $maxLength = 50;
                        $element = 'Zend_Form_Element_Hidden';
                        break;

                    default:
                        echo "default field? - Scaffold shouldn't have to use a default field type.  ";
                        $maxLength = 255;
                        $element = 'Zend_Form_Element_Text';
                        break;
                }

            }

            // if the label has not been set, do it now.
            if( $label === null ) {
                $label = $this->makeLabelName( $field['COLUMN_NAME'] );
            }

            $eleOptions = array();
            $attribs = array();
            if( $required ) {
                $eleOptions['required'] = $required;
            }
            if( count($validators) ) {
                $eleOptions['validators'] = $validators;
            }
            if( count($filters) ) {
                $eleOptions['filters'] = $filters;
            }
            if( $label !== false ) {
                $eleOptions['label'] = $label;
            }
            if( !empty($maxLength) ) {
                $attribs['maxlength'] = $maxLength;
                $maxLength = null;
            }
            $eleOptions = $this->convertToPhpCodeString($eleOptions);

            $codeCreateElements .= "\$$htmlFieldName = new $element( '$htmlFieldName',\n    $eleOptions\n);";
            if( count($attribs) ) {
                foreach($attribs as $key => $value ) {
                    $codeCreateElements .= "\n\$$htmlFieldName" . "->setAttrib('$key', '$value');\n";
                    if( $key == 'maxlength' ) {
                        if( $value > 1000000 ) {
                            $value = 1000000;
                        }
                        if( !$numeric ) {
                            // stringLength checks type, must be a string. TODO, between validation on numbers.
                            $codeCreateElements .= "\n\$$htmlFieldName" . "->addValidator('stringLength', false, array(0, $value));";
                        }
                    }
                }
            }

            $codeCreateElements .= "\n\$this->addElement(\$$htmlFieldName);\n\n";

        }

        //ppr($codeCreateElements);

        // add a button
        $codeCreateElements .= "\$this->addElement(\n    'submit',\n    'Submit');\n";

        // constructor
        $const = array(
            'name' => '__construct',
            'parameters' => array(array('name' => 'name', 'defaultValue' => null),array('name'=>'formType','defaultValue'=>'horizontal')),
            'body' => "parent::__construct(\$name,\$formType);\n$codeCreateElements",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'The constructor',
                )),
        );
        $baseMethods[] = $const;

        // postProcess
        $actionName = strtolower( $this->makeClassName($tableInfo['TABLE_NAME'], false) );
        $postProcess = array(
            'name' => 'postProcess',
            'body' => "\$redirector = Zend_Controller_Action_HelperBroker::getStaticHelper('redirector');
\$redirector->setGotoUrl('/$actionName/list');",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'What happens after process is successful?',
                )),
        );
        $baseMethods[] = $postProcess;

        // comments
        @$baseDocBlock = "$baseClassName\n\nGenerated class file for table ". $tableInfo['TABLE_SCHEMA'] . '.' . $tableInfo['TABLE_NAME'] . "\nAny changes here will be overridden.";
        $subDocBlock = "Form for ". $tableInfo['TABLE_SCHEMA'] . '.' . $tableInfo['TABLE_NAME'] . "\nThis is where you can add/remove elements, validation, filters, etc";

        // Get Model
// require_once( $modelFile );
        $getModel = array(
            'name' => 'getModel',
            'visibility'   => 'protected',
            'body' => "// get a model
\$model = new $modelClassName();
return \$model;",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'Get the corresponding model',
                'tags'             => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                        'paramName' => 'return',
                        'datatype'  => $modelClassName
                    )),
                ),
                )),
        );
        $baseMethods[] = $getModel;

        $isValidBody = "// Validate
\$ret = parent::isValid(\$data);
\$invalidFieldCount = 0;
";
        if( count($validateFunctions) ) {
            foreach($validateFunctions as $field => $function ) {
                $isValidBody .= "
if( !empty( \$data[ \$this->fieldPrefix . '_$field' ]) && !\$this->$function( \$data[ \$this->fieldPrefix . '_$field' ]) ) {
    \$invalidFieldCount++;
}
";
            }
        }
        $isValidBody .= "
if( \$invalidFieldCount ) {
    \$ret = false;
}
return \$ret;
";

        // Process()
        // array('name' => 'controller')
        $isValid = array(
            'visibility'   => 'public',
            'name' => 'isValid',
            'parameters' => array( array('name' => 'data')  ),
            'body' => $isValidBody,
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
            'shortDescription' => 'validate related fields',
            'tags'             => array(
                new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                    'paramName' => 'data',
                    'datatype'  => 'array'
                )),
            ),
            )),
        );
        // not used yet, but other changes need to go out
        //$baseMethods[] = $isValid;

        // Process()
        // array('name' => 'controller')
        $process = array(
            'visibility'   => 'public',
            'name' => 'process',
            'parameters' => array( array('name' => 'request')  ),
            'body' => "// anything to load?
//ppr(\$request->getUserParams()); exit;
\$pk = 'id';
if( ( \$loadValue = \$request->getParam(\$pk) ) > 0 ) {

    if( \$this->canLoad( \$pk ) ) {

        // load the data for this item
        \$model = \$this->getModel();

        // Zend_Db_Table_Rowset
        \$data = \$model->find( \$loadValue );
        if( \$data->count() == 1 ) {
            // Zend_Db_Table_Row Object
            \$row = \$data->current();
            \$data = \$row->toArray();

            \$populate = array();
            foreach( \$data as \$k => \$v ) {
                \$populate[ \$this->fieldPrefix . '_' . \$k ] = \$v;
            }

            \$this->populate( \$populate ) ;
        }

    }

}

// Process Post
if (\$request->isPost()) {
    \$data = \$request->getParams();

    // validate
    if (\$this->isValid( \$data ) ) {

        // grab the filtered values (isValid populated the elemtns, getValues gets filtered values back)
        \$data = \$this->getValues();

        \$model = \$this->getModel();
        \$splitFields = ".$this->convertToPhpCodeString($splitFields) .";

        try {
            \$timezone = Zend_Registry::get('timezone');
        } catch(Exception \$e) {
            \$timezone = null;
        }

        foreach( \$data as \$key => \$d ) {
            if( strpos( \$key, \$this->fieldPrefix) === 0 ) {
                \$newKey = str_replace( \$this->fieldPrefix . '_', '', \$key);
                if(\$this->getElement(\$key) instanceof DDM_Form_Element_DateTimePicker && !empty(\$timezone)) {
                    \$DT = new DateTime(\$d, new DateTimeZone(\$timezone));
                    \$DT->setTimeZone(new DateTimeZone('UTC'));
                    \$d = \$DT->format('Y-m-d H:i:s');
                }
                if( isset( \$splitFields[\$newKey]) ) {
                    \$appendTo = \$splitFields[\$newKey];
                    \$data[ \$appendTo ] .= ' ' . \$d;
                    unset(\$data[\$newKey]);
                } else {
                    \$data[ \$newKey ] = \$d;
                }
                unset(\$data[\$key]);
                //echo \"set \$newKey set to \$d <BR>\";
            } else {
                //echo \"prefix not found in \$key <BR>\";
            }
        }

        // save if they have permission (or whatever canSave does)
        if( \$this->canSave( \$data ) ) {
            \$return = \$model->save( \$data );
            \$this->postProcess( \$return );
            return \$return;
        }
    }
}
return false;",
    'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
        'shortDescription' => 'Get the corresponding model',
        'tags'             => array(
            new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                'paramName' => 'request',
                'datatype'  => 'Zend_Request'
            )),
            new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                'paramName' => 'controller',
                'datatype'  => 'Zend_Controller'
            )),
            new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                'paramName' => 'controller',
                'datatype'  => 'Zend_Controller'
            )),
            new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                'paramName' => 'return',
                'datatype'  => 'mixed'
            )),
        ),
        )),
);
        $baseMethods[] = $process;

        // Can the form load a piece of data? (a place to extend and check ALCs, etc) extended canLoad should use this->addError
        $canLoad = array(
            'name' => 'canLoad',
            'visibility'   => 'protected',
            'body' => "// can this action be performed?
return true;",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'Can this item be loaded in the form?',
                'tags'             => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                        'paramName' => 'return',
                        'datatype'  => 'boolean'
                    )),
                ),
                )),
        );
        $baseMethods[] = $canLoad;

        // Can the form save a piece of data? (a place to extend and check ALCs, etc) extended canSave should use this->addError
        $canSave = array(
            'name' => 'canSave',
            'visibility'   => 'protected',
            'parameters' => array( array('name' => 'data' ) ),
            'body' => "// can this action be performed?
return true;",
            'docblock'   => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'Can this item be saved?',
                'tags'             => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                        'paramName' => 'return',
                        'datatype'  => 'boolean'
                    )),
                ),
                )),
        );
        $baseMethods[] = $canSave;

        //Populate - if the application has a timezone specification we need to translate datetimes
        $populate = array(
            'name' => 'populate',
            'visibility' => 'public',
            'parameters' => array(array('name' => 'data')),
            'body' => "// update datetimes to acount for timezone translations
try {
    \$timezone = Zend_Registry::get('timezone');
} catch(Exception \$e) {
    \$timezone = null;
}
if(!empty(\$timezone)) {
    foreach(\$data as \$name => \$value) {
        if(!empty(\$value) && \$this->getElement(\$name) instanceof DDM_Form_Element_DateTimePicker) {
            \$DT = new DateTime(\$value, new DateTimeZone('UTC'));
            \$DT->setTimeZone(new DateTimeZone(\$timezone));
            \$data[\$name] = \$DT->format('M d, Y g:i a');
        }
    }
}
return parent::populate(\$data);",
            'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                'shortDescription' => 'populate the form',
                'tags' => array(
                    new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                        'paramName' => 'data',
                        'dataType' => 'array'
                    )),
                    new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                        'paramName' => 'return',
                        'dataType' => 'Zend_Form'
                    ))
                )
            ))
        );
        $baseMethods []= $populate;

        // model info
        $baseProperties[] = array(
                'name'         => 'fieldPrefix',
                'visibility'   => 'protected',
                'defaultValue' => $ns,
                'docblock' => 'Field prefix'
            );

        if( $this->dirForNamespace ) {
            $formDirName = ucwords( str_replace( '/', '', $this->paths['form'] ) );
            $baseClassName = 'Generated_'. $formDirName . '_'. $this->makeNsName( $tableInfo['TABLE_SCHEMA'] ) . "_". $tableInfo['classNamePartial'];
        }

        // Let Zend make the contents of the file
        $base = new Zend_CodeGenerator_Php_Class();
        $base->setName( $baseClassName );
        $base->setDocblock( $baseDocBlock );
        $base->setAbstract( true );
        echo $baseClassName . "<br>";
        if( $baseClassName == 'Generate_Models_DS_User') {
            ppr($baseMethods); exit;
        }
        $base->setMethods( $baseMethods );
        $base->setProperties( $baseProperties );
        $base->setExtendedClass( $this->formClass );
        $baseCode = $base->generate();

        // write the base class
        //$baseCode = "require_once('DDM/Form.php');\n\n" . $baseCode;
        $this->writeCode( $baseFile, $baseCode );

        if( $writeSub ) {
            $sub = new Zend_CodeGenerator_Php_Class();
            $sub->setName( $className );
            $sub->setDocblock( $subDocBlock );
            $sub->setExtendedClass( $baseClassName );

            $subCode = $sub->generate();
            //$subCode = $subRequire . $subCode;

            $this->writeCode( $subFile, $subCode );
        }

        return true;

    }


    /**
     * Generate constants classes from database using information in constants.cfg
     *
     * @param array $tableInfo
     * @return boolean
     */
    protected function makeConstants( $dbName, $tableName, $options ) {

        if(!array_key_exists('key', $options) || !array_key_exists('value', $options)) {
            return false;
        }

        $ns = $this->makeNsName($dbName);
        $classNamePartial = $this->makeClassName($tableName);

        if( $this->dirForNamespace === true ) {
            $constantsDirName = ucwords ( str_replace( '/', '', $this->paths['constants'] ) );
            $className = 'Generated_' . $constantsDirName . '_' . $ns . '_' . $classNamePartial;
            $classFile = $this->projectRoot . $this->paths['generated'] . $this->paths['constants'] . $ns . '/'. $classNamePartial . '.php';
        } else {
            $className = 'Base_' . $ns . '_'. $classNamePartial;
            $classFile = $this->projectRoot . $this->paths['generated'] . $this->paths['constants'] . $className . '.php';
        }

        $sql = 'SELECT `' . $options['key'] . '`, `' . $options['value'] . '`'
            . 'FROM `' . $dbName . '`.`' . $tableName . '`';
        $rows = $this->db->fetchAll($sql);

        $properties = array();
        foreach($rows as $row) {
            $name = strtoupper( str_replace( ' ', '_', preg_replace( '/[^A-z0-9 ]/i', '_', $row[$options['key'] ] ) ) );
            if(array_key_exists('prefix', $options)) {
                $name = strtoupper( str_replace( ' ', '_', preg_replace( '/[^A-z0-9 ]/i', '_', $options['prefix'] ) ) ) . '_' . $name;
            }

            $properties[] = array(
                'name' => $name,
                'defaultValue' => $row[$options['value']],
                'const' => true,
            );
        }

        @$docBlock = "$className\n\nGenerated class constants file for table ". $dbName . '.' . $tableName . "\nAny changes here will be overridden.\n";

        $class = new Zend_CodeGenerator_Php_Class();
        $class->setName( $className );
        $class->setDocblock( $docBlock );
        $class->setProperties( $properties );

        $classCode = $class->generate();

        $this->writeCode( $classFile, $classCode );

        return true;
    }

    /**
     * Map mysql field types to php var types
     *
     * @param string $mysqlFieldType
     */
    protected function fieldTypeToVarType( $mysqlFieldType ) {
        $phpType = 'string';
        switch ($mysqlFieldType) {
            case 'int':
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'bigint':
                $phpType = 'int';
                break;
            case 'float':
            case 'decimal':
            case 'double':
                $phpType = 'float';
                break;
            default:
                break;
        }
        return $phpType;
    }

    /**
     * Write the contents to the file
     *
     * @param string $name
     * @param string $contents
     * @param string $type
     * @return int
     */
    protected function writeCode( $name, $contents, $type='php' ) {
        if( $type == 'php' && strpos($contents, '<?php') === false ) {
            $contents = "<?php\n\n" . $contents;
        }

        // Strip all end of line white space
        $contents = preg_replace('/\s+$/m', "\n", $contents);

        $res = file_put_contents( $name, $contents );
        if( $res ) {
            @chmod( $name, 0755);
        }
        return $res;
    }

    /**
     * Make a Label out of a field name
     *
     * @param string $fieldName
     * @return string
     */
    protected function makeLabelName( $fieldName ) {
        $fieldName = str_replace('_id', '', $fieldName);
        $fieldName = str_replace('_', ' ', $fieldName);
        $fieldName = ucfirst($fieldName);
        return $fieldName;
    }

    /**
     * Converts a value to a php code string
     * @param mixed $value
     * @return string
     */
    protected function convertToPhpCodeString($value) {
        $defaultValue = new Zend_CodeGenerator_Php_Property_DefaultValue(array('value' => $value));
        $code = $defaultValue->generate();
        // Remove trailing ;
        if(substr($code, -1, 1) == ';') {
            $code = substr($code, 0, -1);
        }
        return $code;
    }

}
