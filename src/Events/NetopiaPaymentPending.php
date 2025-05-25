<?php

namespace Aflorea4\NetopiaPayments\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Aflorea4\NetopiaPayments\Models\Response;

class NetopiaPaymentPending
{
    use Dispatchable, SerializesModels;

    /**
     * The payment response.
     *
     * @var Response
     */
    public $response;

    /**
     * Create a new event instance.
     *
     * @param Response $response
     * @return void
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
    }
}
