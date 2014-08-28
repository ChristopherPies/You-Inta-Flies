<?php

class DDM_Security_Signature {
	/**
	 * Generates a signature
	 *
	 * @param string $token
	 * @param string $nonce
	 * @param string $secret
	 * @return string
	 */
	public static function generateSignature($token, $nonce, $secret, $member_id = 0, $hash = 'sha1') {
		return Zend_Crypt_Hmac::compute($secret, $hash, $token . $member_id . $nonce);
	}
	
	/**
	 * Validates a signature using token, nonce, and secret
	 *
	 * @param string $token
	 * @param string $nonce
	 * @param string $secret
	 * @return boolean
	 */
	public static function validateSignature($token, $nonce, $secret, $member_id, $signature, $hash = 'sha1') {
		$test_hmac = self::generateSignature($token, $nonce, $secret, $member_id, $hash);
		return $test_hmac == $signature;
	}
}