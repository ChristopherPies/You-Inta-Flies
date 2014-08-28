<?php

class DDM_Db_Adapter_Mysqli extends Zend_Db_Adapter_Mysqli
{
    /**
     * Current Transaction Level Counter
     * 
     * This little guy provides the magic of nested Transactions. The counter
     * is incremented anytime beginTranscation() is called, and decremented
     * (if greater than 1) whenever commit() or rollback() are called. This
     * allows us to wrap multiple layers of "Transactions" in a single "real"
     * transaction, as the transaction continues even when
     * 
     * @var int
     */
    protected $_transactionLevel = 0;
    
    /**
     * Rolled-back Flag
     * 
     * Indicates whether or not a Rollback has occurred at any point during
     * our current set of transactions. If so, the whole transaction stack will
     * be rolled back at the end.
     * 
     * @var boolean
     */
    protected $_rolledBack = false;
    
    /**
     * Start new Transaction Level
     * 
     * This will start a new transaction, by starting an actual transaction if
     * one has not yet been started, and incrementing the transaction level
     * counter.
     * 
     * @return DDM_Db_Adapter_Mysqli
     */
    public function beginTransaction()
    {
        if ( $this->_transactionLevel === 0 ) {
            parent::beginTransaction();
        }
        $this->_transactionLevel++;
        
        return $this;
    }
    
    /**
     * Commit current Transaction Level
     * 
     * Indicates that the current level Transaction has been successful. If
     * we're committing the outermost transaction, this checks to see if a
     * Rollback has occurred at any point, and either Rolls back the whole
     * transaction or commits, accordingly. Otherwise it just decrements the
     * current transaction level.
     * 
     * @return DDM_Db_Adapter_Mysqli
     */
    public function commit()
    {
        if ( $this->_transactionLevel === 1 ) {
            
            if ($this->_rolledBack) {
                parent::rollback();
            } else {
                parent::commit();
            }
        }
        
        $this->_transactionLevel--;
        
        return $this;
    }
    
    /**
     * Rollback Transaction
     * 
     * Flags the entire transaction as a Rollback. If this is the outermost
     * Transaction level, rollback the whole transaction; otherwise just set
     * the Rollback flag and decrement the Transaction level counter.
     * 
     * @return DDM_Db_Adapter_Mysqli
     */
    public function rollback()
    {
        if ( $this->_transactionLevel === 1 ) {
            parent::rollback();
        }
        
        $this->_rolledBack = true;
        $this->_transactionLevel--;
        
        return $this;
    }
    
    /**
     * Get the current Transaction Level
     * 
     * Provides a convenient way for the application to find out whether a
     * transaction is active and how deep the current transaction is nested.
     * 
     * A return value of 1 means that we're in the outermost transaction,
     * which will be actually committed or rolled-back if such an operation
     * were to occur.
     * 
     * A return value of 0 means no transaction is currently active.
     * 
     * @return int
     */
    public function getTransactionLevel()
    {
        return $this->_transactionLevel;
    }
}
