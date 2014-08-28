<?php

class DDM_MongoCRUD
{
        
    /**
     * @var Mongo $_m The connection to the db
     * @var MongoDB $_db The database in use
     * @var MongoCollection $_collection The collection in use
     * @var array $db_cred the various credentials to log on
     */ 
    //private $_m; // I don't think this is used
    private $_db;
    private $_collection;
    private $_db_cred;
    
    /**
     * Constructor
     * 
     * @param array  $db_auth         the credentials to login with
     *                                -> array $hosts unbound array of strings of potential host addresses
     *                                -> string $username
     *                                -> string $password
     *                                -> string $replicaSet
     * @param string $db_name         the name of the database
     * @param string $collection_name the name of the collection
     */ 
    public function __construct($db_auth, $db_name, $collection_name)
    {
        $this->_db_cred = $db_auth;
        $this->setDatabase($db_name, $collection_name);
    }
    
    /**
     * this is something to log the mongo exceptions
     * 
     * @param string $message The message to be logged
     * @param string $method  The method that is sending the log message
     * @param string $class   The class that is sending the log message
     * 
     * @return null           This doesn't need to return anything
     */
    private function _log ($message = '', $method = '', $class = '')
    {
        //return;

        $fh = fopen("/tmp/mongo_error.txt", "a+");
        fputs($fh, $class." - ".$method." - ".$message."\n\n");
        fclose($fh);
    }

    /**
     * Find a record and pop off only the top result
     * 
     * @param string $query  query to use to query Mongo
     * @param string $fields fields to be queries
     *
     * @return array the array of the results
     */ 
    public function findOne($query = "{}", $fields = false)
    {
        if ($fields)
            return $this->_collection->findOne($query, $fields);
        else
            return $this->_collection->findOne($query);
    }
        
    /**
     * Perform a Mongo query; return all records
     * 
     * @param string $query    query to use to query Mongo
     * @param string $fields   fields to be queried
     * @param bool   $to_array if true, returns array instead of MongoCursor
     *
     * @return mixed either a cursor or an array of the results
     */ 
    public function find($query, $fields = false, $to_array = false)
    {
        $cursor;
        if ($fields) {
            $cursor = $this->_collection->find($query, $fields);
        } else {
            $cursor = $this->_collection->find($query);
        }
        
        if ($to_array) {
            return iterator_to_array($cursor);
        } else {
            return $cursor;
        }
    }
        
    /**
     * Sets a new collection in the same database. Collection must already exist.
     * 
     * @param string $collection_name name of the collection to use
     *
     * @return int greater than 0 if successful, less than 0 if fails
     */ 
    public function setCollection($collection_name) 
    {
        if (!$this->_checkCollection($this->_db, $collection_name)) {
            throw new UnexpectedValueException("Collection does not exist.");
        }
        $this->_collection = $this->_db->selectCollection($collection_name);
        return 1;
    }
        
    /**
     * Set a new database and collection. Both must already exist.
     * 
     * @param string $db_name         the name of the new database to use
     * @param string $collection_name the name of the new collection to use
     *
     * @return int greater than 0 if successful, less than 0 if fails
     */ 
    public function setDatabase($db_name, $collection_name) 
    {
        $this->_db = $this->_getMongoDB($db_name);
        $this->setCollection($collection_name);
        return 1;
    }
    
    /**
     * Saves an object based on the array provided. Updates if it matches an object
     * 
     * @param array $data    the data of the object to be saved
     * @param array $options the various options of the operation to pass to Mongo
     *                       [optional]
     *
     * @return mixed array if safe/fsync was set or bool showing if data was not empty
     */ 
    public function save($data, $options = false)
    {
        if ($options) {
            if (!isset($options['safe'])) {
                $options['safe'] = true;
            }
            return $this->_collection->save($data, $options);
        }
        else
            return $this->_collection->save($data, array("safe" => true));
    }
        
    /**
     * Returns total number of documents in the collection
     * 
     * @param array $query associative array or object with fields to match [optional]
     * @param int   $limit specifies an upper limit to the number returned [optional]
     * @param int   $skip  specifies a number of results to skip before starting the count
     *                     [optional]
     *
     * @return int
     */ 
    public function count($query = array(), $limit = 0, $skip = 0) 
    {
        return $this->_collection->count($query, $limit, $skip);
    }
    
    /**
     * Removes object(s) based on the criteria provided
     * 
     * @param array $criteria the data of the object(s) to be removed
     * @param array $options  the various options of the operation to pass to Mongo
     *                        [optional]
     *
     * @return mixed array if safe/fsync was set or bool showing if data was not empty
     */ 
    public function remove($criteria, $options = false)
    {
        if ($options) {
            if (!isset($options['safe'])) {
                $options['safe'] = true;
            }
            return $this->_collection->remove($criteria, $options);
        }
        else
            return $this->_collection->remove($criteria, array("safe" => true));
    }
        
    /**
     * Insert object based on the criteria provided
     * 
     * @param array $data    the data of the object to be inserted
     * @param array $options the various options of the operation to pass to Mongo
     *                       [optional]
     *
     * @return mixed array if safe/fsync was set or bool showing if data was not empty
     */ 
    public function insert($data, $options = false)
    {
        if ($options) {
            if (!isset($options['safe'])) {
                $options['safe'] = true;
            }
            $return = $this->_collection->insert($data, $options);
        } else {
            return $this->_collection->insert($data, array("safe" => true));
        }
        return $return;
    }
        
