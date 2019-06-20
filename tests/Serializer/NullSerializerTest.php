<?php

namespace Infuse\RestApi\Tests\Serializer;

use Infuse\RestApi\Serializer\NullSerializer;
use Mockery;
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
