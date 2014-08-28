<?php

class DDM_FieldMap
{
    const FIELDS       = 'fields';
    const ALIASES      = 'aliases';
    const CONSTRAINTS  = 'constraints';
    const MAP          = 'map';
    const OPTIONS      = 'options';
    const FILTERS      = 'filters';
    const VALIDATORS   = 'validators';
    const REQUIRED     = 'required';

    /**
     * Holds field names and default values
     *
     * supported format:
     * -
     * - {field-name} => {default-value}
     *
     * @var array
     */
    protected $_fields = array();

    /**
     * Holds field aliases
     *
     * supported format:
     * -
     * - {alias-name} => {field-value}
     *
     * @var array
     */
    protected $_aliases = array();

    /**
     * Holds field constraints
     *
     * supported format:
     * -
     * - {field-name} => {int | array(filters, validators, required)}
     *
     * @var array
     */
    protected $_constraints = array();

    /**
     * Holds field map
     *
     * supported format:
     * -
     * - {field-name} => {mapped-field-name}
     * - {field-name} => null Setting the mapped-field-name to null mutes the field
     *
     * @var array
     */
    protected $_map = array();

    /**
     * Stores validation messages
     *
     * @var array
     */
    protected $_validationMessages = array();

    /**
     * Numer of fields with validators
     *
     * @var boolean
     */
    protected $_validatedFieldCount = 0;

    /**
     * Number of fields with filters
     *
     * @var boolean
     */
    protected $_filteredFieldCount = 0;

    /**
     * Validate object used to determine if field id empty
     *
     * @var Zend_Validate_Abstract
     */
    protected $_notEmptyValidator = null;

    /**
     * Default empty type will filter out null fields
     *
     * @var mixed
     */
    protected $_emptyType = Zend_Validate_NotEmpty::NULL;

    /**
     * Constructor
     *
     * supported fields for $fieldData
     * -
     * - fields                = array
     * - aliases               = array
     * - constraints           = array
     * - map                   = array
     * - options               = array
     *
     * NOTE: options is a convenience field and is mapped to $config only if $config not specified
     *
     * supported format for fields:
     * -
     * - {field-name} => {default-value}
     *
     * supported format for aliases:
     * -
     * - {alias-name} => {field-value}
     *
     * supported format for constraints:
     * -
     * - {field-name} => {int | array(filters, validators, required)}
     *
     * supported format for map:
     * -
     * - {field-name} => {mapped-field-name}
     * - {field-name} => null Setting the mapped-field-name to null mutes the field
     *
     * supported fields for $config:
     * -
     * - emptyType             = mixed
     * - notEmptyValidator     = Zend_Validate_Abstract should return false on empty fields
     *
     * @param array $fieldData
     * @param array $config
     */
    public function __construct($fieldData=array(), $config=null)
    {
        $this->setFieldData($fieldData);

        // Allow for options to be passed in $fieldData to enhance readability
        if (is_null($config) && isset($fieldData[self::OPTIONS])) {
            $config = $fieldData[self::OPTIONS];
        }

        if (! is_null($config)) {
            $this->setOptions($config);
        }

    }

    /**
     * Sets Field Data
     *
     * supported fields for $fieldData
     * -
     * - fields                = array
     * - aliases               = array
     * - constraints           = array
     * - map                   = array
     * - options               = array
     *
     * NOTE: options is a convenience field and is mapped to $config only if $config not specified
     *
     * supported format for fields:
     * -
     * - {field-name} => {default-value}
     *
     * supported format for aliases:
     * -
     * - {alias-name} => {field-value}
     *
     * supported format for constraints:
     * -
     * - {field-name} => {int | array(filters, validators, required)}
     *
     * supported format for map:
     * -
     * - {field-name} => {mapped-field-name}
     * - {field-name} => null Setting the mapped-field-name to null mutes the field
     *
     * @param unknown_type $fieldData
     */
    public function setFieldData($fieldData)
    {
        if (! is_array($fieldData)) {
            return;
        }

        // Set fields
        if (isset($fieldData[self::FIELDS])) {
            $this->setFields($fieldData[self::FIELDS]);
        }

        // Set aliases
        if (isset($fieldData[self::ALIASES])) {
            $this->setAliases($fieldData[self::ALIASES]);
        }

        // Set constraints
        if (isset($fieldData[self::CONSTRAINTS])) {
            $this->setConstraints($fieldData[self::CONSTRAINTS]);
        }

        // Set map
        if (isset($fieldData[self::MAP])) {
            $this->setMap($fieldData[self::MAP]);
        }

    }

    /**
     * Getter for fields
     *
     * @return array
     */
    public function getFields()
    {
        return $this->_fields;
    }

    /**
     * Setter for fields
     *
     * @param array $fields
     */
    public function setFields($fields)
    {
        $this->_fields = $fields;
    }

