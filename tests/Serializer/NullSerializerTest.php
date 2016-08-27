<?php

use Infuse\RestApi\Serializer\NullSerializer;

class NullSerializerTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $serializer = new NullSerializer();
        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');
        $this->assertNull($serializer->serialize('input', $route));
    }
}
