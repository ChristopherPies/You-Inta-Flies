<?php

/**
 * Holds information for an Authorize.net CIM Credit Card
 */

class DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_CreditCard
    extends DDM_Payment_Customer_AuthorizeNetCim_PaymentProfile_Payment_Abstract
{

    /**
     * Data stored in this class
     *
     * @var array
     */
    protected $data = array(
        'cardNumber' => null,
        'expirationDate' => null,
        'cardCode' => null,
    );

    /**
     * Maps of key inputs to key outputs
     *
     * @var array
     */
    protected $arrayKeyMap = array(
        'number' => 'cardNumber',
        'card_number' => 'cardNumber',
        'ccv' => 'cardCode',
        'card_code' => 'cardCode',
        'expiration' => 'expirationDate',
        'expiration_date' => 'expirationDate',
        'month' => 'expirationMonth',
        'expiration_month' => 'expirationMonth',
        'year' => 'expirationYear',
        'expiration_year' => 'expirationYear',
    );

    /**
     * Data masks for this class
     *
     * @var array
     */
    protected $dataMasks = array(
        'cardNumber' => -4,
        'expirationDate' => 0,
    );

    /**
     * Translates input array into the keys used by data and stores the results in
     * the object.
     *
     * @param array $data
     */
    public function __construct(array $data = null) {
        $data = $this->applyArrayKeyMap($data, $this->arrayKeyMap);

        // Combine expiration month and year to expiration date
        if (array_key_exists('expirationMonth', $data) && array_key_exists('expirationYear', $data)) {
            $data['expirationDate'] = mktime(0, 0, 0, $data['expirationMonth'], 1, $data['expirationYear']);
        }

        parent::__construct($data);
    }

    /**
     * Sets the expiration in Y-m format from a string (must be compatible with
     * strtotime), timestamp, or DateTime
     *
     * @param string|int|DateTime $expirationDate
     */
    public function setExpirationDate($expirationDate) {
        if ($expirationDate == 'XXXX') {
            $this->data['expirationDate'] = $expirationDate;
            return;
        }

        if ($expirationDate instanceof DateTime) {
            $expirationDate = $expirationDate->getTimestamp();
        } else if (!is_int($expirationDate)) {
            $expirationDate = strtotime($expirationDate);
        }
        $this->data['expirationDate'] = date('Y-m', $expirationDate);
    }

    public function toString() {
        return (string) $this->getMaskedData('cardNumber');
    }

}
