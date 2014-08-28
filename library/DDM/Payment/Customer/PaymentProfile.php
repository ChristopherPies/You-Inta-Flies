<?php

interface DDM_Payment_Customer_PaymentProfile {
    
    public function toString($separator = "\n", $escape = false);
    public function __toString();
    
}
