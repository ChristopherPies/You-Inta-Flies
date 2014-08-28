<?php

/**
 * Extension to Zend_Filter that also handles recursive filtering of arrays. The
 * constructor will accept any number of arguments, which may be any combination
 * of the following:
 * - One or more instances of Zend_Filter_Interface implementations
 * - One or more callback functions/methods
 *   (e.g., function name string, method name array, or lambda function)
 * - One or more arrays of any combination of these
 *
 * The constructor simply passes its arguments into the public addFilters method
 * which "magically" (and recursively, as necessary) adds these filters to the
 * internal $_filters array. These will be executed in the order added once the
 * filter method is called.
 *
 * This class provides an extremely flexible interface to the Zend_Filter
 * stack, yet can be used
 *
 * Note: Callbacks are stored in Zend_Filter_Callback objects.
 *
 * EXAMPLES:
 *
 * 1. Filter a single integer
 *
 * $filterInt = new DDM_Filter(new Zend_Filter_Int());
 * $myInt = $filterInt->filter($myInt);
 *
 * 2. Filter a single float
 *
 * $filterFloat = new DDM_Filter( function($val){ return (float) $val; } );
 * $myFloat = $filterFloat->filter($myFloat);
 *
 * 3. Filter an array of integers
 *
 * $filterInt = new DDM_Filter( function($val){ return (int) $val; } );
 * $integerArray = $filterInt->filter($integerArray);
 *
 * 4. Filter an array of strings that must contain only letters and be less or
 *    equal to five characters long and be in all UPPERCASE
 *
 * $callback1 = function($val) { return substr(0, 5, $val); };
 *
 * $ddmFilter = new DDM_Filter(new Zend_Filter_Alpha());
 * $ddmFilter->addFilters(array( function($val){ return strtoupper($val); }, $callback1 ));
 *
 * $stringsArray = $ddmFilter->filter($stringsArray);
 *
 * 5. Filter a single string that must be alphanumeric
 *
 * $ddmFilter = new DDM_Filter(array( new Zend_Filter_Alnum() ));
 * $someStringValue = DDM_Filter::filter($someStringValue);
 *
 * 6. Filter a recursive array of string values to be in the format 'USD 25.00'
 *
 * $callback2 = function($value) { return '$'.number_format((float) $value, 2); };
 * $ddmFilter = new DDM_Filter(array( new Zend_Filter_Int(), $callback2 ), function($value) {
 *     return str_replace('$', 'USD ', $value);
 * });
 *
 */
class DDM_Filter extends Zend_Filter
{

    /**
     * Constructs a new Filter object
     *
     * The constructor also (optionally) accepts a Filter Arguments List, which
     * is passed on to the addFilters method.
     *
     * @param   mixed   Filter Arguments List (see method 'addFilters') OPTIONAL
     */
    public function __construct() {

        $args = func_get_args();

        $this->addFilters($args);
    }

    /**
     * Adds Filtering Rules
     *
     * Before becoming usful, a DDM_Filter object needs a List of Filter
     * Arguments. The Filter Arguments List is a variable number of arguments,
     * any one of which can be:
     * 1. a Zend_Filter object
     * 2. a PHP 5.3 callback function (anonymous or lambda function)
     * 3. a traditional PHP callback function string or array
     * 4. or array of any number of items 1-4
     *
     * Uses Zend_Filter_Callback to handle callbacks for custom filtering, but
     * also accepts any object implementing on Zend_Filter_Interface.
     *
     * @param   mixed   Filter Arguments List (see above)
     */
    public function addFilters($filter)
    {

        if ($filter instanceof Zend_Filter_Interface) {
            return parent::addFilter($filter);
        }

        // Allow for easily adding callback filters

        if (is_callable($filter)) {

            return parent::addFilter(new Zend_Filter_Callback($filter));
        }

        // Allow for recursive adding of filters

        if (is_array($filter)) {

            foreach ($filter as $filterItem) {
                $this->addFilters($filterItem);
            }

            return $this;
        }

        throw new \Exception("Filter Exception: Invalid Zend_Filter type");
    }

    /**
     * Perform the Filtering operation
     *
     * The Filtering rules are applied to a value OR an array of values, and the
     * filtered result is returned.
     *
     * Unlike the standard Zend_Filter API, this method handles arrays,
     * including multi-dimensional arrays.
     *
     * The method also accepts an optional depth_limit argument for preventing
     * infinite recursion, which can occur in scenarios involving self-
     * referencing arrays. If the depth_limit is exceeded, an Exception is
     * thrown, aborting the Filtering operation. (A limit of "0" will accept a
     * non-array value, but will throw an exception if the value is an array,
     * even if it is empty.)
     *
     * @param mixed $value  The value or array of values to be filtered
     * @param int   Maximum array depth to filter to    OPTIONAL
     *
     * @return mixed The filtered value or array of values
     */
    public function filter($value, $depth_limit = null) {

        if (is_array($value)) {

            if (null !== $depth_limit && $depth_limit < 1) {
                throw new \Exception("Filter Exception: Maximum Array Depth Exceeded");
            }

            foreach ($value as $i=>$val) {

                $value[$i] = $this->filter($val, ( null !== $depth_limit ? $depth_limit - 1 : null ));
            }

            return $value;

        }

        if (null !== $depth_limit && $depth_limit < 0) {
            throw new \Exception("Filter Exception: Maximum Value Depth Exceeded");
        }

        return parent::filter($value);
    }
}
