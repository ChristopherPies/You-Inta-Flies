<?php

/**
 * Test case for classes that use the AuthorizeNetCim gateway. This gets the
 * Authorize.net config from global constants (usually set in phpunit.xml) and
 * sets up $this->gateway.
 */
abstract class DDM_Test_AuthorizeNet_CimTestCase extends PHPUnit_Framework_TestCase {

    /**
     *
     * @var DDM_Payment_Gateway_AuthorizeNetCim
     */
    protected $gateway;
    
    public static function setUpBeforeClass() {        
        $constants = array('AUTHORIZENET_SANDBOX', 'AUTHORIZENET_NAME',
                           'AUTHORIZENET_TRANSACTION_KEY');
        foreach ($constants as $const) {
            if (!defined($const)) {
                throw new Exception('You must define the following constants: '
                                    . implode(', ', $constants));
            }
        }
    }
    
    protected function setUp() {
        $this->gateway = new DDM_Payment_Gateway_AuthorizeNetCim(array(
            'authorizeNetSandbox' => (bool) AUTHORIZENET_SANDBOX,
            'authorizeNetName' => AUTHORIZENET_NAME,
            'authorizeNetTransactionKey' => AUTHORIZENET_TRANSACTION_KEY,
            'refId' => 'TESTING',
            'debug' => false,
        ));
    }
    
}
