<?php

namespace App\RestApi\Error;

class InvalidRequest extends Base
{
    public function __construct($message, $httpStatus = 400, $param = null)
    {
        parent::__construct($message, $httpStatus);

        $this->param = $param;
    }

    public function getParam()
    {
        return $this->param;
    }
}
