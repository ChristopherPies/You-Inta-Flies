<?php

abstract class DDM_Payment_Gateway_Abstract implements DDM_Payment_Gateway {
    
    protected $debug = false;

    public function __construct($options = null) {
        if (is_array($options)) {
            $this->setOptions($options);
        }
    }

    public function setOptions(array $options = null) {
        foreach ($options as $name => $value) {
            $methodName = 'set' . ucfirst($name);
            if (method_exists($this, $methodName)) {
                $this->$methodName($value);
            }
        }
    }

    public function auth(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice) {
        throw new DDM_Payment_Exception_MethodNotSupportedException();
    }

    public function authCapture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice) {
        throw new DDM_Payment_Exception_MethodNotSupportedException();
    }

    public function capture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice) {
        throw new DDM_Payment_Exception_MethodNotSupportedException();
    }

    public function refund(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice) {
        throw new DDM_Payment_Exception_MethodNotSupportedException();
    }

    public function void(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice) {
        throw new DDM_Payment_Exception_MethodNotSupportedException();
    }
    
    public function getDebug() {
        return $this->debug;
    }

    public function setDebug($debug) {
        $this->debug = (bool)$debug;
    }

    public function debug($msg, $encode = false) {
        if ($this->getDebug()) {
            // @todo: do something fancier?
            if (isset($_SERVER['HTTP_USER_AGENT'])) {
                // only encode in non-cli environment:
                $msg = ($encode) ? htmlspecialchars($msg) : $msg;
                echo '<pre>' . $msg . '</pre>';
            } else {
                echo "\n" . $msg . "\n";
            }
        }
    }

}
