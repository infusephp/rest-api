<?php

use Infuse\RestApi\Serializer\ChainedSerializer;

class ChainedSerializerTest extends PHPUnit_Framework_TestCase
{
    public function testSerialize()
    {
        $serializer1 = Mockery::mock('Infuse\RestApi\Serializer\SerializerInterface');
        $serializer1->shouldReceive('serialize')
                    ->andReturnUsing(function ($input) {
                        return $input + 1;
                    });

        $serializer2 = Mockery::mock('Infuse\RestApi\Serializer\SerializerInterface');
        $serializer2->shouldReceive('serialize')
                    ->andReturnUsing(function ($input) {
                        return $input * 2;
                    });

        $serializer = new ChainedSerializer();
        $serializer->add($serializer1)
                   ->add($serializer2);

        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');

        $result = 1;
        $this->assertEquals(4, $serializer->serialize($result, $route));

        $this->assertEquals([$serializer1, $serializer2], $serializer->getSerializers());
    }
}
