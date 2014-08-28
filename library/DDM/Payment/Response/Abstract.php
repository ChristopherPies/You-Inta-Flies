<?php

class DDM_Payment_Response_Abstract implements DDM_Payment_Response {

    protected $status = DDM_Payment_Response::STATUS_UNKNOWN;
    protected $message;
    protected $locatId;
    protected $remoteId;
    protected $code;

    public function setStatus($status) {
        $this->status = $status;
    }

    public function getStatus() {
        return $this->status;
    }

    public function setLocalId($localId) {
        $this->localId = $localId;
    }

    public function getLocalId() {
        return $this->localId;
    }

    public function setRemoteId($remoteId) {
        $this->remoteId = $remoteId;
    }

    public function getRemoteId() {
        return $this->remoteId;
    }

    public function setMessage($message) {
        $this->message = $message;
    }

    public function getMessage() {
        return $this->message;
    }
    
    public function getCode() {
        return $this->code;
    }

    public function setCode($code) {
        $this->code = $code;
    }

    public function setSuccess($message) {
        $this->setStatus(DDM_Payment_Response::STATUS_SUCCESS);
        if ($message) {
            $this->setMessage($message);
        }
    }

    public function setFailure($message) {
        $this->setStatus(DDM_Payment_Response::STATUS_FAILURE);
        if ($message) {
            $this->setMessage($message);
        }
    }
    
    public function isSuccess() {
        return $this->status == DDM_Payment_Response::STATUS_SUCCESS;
    }

}
