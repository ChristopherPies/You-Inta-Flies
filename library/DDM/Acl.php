<?php
class DDM_Acl extends Zend_Acl
{
     /**
     * Can the current user access a resource/permission?
     *
     * @param string $resource
     * @param string $permission
     * @param string $namespace
     * @return boolean
     */
    public function userIsAllowed( $resource, $permission = null, $namespace = 'Zend_Auth', $roles = null ) {
       if(!$roles){
           if(!$namespace){
               $namespace = 'Zend_Auth';
           }
            $this->userInfo = new Zend_Session_Namespace($namespace);
           if( isset($this->userInfo->storage->roles) ) {
                $roles = $this->userInfo->storage->roles;
            }
       }else{

       }
       if( count($roles) ) {
               foreach($roles as $role) {
                       $can = $this->isAllowed( $role, $resource, $permission );
                       //echo (int) $can;
                       //echo "$role $resource $permission <BR>";
                       if( $can ) {
                               return true;
                       }
               }
       }

       return false;
    }
}
