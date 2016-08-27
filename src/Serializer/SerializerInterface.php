<?php

namespace Infuse\RestApi\Serializer;

use Infuse\RestApi\Route\AbstractRoute;

interface SerializerInterface
{
    /**
     * Serializes a given input to a response.
     *
     * A serializer may modify the response object and/or return
     * a serialized output. The output could be used for various
     * purposes such as chaining multiple serializers together.
     *
     * @param mixed         $input
     * @param AbstractRoute $route
     *
     * @return mixed output
     */
    public function serialize($input, AbstractRoute $route);
}
