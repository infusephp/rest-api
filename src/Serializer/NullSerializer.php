<?php

namespace Infuse\RestApi\Serializer;

use Infuse\RestApi\Route\AbstractRoute;

class NullSerializer implements SerializerInterface
{
    public function serialize($input, AbstractRoute $route)
    {
        // do nothing
    }
}
