<?php

namespace Aflorea4\NetopiaPayments\Models;

class Request
{
    /**
     * The order ID
     *
     * @var string
     */
    public $orderId;

    /**
     * The signature (merchant identifier)
     *
     * @var string
     */
    public $signature;

    /**
     * The return URL
     *
     * @var string
     */
    public $returnUrl;

    /**
     * The confirm URL
     *
     * @var string
     */
    public $confirmUrl;

    /**
     * The invoice
     *
     * @var Invoice
     */
    public $invoice;
}
