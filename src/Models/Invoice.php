<?php

namespace Aflorea4\NetopiaPayments\Models;

class Invoice
{
    /**
     * The currency
     *
     * @var string
     */
    public $currency;

    /**
     * The amount
     *
     * @var float
     */
    public $amount;

    /**
     * The details
     *
     * @var string
     */
    public $details;

    /**
     * The billing address
     *
     * @var Address
     */
    public $billingAddress;

    /**
     * Set the billing address
     *
     * @param Address $address
     * @return void
     */
    public function setBillingAddress(Address $address)
    {
        $this->billingAddress = $address;
    }
}
