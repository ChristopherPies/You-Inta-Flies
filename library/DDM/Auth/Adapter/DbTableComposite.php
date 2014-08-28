<?php
/**
 * This class allows for a composite key in your auth table.  Your $_identityColumn can be either a string or an array,
 * just make sure your $_identity matches - (so $_identityColumn[0] is the column name for the value in $_identity[0] and etc)
 *
 * @author sdickson
 */
class DDM_Auth_Adapter_DbTableComposite extends Zend_Auth_Adapter_DbTable
{
    /**
     * $_identityColumn - the column to use as the identity
     *
     * @var string|array
     */
    protected $_identityColumn = null;

    /**
     * $_identity - Identity value
     *
     * @var string|array
     */
    protected $_identity = null;

    /**
     * creates a select for the identity and accounts for the identity being an array or string (to support composite keys)
     * @return type
     */
    protected function _authenticateCreateSelect() {
        $select = parent::_authenticateCreateSelect();
        if(is_array($this->_identityColumn) && is_array($this->_identity)) {
            $select->reset(Zend_Db_Select::WHERE);
            foreach ($this->_identityColumn as $key=>$value) {
                $select->where("$value = ?",$this->_identity[$key]);
            }
        }
        return $select;
    }
}
?>
