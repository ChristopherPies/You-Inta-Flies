<?php

/**
 * @group authorizenet
 */
class DDM_Payment_Customer_AuthorizeNetCimTest extends DDM_Test_AuthorizeNet_CimTestCase
{
    
    /**
     * @var DDM_Payment_Customer_AuthorizeNetCim
     */
    protected $object;
    
    protected function setUp() {
        parent::setUp();
        
        $this->object = new DDM_Payment_Customer_AuthorizeNetCim(array(
            'gateway' => $this->gateway,
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
    }
    
    protected function assertPreConditions() {
        $this->assertNotEmpty($this->object->getCustomerProfileId());
    }
    
    protected function tearDown() {
        if ($this->object && $this->object->getCustomerProfileId()) {
            try {
                $this->object->deleteProfile();
            } catch (DDM_Payment_Exception_ResponseError_AuthorizeNetCim $ex) {
                // do nothing
            }
        }
    }
    
///////////////////////////////////
// PROFILE TESTS    
///////////////////////////////////
   
    public function testGetProfile() {
        $this->object->getProfile();
        $this->assertTrue($this->object->isConnected());
        $this->assertNotEmpty($this->object->getPaymentProfiles());
    }

    /**
     * @expectedException DDM_Payment_Exception_UnexpectedValueException
     */
    public function testCreateProfile() {
        $this->object->createProfile();
    }
    
    public function testUpdateProfile() {
        $this->object->setDescription('updated');
        $this->object->updateProfile();
        $this->assertEquals('updated', $this->object->getDescription());
    }
    
    public function testDeleteProfile() {
        $id = $this->object->getCustomerProfileId();
        $this->object->deleteProfile();
        try {
            $this->object->setCustomerProfileId($id);
            $this->object->getProfile();
            $this->fail('Expected exception on getProfile()');
        } catch (DDM_Payment_Exception_ResponseError_AuthorizeNetCim $ex) { }
        
        $this->object->setCustomerProfileId(null);
        
    }
    
///////////////////////////////////
// PAYMENT PROFILE TESTS    
///////////////////////////////////
    
    public function testGetPaymentProfileById()
    {
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $profileById = $this->object->getPaymentProfileById($profile->getId());
        $this->assertEquals($profile->getId(), $profileById->getId());
    }

    public function testCreatePaymentProfile()
    {
        $profile = new DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile(array(
            'billTo' => array(
                'address' => '123 test'
            ),
            'payment' => array(
                'bankAccount' => array(
                    'accountNumber' => 123456789,
                    'routingNumber' => 123456789,
                    'nameOnAccount' => 'Test Test',
                ),
            ),
        ));
        $id = $this->object->createPaymentProfile($profile);
        $this->assertNotEmpty($id);
    }
    
    public function testUpdatePaymentProfile()
    {
        $this->object->getProfile(); // load profile
        
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $profile->setBillTo(array('firstName' => 'updated'));
        $this->object->updatePaymentProfile($profile);
        
        $this->object->getProfile(); // refresh
        $profile = $this->object->getPaymentProfileById($profile->getId());
        $this->assertEquals('updated', $profile->getBillTo()->getFirstName());
    }
    
    public function testUpdatePaymentProfileWithoutCustomerProfileId()
    {
        $this->object->getProfile(); // load profile
        
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $id = $this->object->getCustomerProfileId();
        $this->object->setCustomerProfileId(null);
        try {
            $this->object->updatePaymentProfile($profile);
            $this->fail('updatePaymentProfile should have caused an exception');
        } catch (DDM_Payment_Exception_UnexpectedValueException $ex) {
            // pass
            $this->object->setCustomerProfileId($id);
        }
    }
    
    public function testValidatePaymentProfile()
    {
        $this->object->getProfile(); // load profile
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $response = $this->object->validatePaymentProfile($profile->getId(), array('cardCode' => 123));
        $this->assertTrue($response->isSuccess());
    }
    
    public function testValidatePaymentProfileFailure()
    {
        $this->object->getProfile(); // load profile
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        
        $profile->getPayment()->getCreditCard()->setCardNumber(1111111111111111);
        $this->object->updatePaymentProfile($profile);
        
        $response = $this->object->validatePaymentProfile($profile->getId(), array('cardCode' => 123));
        $this->assertFalse($response->isSuccess());
    }

    public function testDeletePaymentProfile()
    {
        $this->object->getProfile(); // load profile
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $this->object->deletePaymentProfile($profile->getId());
        
        $this->assertEmpty($this->object->getPaymentProfiles());
        $this->object->getProfile(); // refresh
        $this->assertEmpty($this->object->getPaymentProfiles());
    }

///////////////////////////////////
// SHIPPING ADDRESS TESTS    
///////////////////////////////////

    public function testGetShippingAddressById()
    {
        $addresses = $this->object->getShippingAddresses();
        $address = end($addresses);
        $addressById = $this->object->getShippingAddressById($address->getId());
        $this->assertEquals($address->getId(), $addressById->getId());
    }
    public function testCreateShippingAddress()
    {
        $address = new DDM_Payment_Customer_AuthorizeNetCim_Address(array(
            'firstName' => 'Test',
            'lastName' => 'Test',
            'zip' => 12345,
        ));
        $id = $this->object->createShippingAddress($address);
        $this->assertNotEmpty($id);
    }
    
    public function testUpdateShippingAddress()
    {
        $this->object->getProfile(); // load profile
        
        $addresses = $this->object->getShippingAddresses();
        $address = end($addresses);
        $address->setFirstName('updated');
        $this->object->updateShippingAddress($address);
        
        $this->object->getProfile(); // refresh
        $address = $this->object->getShippingAddressById($address->getId());
        $this->assertEquals('updated', $address->getFirstName());
    }
    
    public function testUpdateShippingAddressWithoutCustomerProfileId()
    {
        $this->object->getProfile(); // load profile
        
        $addresses = $this->object->getShippingAddresses();
        $address = end($addresses);
        $id = $this->object->getCustomerProfileId();
        $this->object->setCustomerProfileId(null);
        try {
            $this->object->updateShippingAddress($address);
            $this->fail('updateShippingAddress should have caused an exception');
        } catch (DDM_Payment_Exception_UnexpectedValueException $ex) {
            // pass
            $this->object->setCustomerProfileId($id);
        }
    }

    public function testDeleteShippingAddress()
    {
        $this->object->getProfile(); // load profile
        $addresses = $this->object->getShippingAddresses();
        $address = end($addresses);
        $this->object->deleteShippingAddress($address->getId());
        
        $this->assertEmpty($this->object->getShippingAddresses());
        $this->object->getProfile(); // refresh
        $this->assertEmpty($this->object->getShippingAddresses());
    }
    
///////////////////////////////////
// MEDIUM TESTS    
///////////////////////////////////

    public function testGetMediumNewPaymentProfile() {
        $data = array(
            'cardNumber' => '4222222224444',
            'expirationDate' => '2030-01',
            'cardCode' => '555',
            'shippingAddress' => array(
                'firstName' => 'Medium Test',
            ),
        );
        $medium = $this->object->getMedium($data);
        
        $this->assertEquals($this->object->getId(), $medium->getCustomerProfileId());
        $this->assertNotEmpty($medium->getCustomerPaymentProfileId());
        $this->assertNotEmpty($medium->getCustomerShippingAddressId());
    }
    
    public function testGetMediumExistingPaymentProfile() {
        $this->object->getProfile();
        $profiles = $this->object->getPaymentProfiles();
        $profile = end($profiles);
        $addresses = $this->object->getShippingAddresses();
        $address = end($addresses);
        
        $data = array_merge(
            $profile->getPayment()->getCreditCard()->getData(),
            $profile->getBillTo()->getData(),
            array(
                'cardCode' => '123',
                'shippingAddress' => $address->getData(),
            )
        );
        $medium = $this->object->getMedium($data, false);
        
        $this->assertEquals($this->object->getId(), $medium->getCustomerProfileId());
        $this->assertEquals($profile->getId(), $medium->getCustomerPaymentProfileId());
        $this->assertEquals($address->getId(), $medium->getCustomerShippingAddressId());
    }
    
}