    /**
     * Getter for aliases
     *
     * @return array
     */
    public function getAliases()
    {
        return $this->_aliases;
    }

    /**
     * Setter for aliases
     *
     * @param array $aliases
     */
    public function setAliases($aliases)
    {
        $this->_aliases = $aliases;
    }

    /**
     * Retrieve constraint object for specified field
     *
     * @param string $field
     * @return mixed int|DDM_FieldMap_Constraint on success false otherwise
     */
    public function getConstraint($field)
    {
        if (! isset($this->_constraints[$field])) {
            return false;
        }

        return $this->_loadConstraint($field);
    }

    /**
     * Allow filters and validators to be defined at runtime
     *
     * supported fields for constraints:
     * -
     * - filters      = array
     * - validators   = array
     * - required     = boolean
     *
     * @param string $field
     * @param mixed int|array|DDM_FieldMap_Constraint_Interface $constraint
     */
    public function setConstraint($field, $constraint)
    {
        $this->_setConstraint($field, $constraint);
    }

    /**
     * Getter for constraints
     *
     * @return array
     */
    public function getConstraints()
    {
        return $this->_constraints;
    }

    /**
     * Setter for constraints
     *
     * @param array $constraints
     */
    public function setConstraints($constraints)
    {
        $this->_constraints = $constraints;

        $this->_initFieldConstraintCounts();
    }

    /**
     * Have any fields been defined
     *
     * @return boolean
     */
    public function hasFields()
    {
        return (! empty($this->_fields));
    }

    /**
     * Have any aliases been defined
     *
     * @return boolean
     */
    public function hasAliases()
    {
        return (! empty($this->_aliases));
    }

    /**
     * Do any of the constraints have associated filters
     *  @return boolean true if at least one constraint has a filter
     */
    public function hasFilters()
    {
        return ($this->_filteredFieldCount > 0);
    }

    /**
     * Do any of the constraints have associated validators
     *  @return boolean true if at least one constraint has a validator
     */
    public function hasValidators()
    {
        return ($this->_validatedFieldCount > 0);
    }

    /**
     * Getter for map
     *
     * @return array
     */
    public function getMap()
    {
        return $this->_map;
    }

    /**
     * Setter for map
     *
     * @param array
     */
    public function setMap($map)
    {
        $this->_map = $map;
    }

    /**
     * Getter for Api Error Response
     *
     * @return array
     */
    public function getValidationMessages()
    {
        return $this->_validationMessages;
    }

    /**
     * Sets empty type used to test for filtering empty fields
     *
     * @param mixed $type
     */
    public function setEmptyType($type)
    {
        $this->_emptyType = $type;

        if (! is_null($this->_notEmptyValidator)) {
            $this->getNotEmptyValidator()->setType($type);
        }

    }

    /**
     * Returns the current empty type used to test for filtering empty fields
     *
     * @return int
     */
    public function getEmptyType()
    {
        return $this->getNotEmptyValidator()->getType();
    }

    /**
     * Lazy load NotEmpty object
     *
     * @return Zend_Validate_Abstract
     */
    public function getNotEmptyValidator()
    {
        if (is_null($this->_notEmptyValidator)) {
            $this->_notEmptyValidator = new Zend_Validate_NotEmpty(array(
                'type' => $this->_emptyType
            ));
        }

        return $this->_notEmptyValidator;
    }

    /**
     * Setter for notEmtpyValidator
     *
     * @param Zend_Validate_Interface $validator
     */
    public function setNotEmptyValidtaor(Zend_Validate_Interface $validator)
    {
        $this->_notEmptyValidator = $validator;
    }

    /**
     * Allows for model configuration at runtime
     *
     * Supported fields for $config
     * -
     * - emptyType         = mixed
     * - notEmptyValidator = Zend_Validate_Abstract should return false on empty fields
     *
     * (non-PHPdoc)
     * @see Zend_Validate_NotEmpty for empty types
     *
     * @param mixed $config
     */
    public function setOptions($config)
    {
        if ($config instanceof Zend_Config) {
            $config = $config->toArray();
        }

        // Configure empty validator
        if (isset($config['emptyType'])) {

            $this->_emptyType = $config['emptyType'];
        }

        // Allow emptyValidator object override
        if (isset($config['notEmptyValidator'])) {
            $this->setNotEmptyValidtaor($config['notEmptyValidator']);
        }
    }

