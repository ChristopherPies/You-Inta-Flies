<?php

class DDM_Application_Resource_Session extends Zend_Application_Resource_Session
{
    /**
     * @return bool
     */
    protected function _hasSaveHandler()
    {
        // THE ORIGINAL ZEND VERSION INSISTS ON A TRUE NULL VALUE HERE.
        // Since the application.ini parser won't set a value to a true
        // null, you can never reset the sessionHandler once it has been
        // set.  This is a problem if you want to define a sessionHandler
        // for the production enviornment, but want to override it in your
        // own local dev environment.
        return ($this->_saveHandler != null);
    }

}