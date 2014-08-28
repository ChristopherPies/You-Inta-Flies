<?php 

interface DDM_Payment_Gateway {

    public function __construct($options = null);

    public function setOptions(array $options);

    public function auth(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice);

    public function authCapture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice);

    public function capture(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice);

    public function refund(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice);

    public function void(DDM_Payment_Medium $medium, DDM_Payment_Invoice $invoice);

}
