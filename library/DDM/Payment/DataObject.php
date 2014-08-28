<?php

/**
 * A class for creating data objects with automatic getters and setters
 * for all properties in the $data array.
 *
 * You can access $this->data[key] with $this->getKey() and $this->setKey().
 * You can use $this->getData() and $this->setData() to get and set all data or
 * a subset of the data. Note that child class methods are used (if set) even
 * when you call $this->getData() and $this->setData(). If you want to change a
 * value without any of this magic, set the value directly in $this->data.
 *
 * Additionally, the $dataClassMap allows you to define child objects
 * that also inherit this class so that nested array data passed to the parent
 * class can automatically be used to construct the child objects. The class map
 * is applied at construction and any time you call setData() or setKeyName()
 * virtual methods.
 */
abstract class DDM_Payment_DataObject
{

    /**
     * An array of data that can be accessed with auto getters/setters.
     *
     * @var array
     */
    protected $data = array();

    /**
     * An array of data that will be merged with $data on construction. Useful
     * to provide child classes with extra data without having to override the
     * parent class $data array.
     *
     * @var array
     */
    protected static $dataExtended = array();

    /**
     * The $data key of the id field if it something other than 'id'. This allows
     * objects to have setId/getId aliases even if the underlying id field
     * name needs to be different. Leave this null to disable aliasing.
     *
     * @var string
     */
    protected static $dataIdAlias = null;

    /**
     * A map of $data keys to class names. This allows automatic conversion of
     * data arrays to classes when using setData or associated virtual methods.
     * Key names should match $data keys, and you can use the 'keyName[]' syntax
     * to create an array of the specified class.
     *
     * Example:
     * array('key1' => 'myClass', 'key2[]' => 'myClass2')
     *
     * @var array
     */
    protected static $dataClassMap = array();

    /**
     * Constructor
     *
     * @param array $data OPTIONAL
     */
    public function __construct(array $data = null)
    {
        $this->data = array_merge($this->data, static::$dataExtended);

        if (static::$dataIdAlias && !array_key_exists(static::$dataIdAlias, $this->data)) {
            $this->data[static::$dataIdAlias] = null;
        }

        if (!empty($data)) {
            $this->setData($data);
        }
    }

    /**
     * Overidden to provide auto getters/setters when the method is not found.
     *
     * @param string $name
     * @param type $arguments
     *
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        $prefix = substr($name, 0, 3);

        if ($prefix === 'get' || $prefix === 'set') {
            $dataKey = lcfirst(substr($name, 3));
            if (array_key_exists($dataKey, $this->data)) {
                if ($prefix === 'set') {
                    if (count($arguments) !== 1) {
                        throw new Exception('Invalid arguments for ' . $name);
                    }
                    $value = reset($arguments);
                    $this->applyDataClassMap($dataKey, $value);
                    $this->data[$dataKey] = $value;

                    return $this; // fluent interface
                } else {
                    return $this->data[$dataKey];
                }
            }
        }

        throw new Exception($name . ' is not a valid method.');
    }

    /**
     * Returns object data
     *
     * @param null|string|array $key Can be null for all values, a single key,
     *                               or an array of keys. OPTIONAL
     * @param boolean $recursive Call getData() on eligible child objects. OPTIONAL
     * @param boolean $useGetter Whether to check the object for a getKeyName
     *                           method instead of returning $this->data[keyName].
     *                           There are certain rare cases where this must be
     *                           false to prevent recursion, such as calling
     *                           parent::getData(keyName) from a child object's
     *                           getKeyName() method (which seems like a bad
     *                           idea anyway).
     *
     * @return mixed
     */
    public function getData($key = null, $recursive = false, $useGetter = true)
    {
        $returnArray = !$key || is_array($key);
        if (!$key) {
            $key = array_keys($this->data);
        } else if (!is_array($key)) {
            $key = array($key);
        }

        $data = array();
        foreach ($key as $k) {
            if (array_key_exists($k, $this->data)) {
                $getter = 'get' . ucfirst($k);
                if ($useGetter && method_exists($this, $getter)) {
                    $data[$k] = $this->$getter();
                } else {
                    $data[$k] = $this->data[$k];
                }
            } else if (static::$dataIdAlias && $k === 'id') {
                $data['id'] = $this->data[static::$dataIdAlias];
            }
        }

        if (!$returnArray) {
            if (count($data) === 0) {
                throw new Exception('Invalid data key ' . reset($key));
            }
            $data = reset($data);
        }

        // call getData recursively
        if ($recursive) {
            if ($data instanceof self) {
                $data = $data->toArray();
            } else if (is_array($data)) {
                foreach ($data as $key => &$value) {
                    if ($value instanceof self) {
                        $value = $value->toArray();
                    } else if (is_array($value)) {
                        foreach ($value as &$arrayValue) {
                            if (!$arrayValue instanceof self) {
                                break;
                            }
                            $arrayValue = $arrayValue->toArray();
                        }
                    }
                }
            }
        }

        return $data;
    }

