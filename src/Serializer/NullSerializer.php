<?php

namespace App\RestApi\Serializer;

use App\RestApi\Route\AbstractRoute;

class NullSerializer implements SerializerInterface
{
    public function serialize($input, AbstractRoute $route)
    {
        // do nothing
    }
}
