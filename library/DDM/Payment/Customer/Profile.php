<?php

interface DDM_Payment_Customer_Profile extends DDM_Payment_Customer {
    
    public function getProfileId();
    public function setProfileId($id);
    
    public function getProfile();
    public function createProfile($options = null);
    public function updateProfile($options = null);
    public function deleteProfile();
    
    public function getPaymentProfiles();
    public function createPaymentProfile(DDM_Payment_Customer_PaymentProfile $paymentProfile, $options = null);
    public function updatePaymentProfile(DDM_Payment_Customer_PaymentProfile $paymentProfile, $options = null);
    public function validatePaymentProfile($paymentProfileId, $options = null);
    public function deletePaymentProfile($paymentProfileId);
    
    public function getShippingAddresses();
    public function createShippingAddress(DDM_Payment_Customer_Address $address, $options = null);
    public function updateShippingAddress(DDM_Payment_Customer_Address $address, $options = null);
    public function deleteShippingAddress($addressId);
    
}
