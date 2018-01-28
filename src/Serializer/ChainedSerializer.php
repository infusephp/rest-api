<?php

namespace Infuse\RestApi\Serializer;

use Infuse\RestApi\Route\AbstractRoute;

class ChainedSerializer implements SerializerInterface
{
    /**
     * @var array
     */
    private $serializers = [];

    /**
     * Adds a serializer to the chain. Serializers are
     * executed in FIFO order.
     *
     * @param SerializerInterface $serializer
     *
     * @return $this
     */
    public function add(SerializerInterface $serializer)
    {
        $this->serializers[] = $serializer;

        return $this;
    }

    /**
     * Gets the chain of serializers.
     *
     * @return array
     */
    public function getSerializers()
    {
        return $this->serializers;
    }

    public function serialize($input, AbstractRoute $route)
    {
        foreach ($this->serializers as $serializer) {
            $input = $serializer->serialize($input, $route);
        }

        return $input;
    }
}
