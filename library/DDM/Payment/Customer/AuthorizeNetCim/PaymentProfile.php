<?php

class DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile
    extends DDM_Payment_DataObject
    implements DDM_Payment_Customer_PaymentProfile
{
    const CUSTOMER_TYPE_INDIVIDUAL = 'individual';
    const CUSTOMER_TYPE_BUSINESS = 'business';

    protected $data = array(
        'customerType' => null,
        'billTo' => null,
        'payment' => null,
        'customerPaymentProfileId' => null,
    );

    protected static $dataIdAlias = 'customerPaymentProfileId';

    protected static $dataClassMap = array(
        'billTo' => 'DDM_Payment_Customer_AuthorizeNetCim_Address',
        'payment' => 'DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment',
    );

    /**
     * Normal toArray output but with option to map keys to other names
     *
     * @param array $arrayKeyMap
     *
     * @return array
     */
    public function toArray(array $arrayKeyMap = null)
    {
        $output = parent::toArray();

        if ($arrayKeyMap !== null) {
            // Address has a customized version of applyArrayKeyMap that we need to make sure is called
            if (array_key_exists('billTo', $arrayKeyMap) && is_array($arrayKeyMap['billTo'])) {
                $output['billTo'] = $this->getBillTo()->applyArrayKeyMap($output['billTo'], $arrayKeyMap['billTo']);
                // No need to process the map again, so either unset it or promote __SELF__ to billTo
                if (array_key_exists('__SELF__', $arrayKeyMap['billTo'])) {
                    $arrayKeyMap['billTo'] = $arrayKeyMap['billTo']['__SELF__'];
                } else {
                    unset($arrayKeyMap['billTo']);
                }
            }
            $output = $this->applyArrayKeyMap($output, $arrayKeyMap);
        }

        return $output;
    }

    public function toString($separator = "\n", $escape = false) {
        $partNames = array('payment', 'billTo');
        $parts = array();

        foreach ($partNames as $name) {
            $obj = $this->getData($name);
            if ($obj) {
                $string = $obj->toString($separator, $escape);
                if ($string) {
                    $parts[] = $string;
                }
            }
        }

        return implode($separator, $parts);
    }

    public function __toString() {
        return $this->toString();
    }

}
