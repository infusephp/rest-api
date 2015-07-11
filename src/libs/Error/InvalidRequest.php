<?php

namespace app\api\libs\Error;

class InvalidRequest extends Base
{
    public function __construct($message, $httpStatus = 400)
    {
        parent::__construct($message, $httpStatus);
    }
}
