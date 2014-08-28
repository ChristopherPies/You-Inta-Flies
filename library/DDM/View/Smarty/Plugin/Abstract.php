<?php 
/**
 * Smarty plugin base class
 *
 */
abstract class DDM_View_Smarty_Plugin_Abstract {
 
    /**
     * Function storage
     * @var array
     */
    protected $_functionRegistry;
    
    /**
     * Naming pattern
     * @var string
     */
    protected $_namingPattern ='/([a-zA-Z1-9]+)(Function|Block)$/';
 
    /**
     * noop constuctor
     */
    function __construct() {}
 

    /**
     * Gets methods from a class.
     * @return multitype:
     */
    public function getClassFunctionArray() {
        $type = get_class($this);
        $methods = get_class_methods($this);
        foreach($methods as $value) {
            if(preg_match($this->_namingPattern,$value,$matches)) {
                $this->_functionRegistry[$matches[1]] = $type."::".$value;
            }
        }
        return $this->_functionRegistry;
    }
 

    /**
     * Sets the naming pattern
     * @param string $pattern
     */
    public function setNamingPattern($pattern ='/([a-zA-Z1-9]+)(Function|Block)$/') {
         //"/([a-zA-Z1-9]+)Function$/";
         $this->_namingPattern = $pattern;
    }
}
 