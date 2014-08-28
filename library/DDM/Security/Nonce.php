<?php

class DDM_Security_Nonce {
    CONST ALLOWED_TIME_SKEW = 300;

    // iWitness App uses an timestamp-\d{16} as their salt nonce instead of the normal six digit salt
    CONST NONCE_REGEX = '/^(\d{4})-(\d\d)-(\d\d)T(\d\d):(\d\d):(\d\d)Z([\w-]{6,})$/';
    CONST NONCE_DATEFORMAT = '%Y-%m-%dT%H:%M:%SZ';
    CONST NONCE_CHARS = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    /**
     * Gets a random string
     *
     * @param int $length
     * @return string
     */
    private static function getRandomString($length = 6)
    {
        $popsize = strlen(self::NONCE_CHARS);
        $duplicate = 256 % $popsize;

        $str = '';
        for ($i = 0; $i < $length; $i++) {
            do {
                $n = ord(self::getBytes(1));
            } while ($n < $duplicate);

            $n %= $popsize;
            $str .= substr(self::NONCE_CHARS, $n, 1);
        }

        return $str;
    }

    /**
     * Gets a number of bytes
     *
     * @param int $num_bytes
     * @return int
     */
    private static function getBytes($num_bytes)
    {
        // pseudorandom used
        $bytes = '';
        for ($i = 0; $i < $num_bytes; $i += 4) {
            $bytes .= pack('L', mt_rand());
        }
        $bytes = substr($bytes, 0, $num_bytes);

        return $bytes;
    }

    /**
     * Generates a nonce
     *
     * @return string
     */
    public static function generateNonce()
    {
        $time_str = gmstrftime(self::NONCE_DATEFORMAT, time());
        return $nonce = $time_str . self::getRandomString();
    }

    /**
     * Splits a nonce into timestamp and salt
     *
     * @param string $nonce
     * @return array
     */
    public static function splitNonce($nonce)
    {
        $result = preg_match(self::NONCE_REGEX, $nonce, $matches);
        if ($result != 1 || count($matches) != 8) {
            return null;
        }

        list(
            $unused,
            $year,
            $month,
            $mday,
            $hour,
            $minute,
            $second,
            $salt,
        ) = $matches;

        $timestamp = @gmmktime($hour, $minute, $second, $month, $mday, $year);

        if ($timestamp === false || $timestamp < 0) {
            return null;
        }

        return array(
            $timestamp,
            $salt
        );
    }

    /**
     * Validates a nonce
     *
     * @param string $nonce
     * @param int $skew OPTIONAL
     * @param int $now OPTIONAL
     * @return boolean
     */
    public static function validateNonceTime($timestamp, $skew = null, $now = null)
    {
        if ($skew === null) {
            $skew = self::ALLOWED_TIME_SKEW;
        }
        if ($now === null) {
            $now = time();
        }

        // Time after which we should not use the nonce
        $past = $now - $skew;

        // Time that is too far in the future for us to allow
        $future = $now + $skew;

        // the stamp is not too far in the future and is not too far
        // in the past
        return (($past <= $timestamp) && ($timestamp <= $future));
    }

    /**
     * Returns the expired time
     *
     * @param int $skew OPTIONAL
     * @param int $now OPTIONAL
     * @return int
     */
    public static function getExpiredTime($skew = null, $now = null)
    {
        if ($skew === null) {
            $skew = self::ALLOWED_TIME_SKEW;
        }
        if ($now === null) {
            $now = time();
        }

        return $now - $skew;
    }

    /**
     * Returns the expired timestamp
     *
     * @param int $now
     * @return string
     */
    public static function getExpiredTimestamp($now = null)
    {
        return gmstrftime(self::NONCE_DATEFORMAT, self::getExpiredTime($now));
    }
}