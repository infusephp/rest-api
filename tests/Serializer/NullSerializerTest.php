<?php

use App\RestApi\Serializer\NullSerializer;

class NullSerializerTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $serializer = new NullSerializer();
        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');
        $this->assertNull($serializer->serialize('input', $route));
    }
}