    /**
     * Update object based on the criteria provided
     * 
     * @param array $data       the data of the object to be updated
     * @param array $new_object the new parameters to be inserted
     * @param array $options    the various options of the operation to pass to Mongo
     *                          [optional]
     *
     * @return mixed array if safe/fsync was set or bool showing if data was not empty
     */ 
    public function update($data, $new_object, $options = false)
    {
        if ($options) {
            if (!isset($options['safe'])) {
                $options['safe'] = true;
            }
            return $this->_collection->update($data, $new_object, $options);
        }
        else
            return $this->_collection->update($data, $new_object, array("safe" => true));
    }
    
    /**
     * Sets the slaveOkay query option for a Mongo collection
     * 
     * @param bool $ok if reads should be sent to secondary members of a replica set for all possible queries using this MongoCollection instance
     *
     * @return bool returns the former value of slaveOkay for this instance
     */ 
    public function setSlaveOkay($ok = true)
    {
        // if we  have newer Mongo, set it correctly
        if (class_exists('MongoClient', false))
        {
            return $this->_collection->setReadPreference((!empty($ok) ? MongoClient::RP_SECONDARY_PREFERRED : MongoClient::RP_PRIMARY));
        }
        else
        {
            return $this->_collection->setSlaveOkay($ok);
        }
    }
        
    /**
     * Get slaveOkay setting for this collection
     * 
     * @return bool returns the value of slaveOkay for this instance
     */ 
    public function getSlaveOkay()
    {
        if (class_exists('MongoClient', false))
        {
            return $this->_collection->getReadPreference();
        }
        else
        {
            /* 
             * TODO It makes no sense that this doesn't work. It claims that the getSlaveOkay() method
             *      doesn't exist in the MongoCollection class, but it does! Ultimate lameness.
             */ 
            return $this->_collection->getSlaveOkay();
        }
    }
    /**
     * Execute a findAndModify command in Mongo
     * @param  array $query   the query to find the record
     * @param  array $update  the fields to modify
     * @return array          information regarding the execution of the command
     */
    public function findAndModify($query, $update) {
        return $this->_db->command(
                array(
                "findandmodify" => $this->_collection->getName(),
                "query" => $query,
                "update" => $update
            )
        );
    }

    /**
     * Send a command to MongoDB
     * @param  array $command the array of the command information
     * @return array          the results of the command
     */
    public function command($command) {
        return $this->_db->command($command, array("timeout" => -1));
    }
    
    /**
     * Returns a MongoDB object to interact with the specified database
     * 
     * @param string $db the database to connect to (and permissions to acquire)
     *
     * @return Mongo|MongoDB returns a MongoDB object to use
     */ 
    private function _getMongoDB($db)
    {
        $hosts = $this->_db_cred['hosts'];
        $db_auth = $this->_db_cred;
        $options = array();
        $options['username'] = $db_auth['username'];
        $options['password'] = $db_auth['password'];
        $options['db'] = $db;
        $options['replicaSet'] = $db_auth['replicaSet'];
        
        // Randomizes order of possible hosts so that it doesn't connect to the same host always
        shuffle($hosts);

        $hosts_string = "mongodb://" . implode(',', $hosts);

        if (class_exists('MongoClient', false))
        {
            // MongoClient exists -- using it!!!
            $m = new MongoClient($hosts_string, $options); // connect to a remote host at a given port
            $m->setReadPreference(MongoClient::RP_SECONDARY_PREFERRED);  // default to sharing the load (possible to have stale data)
        }
        else
        {
            // old Mongo class
            $m = new Mongo($hosts_string, $options);
            $m->setSlaveOkay(true);
        }
		
        
        if (!$this->_checkDatabase($m, $db)) {
            throw new UnexpectedValueException("Database does not exist.");
        }
        return $m->selectDB($db);

    }

    /**
     * Checks to see if collection exists
     * 
     * @param MongoDB &$mongo_db       a MongoDB object passed by reference
     * @param string  $collection_name the name of the collection to check
     *
     * @return bool returns true if the collection exists
     */ 
    private function _checkCollection(&$mongo_db, $collection_name)
    {
        $list_collections = $mongo_db->listCollections();
        $count = count($list_collections);
        foreach ($list_collections AS $current) {
            $parts = explode('.', $current);
            $curCollection = $parts[count($parts) - 1];
            if ($curCollection == $collection_name) {
                return true;
            }
        }
        return false;
    }

    /**
     * Checks to see if database exists
     * 
     * @param Mongo  &$mongo  a Mongo object passed by reference
     * @param string $db_name the name of the database to check
     *
     * @return bool returns true if the database exists
     */ 
    private function _checkDatabase(&$mongo, $db_name)
    {
        if (class_exists('MongoClient', false))
        {
            return true;  // need user permissions for listDBs()
        }
        $list_dbs = $mongo->listDBs();
        $found = false;
        $count = count($list_dbs);

        for ($i = 0; $i < $count && !$found; $i++) {
            if ($list_dbs['databases'][$i]['name'] === $db_name) {
                return true;
            }
        }
        return false;
    }

}
