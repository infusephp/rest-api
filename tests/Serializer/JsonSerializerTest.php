<?php

use Infuse\Application;
use Infuse\Request;
use Infuse\Response;
use Infuse\RestApi\Serializer\JsonSerializer;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class JsonSerializerTest extends MockeryTestCase
{
    public function testGetJsonParams()
    {
        $serializer = new JsonSerializer(new Request());
        $this->assertEquals(0, $serializer->getJsonParams());
        $serializer->prettyPrint();
        $this->assertEquals(JSON_PRETTY_PRINT, $serializer->getJsonParams());
        $serializer->compactPrint();
        $this->assertEquals(0, $serializer->getJsonParams());
    }

    public function testSerialize()
    {
        $serializer = new JsonSerializer(new Request());

        $req = new Request();
        $res = new Response();

        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');
        $route->shouldReceive('getRequest')
              ->andReturn($req);
        $route->shouldReceive('getResponse')
              ->andReturn($res);

        // test string
        $result = 'blah';
        $serializer->serialize($result, $route);
        $this->assertEquals('blah', $result);

        // test object
        $result = new stdClass();
        $result->answer = 42;
        $result->nested = new stdClass();
        $result->nested->id = 10;
        $result->nested->name = 'John Appleseed';

        $serializer->serialize($result, $route);

        $this->assertEquals('application/json', $res->getContentType());

        // JSON should be compact by default
        $expected = '{"answer":42,"nested":{"id":10,"name":"John Appleseed"}}';
        $this->assertEquals($expected, $res->getBody());
    }

    public function testSerializePretty()
    {
        $serializer = new JsonSerializer(new Request(['pretty' => 1]));

        $res = new Response();
        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');
        $route->shouldReceive('getResponse')
              ->andReturn($res);

        $result = [
            'answer' => 42,
            'nested' => [
                'id' => 10,
                'name' => 'John Appleseed',
            ],
        ];

        $serializer->serialize($result, $route);

        // JSON should be pretty printed
        $expected = '{
    "answer": 42,
    "nested": {
        "id": 10,
        "name": "John Appleseed"
    }
}';
        $this->assertEquals($expected, $res->getBody());
    }

    public function testSerializePrettyCurl()
    {
        $serializer = new JsonSerializer(Request::create('/', 'GET', [], [], [], ['HTTP_USER_AGENT' => 'curl/7.47.0']));

        $res = new Response();
        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute');
        $route->shouldReceive('getResponse')
            ->andReturn($res);

        $result = [
            'answer' => 42,
            'nested' => [
                'id' => 10,
                'name' => 'John Appleseed',
            ],
        ];

        $serializer->serialize($result, $route);

        // JSON should be pretty printed
        $expected = '{
    "answer": 42,
    "nested": {
        "id": 10,
        "name": "John Appleseed"
    }
}';
        $this->assertEquals($expected, $res->getBody());
    }

    public function testSerializeError()
    {
        $serializer = new JsonSerializer(new Request());

        $app = new Application();
        $logger = Mockery::mock();
        $logger->shouldReceive('error');
        $app['logger'] = $logger;

        $res = new Response();
        $route = Mockery::mock('Infuse\RestApi\Route\AbstractRoute')->makePartial();
        $route->shouldReceive('getResponse')
              ->andReturn($res);
        $route->shouldReceive('getApp')
              ->andReturn($app);

        // An invalid UTF8 sequence
        $text = "\xB1\x31";
        $serializer->serialize([$text], $route);
    }
}
