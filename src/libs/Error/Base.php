<?php

namespace app\api\libs\Error;

use Exception;

abstract class Base extends Exception
{
    public function __construct($message, $httpStatus = null)
    {
        parent::__construct($message);

        $this->httpStatus = $httpStatus;
    }

    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
}
