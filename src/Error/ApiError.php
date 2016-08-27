<?php

namespace Infuse\RestApi\Error;

class ApiError extends Base
{
    public function __construct($message, $httpStatus = 500)
    {
        parent::__construct($message, $httpStatus);
    }
}
