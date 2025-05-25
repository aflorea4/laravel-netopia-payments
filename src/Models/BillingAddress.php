<?php

namespace Aflorea4\NetopiaPayments\Models;

/**
 * Billing Address Model
 */
class BillingAddress
{
    /**
     * The type of the billing address (person or company)
     *
     * @var string
     */
    public $type;

    /**
     * The first name
     *
     * @var string
     */
    public $firstName;

    /**
     * The last name
     *
     * @var string
     */
    public $lastName;

    /**
     * The email address
     *
     * @var string
     */
    public $email;

    /**
     * The address
     *
     * @var string
     */
    public $address;

    /**
     * The mobile phone number
     *
     * @var string
     */
    public $mobilePhone;
}
