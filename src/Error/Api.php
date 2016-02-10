<?php

namespace App\RestApi\Error;

class Api extends Base
{
    public function __construct($message, $httpStatus = 500)
    {
        parent::__construct($message, $httpStatus);
    }
}
