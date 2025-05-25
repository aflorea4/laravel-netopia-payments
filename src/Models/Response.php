<?php

namespace Aflorea4\NetopiaPayments\Models;

class Response
{
    /**
     * The order ID
     *
     * @var string
     */
    public $orderId;

    /**
     * The action
     *
     * @var string
     */
    public $action;

    /**
     * The error code
     *
     * @var string
     */
    public $errorCode;

    /**
     * The error message
     *
     * @var string
     */
    public $errorMessage;

    /**
     * The processed amount
     *
     * @var float
     */
    public $processedAmount;

    /**
     * The original amount
     *
     * @var float
     */
    public $originalAmount;

    /**
     * Check if the payment was successful
     *
     * @return bool
     */
    public function isSuccessful()
    {
        return $this->action === 'confirmed' && empty($this->errorCode);
    }

    /**
     * Check if the payment is pending
     *
     * @return bool
     */
    public function isPending()
    {
        return $this->action === 'confirmed_pending';
    }

    /**
     * Check if the payment was paid
     *
     * @return bool
     */
    public function isPaid()
    {
        return $this->action === 'paid';
    }

    /**
     * Check if the payment was canceled
     *
     * @return bool
     */
    public function isCanceled()
    {
        return $this->action === 'canceled';
    }

    /**
     * Check if the payment was credited
     *
     * @return bool
     */
    public function isCredited()
    {
        return $this->action === 'credit';
    }
}
