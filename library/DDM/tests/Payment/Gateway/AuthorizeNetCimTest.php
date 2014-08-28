<?php

/**
 * @group authorizenet
 */
class DDM_Payment_Gateway_AuthorizeNetCimTest extends DDM_Test_AuthorizeNet_CimTestCase
{
    
    /**
     * @var DDM_Payment_Gateway_AuthorizeNetCim
     */
    protected $object;
    
    /**
     *
     * @var DDM_Payment_Customer_AuthorizeNetCim
     */
    protected $customer;
    
    /**
     *
     * @var DDM_Payment_Invoice_Generic
     */
    protected $invoice;
    
    protected function setUp() {
        parent::setUp();
        
        $this->object =& $this->gateway;
        
        $this->customer = new DDM_Payment_Customer_AuthorizeNetCim(array(
            'merchantCustomerId' => ('testing' . time()),
            'email' => ('testing' . time() . '@example.com'),
            'description' => 'testing',
            'paymentProfiles' => array(
                array(
                    'billTo' => array(
                        'firstName' => 'TestFirstName',
                        'lastName' => 'TestLastName',
                        'zip' => '12345',
                    ),
                    'payment' => array(
                        'creditCard' => array(
                            'cardNumber' => '4222222222222',
                            'expirationDate' => '2020-01',
                            'cardCode' => '123',
                        ),
                    ),
                ),
            ),
            'shipToList' => array(
                array('firstName' => 'TestFirstName'),
            ),
        ));
        $this->customer->setGateway($this->object);
        
        $this->invoice = new DDM_Payment_Invoice_Generic();
        $this->invoice->addLineItem(new DDM_Payment_Invoice_LineItem_Product(0.10));
        $this->invoice->addLineItem(new DDM_Payment_Invoice_LineItem_Product(0.10));
        $this->invoice->addLineItem(new DDM_Payment_Invoice_LineItem_Product(0.10));
        $this->invoice->addLineItem(new DDM_Payment_Invoice_LineItem_Shipping(0.10));
        $this->invoice->addLineItem(new DDM_Payment_Invoice_LineItem_Tax(0.10));
    }
    
    protected function tearDown() {
        if ($this->customer->getCustomerProfileId()) {
            try {
                $this->customer->deleteProfile();
            } catch (DDM_Payment_Exception_ResponseError_AuthorizeNetCim $ex) {
                // do nothing
            }
        }
    }
    
    protected function loadProfile() {
        if (!$this->customer->isConnected()) {
            // for some reason, we're getting duplicate profile errors randomly.
            // i think it has to do with the creating/deleting profiles really
            // fast in succession. so, we'll catch the exception and grab the id.
            try {
                $this->customer->createProfile();
            } catch (DDM_Payment_Exception_ResponseError_AuthorizeNetCim $ex) {
                // set customer id from duplicate id error msg
                $matches;
                if (preg_match('/ID (\d+)/', $ex->getMessage(), $matches)) {
                    $this->customer->setId($matches[1]);
                }
            }
            $this->customer->getProfile();
        }
    }
    
    protected function getPaymentProfile() {
        $this->loadProfile();
        $profiles = $this->customer->getPaymentProfiles();
        return reset($profiles);
    }
    
    protected function getShippingAddress() {
        $this->loadProfile();
        $addresses = $this->customer->getShippingAddresses();
        return reset($addresses);
    }
    
//////////////////////////////////
// TRANSACTION TESTS
////////////////////////////////// 

