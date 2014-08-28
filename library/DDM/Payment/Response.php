<?php

interface DDM_Payment_Response {
    
    const STATUS_UNKNOWN = 0;
    const STATUS_SUCCESS = 1;
    const STATUS_FAILURE = 2;

    public function setStatus($status);
    public function getStatus();

    public function setLocalId($localId);
    public function getLocalId();

    public function setRemoteId($remoteId);
    public function getRemoteId();

    public function setMessage($message);
    public function getMessage();
    
    public function setCode($code);
    public function getCode();

}