    /**
     * Filters $inputParams based on permitted params specified in params array.
     * If there are params not specified the default value will be used.
     *
     * NOTE: fieldMap relies on the Zend_Validate_NotEmpty object to determine if a field is
     * empty. By default the isEmpty checkout will return true for fields set to null.
     * This behavior can be overridden by setting the emptyType.
     *
     * @param array $inputFields
     * @param boolean $filterEmpty if true empty fields will be unset
     * @param boolean $applyAliases if true aliases will be mapped prior to filtering
     * @return array filtered result set
     */
    public function filterFields($inputFields, $filterEmpty=false, $applyAliases=true)
    {
        if ($applyAliases && ! empty($this->_aliases)) {
            $inputFields = $this->mapFields($inputFields, self::ALIASES);
        }

        $filtered = array_intersect_key($inputFields, $this->_fields);

        // Adds default values if not set
        $filtered += $this->_fields;

        // Filter null values
        if ($filterEmpty) {

            $notEmptyValidator = $this->getNotEmptyValidator();

            foreach ($filtered as $key => $val) {
                if (! $notEmptyValidator->isValid($val)) {
                    unset($filtered[$key]);
                }
            }
        }

        // Apply filters if they exist
        if ($this->_filteredFieldCount) {

            foreach($this->_constraints as $field => $constraint) {

                // Lazy Load Constraints
                $constraint = $this->_loadConstraint($field, $constraint);

                if (! is_numeric($constraint)
                    && array_key_exists($field, $filtered)
                    && $constraint instanceof DDM_FieldMap_Constraint_Interface
                    && $constraint->hasFilters()) {

                    $constraint->setValue($filtered[$field]);
                    $filtered[$field] = $constraint->getValue();
                }
            }
        }

        return $filtered;

    }

    /**
     * Maps single field name using alias table
     *
     * @param string $alias
     * @return string if mapping exist mapped field will be returned
     *         otherwise $alias returned unchanged
     */
    public function getFieldFromAlias($alias)
    {
        // Look up field name
        if (isset($this->_aliases[$alias])) {
            $alias = $this->_aliases[$alias];
        }

        return $alias;
    }

    /**
     * Maps input fields according to map array
     *
     * Supported format for array $map:
     * -
     * - {field-name} => {mapped-field-name}
     * - {field-name} => null Setting the mapped-field-name to null mutes the field
     *
     * @param array $inputFields
     * @param mixed $map array mapping or string 'aliases' | 'map'
     * @param boolean $reverseMap If true it will call array_flip() on the map before mapping the fields
     * @return array mapped result set
     */
    public function mapFields($inputFields, $map=self::MAP, $reverseMap=false)
    {
        if (is_null($map)) {
            $map = self::MAP;
        }

        if (is_string($map)) {
            $propertyName = '_' . $map;
            if (isset($this->{$propertyName})) {
                $map = $this->{$propertyName};
            }
        }

        if (empty($map) || ! is_array($map)) {
            return $inputFields;
        }

        if ($reverseMap) {
            $map = array_flip($map);
        }

        foreach ($map as $field => $mappedField) {
           if ($field != $mappedField && array_key_exists($field, $inputFields)) {
               if (! is_null($mappedField)) {
                   $inputFields[$mappedField] = $inputFields[$field];
               }
               unset($inputFields[$field]);
           }
        }

        return $inputFields;
    }

    /**
     * Maps input field names according to map array
     *
     * Supported format for array $map:
     * -
     * - {field-name} => {mapped-field-name}
     * - {field-name} => null Setting the mapped-field-name to null mutes the field
     *
     * @param array $fieldNames
     * @param mixed $map array mapping or string 'aliases' | 'map'
     * @param boolean $skipIfNotExists If a given field name does not exist in the map, should we
     *                                 return it as is, or skip over it, leaving it out of the result
     * @return array mapped result set
     */
    public function mapFieldNames($fieldNames, $map=self::MAP, $skipIfNotExists=false)
    {
        if (is_null($map)) {
            $map = self::MAP;
        }

        if (is_string($map)) {
            $propertyName = '_' . $map;
            if (isset($this->{$propertyName})) {
                $map = $this->{$propertyName};
            }
        }

        if (empty($map) || ! is_array($map)) {
            return $fieldNames;
        }

        $aliases = $map;
        $mappedFields = array();
        foreach($fieldNames as $f) {
            if(array_key_exists($f, $aliases)) {
                $mappedFields[] = $aliases[$f];
            }
            if(!$skipIfNotExists) {
                $mappedFields[] = $f;
            }
        }

        return $mappedFields;
    }


