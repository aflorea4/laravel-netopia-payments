<?php

namespace Aflorea4\NetopiaPayments\Facades;

use Illuminate\Support\Facades\Facade;

class NetopiaPayments extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'netopia-payments';
    }
}
