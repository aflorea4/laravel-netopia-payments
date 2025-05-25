<?php

namespace Aflorea4\NetopiaPayments\Models;

class Address
{
    /**
     * The address type (person or company)
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
     * The email
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
     * The mobile phone
     *
     * @var string
     */
    public $mobilePhone;
}