    /**
     * Checks to make sure required params are present
     *
     * @param array $inputParams
     * @return boolean true on success false otherwise
     */
    public function isValid($inputFields)
    {

        $valid = true;
        $messages = array();

        $missingFields = array_diff_key($this->_constraints, $inputFields);

        // Check for field with constraints that are not required
        foreach($missingFields as $field => $constraint) {
            if (! is_numeric($constraint)
                || ($constraint instanceof DDM_FieldMap_Constraint_Interface && ! $constraint->isRequired())) {
                unset($missingFields[$field]);
            }
        }

        if (! empty($missingFields)) {
            // Build error response
            $messages['missingField'] =
                'Missing required field' . ((count($missingFields) > 1) ? 's' : '') . ': '
                . implode(', ', array_keys($missingFields));
        }


        // Append defaults
        $inputFields += $this->_fields;

        // Use Zend_Form_Element to apply filters and validators
        if ($this->_validatedFieldCount) {

            foreach($this->_constraints as $field => $constraint) {

                // Lazy Load Constraints
                $constraint = $this->_loadConstraint($field, $constraint);

                if (! is_numeric($constraint)
                    && $constraint instanceof DDM_FieldMap_Constraint_Interface
                    && $constraint->hasValidators()) {

                    if (! $constraint->isValid(isset($inputFields[$field]) ? $inputFields[$field] : null)) {
                        $valid = false;
                        $vMessages = $constraint->getMessages();

                        if (! empty($vMessages)) {
                            if (! isset($messages[$field])) {
                                $messages[$field] = array();
                            }

                            $messages[$field] = array_merge_recursive($messages[$field], $vMessages);
                        }
                    }
                }
            }
        }

        // Store messages for retrieval
        if (! empty($messages)) {
            $this->_validationMessages = $messages;
        }

        return $valid;

    }

    /**
     * Returns fieldData as array
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::FIELDS       => $this->_fields,
            self::ALIASES      => $this->_aliases,
            self::CONSTRAINTS  => $this->_constraints,
            self::MAP          => $this->_map
        );
    }

    /**
     * Allows for simplified property setter
     *
     * @param string $name
     * @param mixed $value
     * @throws Exception if attempting to set new property
     */
    public function __set($name, $value)
    {
        // Check for setter method
        $method = 'set' . ucfirst($name);

        if (method_exists($this, $method)) {
            $this->{$method}($value);
        } else {
            throw new DDM_FieldMap_Exception('Invalid property: \'' . $name . '\'. You cannot set new properties on this object.');
        }

    }

    /**
     * Allows for simplified property getter
     *
     * @param string $name
     * @param mixed field value/object or null if field name is invalid
     */
    public function __get($name)
    {
        // Check for getter method
        $method = 'get'. ucfirst($name);

        if (method_exists($this, $method)) {
            return $this->{$method}();
        } else {
            throw new DDM_FieldMap_Exception('Invalid property: \'' . $name . '\'.');
        }
    }

    /**
     * Attempts to set up constraints (Validators and Filters) for individual field
     *
     * @param string $field
     */
    protected function _loadConstraint($field)
    {
        if (isset($this->_constraints[$field]) && is_array($this->_constraints[$field])) {
            $this->_setConstraint($field, $this->_constraints[$field]);
        }

        return $this->_constraints[$field];
    }

    /**
     * Counts fields that are for validated and filtered.
     *
     */
    protected function _initFieldConstraintCounts()
    {

        $this->_filteredFieldCount = 0;
        $this->_validatedFieldCount = 0;

        foreach($this->_constraints as $field => $options) {
            if (is_array($options)) {
                if (! empty($options[self::FILTERS])) {
                    $this->_filteredFieldCount++;
                }

                if (! empty($options[self::VALIDATORS]) || ! empty($options[self::REQUIRED])) {
                    $this->_validatedFieldCount++;
                }
            }
        }

    }

    /**
     * Add field constraint to field existing constraints will be replaced
     *
     * @param string $field
     * @param mixed int|array|DDM_FieldMap_Constraint_Interface $options
     */
    protected function _setConstraint($field, $options)
    {
        $curConstraint = (isset($this->_constraints[$field]))? $this->_constraints[$field] : null;
        $curConstraintInitialized = ($curConstraint instanceof DDM_FieldMap_Constraint_Interface);

        // Update counts
        if ($curConstraintInitialized) {
            if ($currentConstraint->hasFilters()) {
                $this->_filteredFieldCount--;
            }

            if ($currentConstraint->hasValidators()) {
                $this->_validatedFieldCount--;
            }
        }

        if (is_array($options)) {
            if ($curConstraintInitialized) {
                $curConstraint->setOptions($options);
                $constraint = $curConstraint;
            } else {
                $constraint = new DDM_FieldMap_Constraint($field, $options);
            }
        } else if (is_int($options) || $options instanceof DDM_FieldMap_Constraint_Interface) {
            $constraint = $options;
        } else {
            throw new DDM_FieldMap_Exception('Invalid Constraint type');
        }

        // Update counts
        if ($constraint instanceof DDM_FieldMap_Constraint_Interface) {
            if ($constraint->hasFilters()) {
                $this->_filteredFieldCount++;
            }

            if ($constraint->hasValidators()) {
                $this->_validatedFieldCount++;
            }
        }

        $this->_constraints[$field] = $constraint;

    }

}