    /**
     * Sets object data, either with a single key/value or an array of key/values
     *
     * @param mixed $data A key to set, or an array of key/value pairs
     * @param mixed $value The value for the key, or null if $key is an array. OPTIONAL
     * @param boolean $useGetter Whether to check the object for a setKeyName
     *                           method instead of setting $this->data[keyName].
     *                           There are certain rare cases where this must be
     *                           false to prevent recursion, such as calling
     *                           parent::setData(keyName) from a child object's
     *                           setKeyName() method (which seems like a bad
     *                           idea anyway).
     *
     * @return $this
     */
    public function setData($key, $value = null, $useSetter = true)
    {
        $this->applyDataClassMap($key, $value);

        if (!is_array($key)) {
            $key = array($key => $value);
        }

        if (static::$dataIdAlias) {
            if (array_key_exists(static::$dataIdAlias, $key)) {
                $key['id'] = $key[static::$dataIdAlias];
            } else if (array_key_exists('id', $key)) {
                $key[static::$dataIdAlias] = $key['id'];
            }
        }

        foreach ($key as $k => $v) {
            if (array_key_exists($k, $this->data)) {
                $setter = 'set' . ucfirst($k);
                if ($useSetter && method_exists($this, $setter)) {
                    $this->$setter($v);
                } else {
                    $this->data[$k] = $v;
                }
            }
        }

        return $this; // fluent interface
    }

    /**
     * Gets all object data as an array.
     * Convenience method for calling getData() with key=null and recursive=true.
     *
     * @return array
     */
    public function toArray(array $arrayKeyMapper = null)
    {
        $output = $this->getData(null, true);

        if ($arrayKeyMapper !== null) {
            $output = $this->applyArrayKeyMap($output, $arrayKeyMapper);
        }

        return $output;
    }

    /**
     * Sets all object data from an array.
     * Convenience method for calling setData() with an array.
     *
     * @param array $array
     *
     * @return $this
     */
    public function fromArray(array $array)
    {
        return $this->setData($array);
    }

    /**
     * Applies the data class map to the data being set
     *
     * @param mixed $key
     * @param mixed $value
     *
     * @return $this
     */
    protected function applyDataClassMap(&$key, &$value)
    {
        foreach (static::$dataClassMap as $classKey => $className) {
            $isClassArray = substr($classKey, count($classKey)-3) === '[]';
            if ($isClassArray) {
                $classKey = substr($classKey, 0, count($classKey)-3);
            }

            if ($key == $classKey) {
                $valueRef =& $value;
            } else if (is_array($key) && isset($key[$classKey])) {
                $valueRef =& $key[$classKey];
            }

            if (!empty($valueRef) && is_array($valueRef)) {
                if ($isClassArray) {
                    if (!isset($valueRef[0])) {
                        $valueRef = array($valueRef);
                    }
                    foreach ($valueRef as &$valueRefItem) {
                        if (is_array($valueRefItem)) {
                            $valueRefItem = new $className($valueRefItem);
                        }
                    }
                } else {
                    $valueRef = new $className($valueRef);
                }
            }

            unset($valueRef);
        }

        return $this;
    }

    /**
     * Rename keys in an array based on a mapper
     *
     * @param array $array
     * @param array $mapper
     *
     * @return array
     */
    public function applyArrayKeyMap(array $array, array $mapper)
    {
        foreach ($mapper as $from => $to) {
            if (!array_key_exists($from, $array)) {
                continue;
            }

            // Keys being mapped to false mean do not include in the output array
            if ($to === false) {
                unset($array[$from]);
                continue;
            }

            if (is_array($to)) {
                $mappedTo = $this->applyArrayKeyMap($array[$from], $to);
                $array[$from] = $mappedTo;

                // Check for __SELF__ to see if we also need to rename $from
                if (array_key_exists('__SELF__', $to)) {
                    unset($array[$from]);

                    if ($to['__SELF__'] === null) {
                        // If it's null, we lose the parent key, and merge in the child array
                        $array = array_merge($array, $mappedTo);
                    } else {
                        $array[$to['__SELF__']] = $mappedTo;
                    }
                }
            } else {
                $array[$to] = $array[$from];
                if ($to !== $from) {
                    unset($array[$from]);
                }
            }
        }
        return $array;
    }


    /**
     * Gets the id of the object
     *
     * @return int
     */
    public function getId()
    {
        if (static::$dataIdAlias) {
            return $this->data[static::$dataIdAlias];
        }
        if (array_key_exists('id', $this->data)) {
            return $this->data['id'];
        }

        throw new Exception('getId is not a valid method.');
    }

    /**
     * Sets the id of the object
     *
     * @param int $id
     */
    public function setId($id)
    {
        if (static::$dataIdAlias) {
            $this->data[static::$dataIdAlias] = $id;
            if (array_key_exists('id', $this->data)) {
                $this->data['id'] = $id;
            }
        } else if (array_key_exists('id', $this->data)) {
            $this->data['id'] = $id;
        } else {
            throw new Exception('setId is not a valid method.');
        }

        return $this;
    }

}
