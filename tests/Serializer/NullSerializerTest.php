<?php

use Infuse\RestApi\Serializer\NullSerializer;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class NullSerializerTest extends MockeryTestCase
{
    public function testSerialize()
    {
        $serializer = new NullSerializer();
        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');
        $this->assertNull($serializer->serialize('input', $route));
    }
}
