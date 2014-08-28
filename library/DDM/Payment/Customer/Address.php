<?php

/**
 * Class represents an address for use with DDM_Payment_Gateways and other objects
 */

class DDM_Payment_Customer_Address extends DDM_Payment_DataObject
{
    /**
     * Character used to mark the end of the first address line and the start
     * of address line 2.
     *
     * @var string
     */
    CONST ADDRESS_LINE_DELIMITER = '|';

    /**
     * Address information stored by the object
     *
     * @var array
     */
    protected $data = array(
        'id' => null,
        'firstName' => null,
        'lastName' => null,
        'company' => null,
        'address' => null,
        'city' => null,
        'state' => null,
        'zip' => null,
        'country' => null,
        'phoneNumber' => null,
        'faxNumber' => null,
    );

    /**
     * Maps of key inputs to key outputs
     *
     * @var array
     */
    protected $arrayKeyMap = array(
        'firstname' => 'firstName',
        'first_name' => 'firstName',
        'lastname' => 'lastName',
        'last_name' => 'lastName',
        'region' => 'state',
        'zipCode' => 'zip',
        'zip_code' => 'zip',
        'postal_code' => 'zip',
        'phone' => 'phoneNumber',
        'phone_number' => 'phoneNumber',
        'fax' => 'faxNumber',
        'fax_number' => 'faxNumber',
    );

    /**
     * Translates input array into the keys used by data and stores the results in
     * the object.
     *
     * @param array $data
     */
    public function __construct(array $data = null)
    {
        $data = $this->applyArrayKeyMap($data, $this->arrayKeyMap);

        if (!empty($data['address2']) && !empty($data['address'])) {
            $data['address'] .= self::ADDRESS_LINE_DELIMITER . $data['address2'];
        }

        parent::__construct($data);
    }

    /**
     * Explodes address back into an array of line 1 and line 2
     *
     * @param string $address
     *
     * @return array
     */
    protected function getAddressParts($address)
    {
        $addressParts = explode(self::ADDRESS_LINE_DELIMITER, $address);
        if (count($addressParts) == 1) {
            $addressParts[] = null;
        }
        return $addressParts;
    }

    /**
     * Rename keys in an array based on a mapper
     *
     * @param array $array
     * @param array $mapper
     *
     * @return array
     */
    public function applyArrayKeyMap(array $array, array $mapper)
    {
        if (array_key_exists('address2', $mapper)) {
            // Break address back out into two lines
            if (array_key_exists('address', $array)
                && strpos($array['address'], self::ADDRESS_LINE_DELIMITER) !== false
            ) {
                list($address, $address2) = $this->getAddressParts($array['address']);
                $array['address'] = $address;
                $array['address2'] = $address2;
            }
        }

        $output = parent::applyArrayKeyMap($array, $mapper);

        return $output;
    }

    /**
     * Converts object to string with additional parameters
     *
     * @param string $separator
     * @param boolean $escape
     *
     * @return string
     */
    public function toString($separator = "\n", $escape = false)
    {
        extract($this->getData());

        // Break address back out into two lines
        list($address, $address2) = $this->getAddressParts($address);

        $parts = array(
            "$firstName $lastName",
            $company,
            $address,
            $address2,
            "$city, $state $zip",
            $country,
            $phoneNumber,
            $faxNumber,
        );
        foreach ($parts as $index => &$part) {
            if (!$part || strlen(trim($part)) === 0) {
                unset($parts[$index]);
            } else if ($escape) {
                $part = htmlspecialchars($part);
            }
        }

        return implode($separator, $parts);
    }

    /**
     * Converts the object to a string
     *
     * @return string
     */
    public function __toString()
    {
        return $this->toString();
    }

}