    public function testAuth() {
        $profile = $this->getPaymentProfile();
        $address = $this->getShippingAddress();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
            'customerShippingAddressId' => $address->getId(),
            'cardCode' => '123',
        ));
        
        $response = $this->object->auth($medium, $this->invoice);
        $this->assertTrue($response->isApproved());
        $this->assertEquals('0.50', $response->getData('amount'));
    }
    

    public function testCapture() {
        $profile = $this->getPaymentProfile();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
        ));
        
        $authResponse = $this->object->auth($medium, $this->invoice);
        $this->assertTrue($authResponse->isApproved());
        
        $medium->setApprovalCode($authResponse->getData('authorizationCode'));
        $response = $this->object->capture($medium, $this->invoice);
        
        $this->assertTrue($response->isApproved());
        $this->assertEquals('0.50', $response->getData('amount'));
    }
    
    public function testAuthCapture() {
        $profile = $this->getPaymentProfile();
        $address = $this->getShippingAddress();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
            'customerShippingAddressId' => $address->getId(),
            'cardCode' => '123',
        ));
        
        $response = $this->object->authCapture($medium, $this->invoice);
        $this->assertTrue($response->isApproved());
        $this->assertEquals('0.50', $response->getData('amount'));
    }
    
    
    public function testPriorAuthCapture() {
        $profile = $this->getPaymentProfile();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
        ));
        
        $authResponse = $this->object->auth($medium, $this->invoice);
        $this->assertTrue($authResponse->isApproved());
        
        $medium->setTransId($authResponse->getData('transactionId'));
        $response = $this->object->priorAuthCapture($medium, $this->invoice);
        
        $this->assertTrue($response->isApproved());
        $this->assertEquals('0.50', $response->getData('amount'));
    }
    
    public function testRefund() {
        $profile = $this->getPaymentProfile();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
        ));
        
        $response = $this->object->refund($medium, $this->invoice);
        $this->assertTrue($response->isApproved());
        $this->assertEquals('0.50', $response->getData('amount'));
    }
    
    public function testVoid() {
        $profile = $this->getPaymentProfile();
        $medium = $this->customer->getMedium(array(
            'customerPaymentProfileId' => $profile->getId(),
        ));
        
        $authCaptureResponse = $this->object->authCapture($medium, $this->invoice);
        $this->assertTrue($authCaptureResponse->isApproved());
        
        $medium->setTransId($authCaptureResponse->getData('transactionId'));
        $response = $this->object->void($medium, $this->invoice);
        $this->assertTrue($response->isApproved());
    }
    

//////////////////////////////////
// PROFILE TESTS
//////////////////////////////////  

    public function testGetCustomerProfileIds() {
        $this->loadProfile();
        $ids = $this->object->getCustomerProfileIds();
        $this->assertNotEmpty($ids);
    }
    
    public function testCreateCustomerProfile() {
        $result = $this->object->createCustomerProfile($this->customer);
        $this->assertNotEmpty($result['customerProfileId']);
    }
    
    public function testGetCustomerProfile() {
        $this->loadProfile();
        $profile = $this->object->getCustomerProfile($this->customer->getId());
        $this->assertNotEmpty($profile->getId());
    }
    
    public function testUpdateCustomerProfile() {
        $this->loadProfile();
        $this->customer->setDescription('updated');
        $this->object->updateCustomerProfile($this->customer);
        $this->assertEquals('updated', $this->customer->getDescription());
    }
    
    public function testDeleteCustomerProfile() {
        $this->loadProfile();
        $this->object->deleteCustomerProfile($this->customer->getId());
        $ids = $this->object->getCustomerProfileIds();
        $this->assertNotContains($this->customer->getId(), $ids);
        
        $this->customer->setId(null);
    }
 
    
    
//////////////////////////////////
// todo: finish profile tests
// although we may not need all this, as most is also tested through customer.
////////////////////////////////// 
//    
//
//    /**
//     * @todo Implement testUpdateCustomerProfile().
//     */
//    public function testUpdateCustomerProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testDeleteCustomerProfile().
//     */
//    public function testDeleteCustomerProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateCustomerPaymentProfile().
//     */
//    public function testCreateCustomerPaymentProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testGetCustomerPaymentProfile().
//     */
//    public function testGetCustomerPaymentProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testUpdateCustomerPaymentProfile().
//     */
//    public function testUpdateCustomerPaymentProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testDeleteCustomerPaymentProfile().
//     */
//    public function testDeleteCustomerPaymentProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testCreateCustomerShippingAddress().
//     */
//    public function testCreateCustomerShippingAddress()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testGetCustomerShippingAddress().
//     */
//    public function testGetCustomerShippingAddress()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testUpdateCustomerShippingAddress().
//     */
//    public function testUpdateCustomerShippingAddress()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testDeleteCustomerShippingAddress().
//     */
//    public function testDeleteCustomerShippingAddress()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//
//    /**
//     * @todo Implement testValidateCustomerPaymentProfile().
//     */
//    public function testValidateCustomerPaymentProfile()
//    {
//        // Remove the following lines when you implement this test.
//        $this->markTestIncomplete(
//          'This test has not been implemented yet.'
//        );
//    }
//    
//    public function public function testCreateCustomerProfileTransaction()
//    {
//    
//    }
//    
//    public function testUpdateSplitTenderGroup() {
//        $this->markTestIncomplete(
//          'Not sure how to test partial auth in authorize.net sandbox.'
//        );
//    }

}
