<?php

use Infuse\RestApi\Error\InvalidRequest;
use Infuse\RestApi\Libs\ErrorStack;
use Infuse\Request;
use Infuse\Response;
use Infuse\Test;
use Mockery\Adapter\Phpunit\MockeryTestCase;

abstract class RouteTestBase extends MockeryTestCase
{
    public static $req;
    public static $res;

    public static function setUpBeforeClass()
    {
        self::$req = new Request();
        self::$res = new Response();

        Test::$app['errors'] = new ErrorStack(Test::$app);;
    }

    public static function tearDownAfterClass()
    {
        Test::$app['errors']->clear();
    }

    public function testGetRequest()
    {
        $route = $this->getRoute();
        $this->assertEquals(self::$req, $route->getRequest());
    }

    public function testGetResponse()
    {
        $route = $this->getRoute();
        $this->assertEquals(self::$res, $route->getResponse());
    }

    public function testGetSerializer()
    {
        $route = $this->getRoute();
        $serializer = Mockery::mock('Infuse\RestApi\Serializer\SerializerInterface');
        $this->assertEquals($route, $route->setSerializer($serializer));
        $this->assertEquals($serializer, $route->getSerializer());
    }

    public function testHandleError()
    {
        $error = new InvalidRequest('Username has already been taken', 401, 'username');

        $route = $this->getRoute();
        $response = $route->handleError($error);

        $expected = [
            'type' => 'invalid_request',
            'message' => 'Username has already been taken',
            'param' => 'username',
        ];
        $this->assertEquals($expected, $response);

        $this->assertEquals(401, self::$res->getCode());
    }

    public function testGetEndpoint()
    {
        $requestedUrl = 'https://example.com/api/v1/users/';
        $req = Request::create($requestedUrl, 'post');
        $route = $this->getRoute($req);

        // try without an API URL or base path defined
        Test::$app['config']->set('api.url', false);
        Test::$app['config']->set('api.base_path', null);
        Test::$app['base_url'] = 'https://example.com/';
        $this->assertEquals('https://example.com/api/v1/users', $route->getEndpoint());

        // try without any API URL but with a base path
        Test::$app['config']->set('api.base_path', '/api');
        $this->assertEquals('https://example.com/api/v1/users', $route->getEndpoint());

        // Try with an API URL and no base path
        Test::$app['config']->set('api.url', 'https://api.example.com/');
        Test::$app['config']->set('api.base_path', null);
        $this->assertEquals('https://api.example.com/api/v1/users', $route->getEndpoint());

        // Try with an API URL and a base path
        Test::$app['config']->set('api.base_path', '/api');
        $this->assertEquals('https://api.example.com/v1/users', $route->getEndpoint());
    }

    public function testHumanClassName()
    {
        $route = $this->getRoute();
        $this->assertEquals('Post', $route->humanClassName('App\Posts\Models\Post'));
        $this->assertEquals('Line Item', $route->humanClassName('App\Invoices\Models\LineItem'));
        $error = new InvalidRequest('error');
        $this->assertEquals('Invalid Request', $route->humanClassName($error));
    }

    public function testSingularClassName()
    {
        $route = $this->getRoute();
        $this->assertEquals('post', $route->singularClassName('App\Posts\Models\Post'));
        $this->assertEquals('line_item', $route->singularClassName('App\Invoices\Models\LineItem'));
        $error = new InvalidRequest('error');
        $this->assertEquals('invalid_request', $route->singularClassName($error));
    }

    public function testPluralClassName()
    {
        $route = $this->getRoute();
        $this->assertEquals('posts', $route->pluralClassName('App\Posts\Models\Post'));
        $this->assertEquals('line_items', $route->pluralClassName('App\Invoices\Models\LineItem'));
        $error = new InvalidRequest('error');
        $this->assertEquals('invalid_requests', $route->pluralClassName($error));
    }

    public function testRun()
    {
        $route = Mockery::mock(static::ROUTE_CLASS, [self::$req, self::$res])->makePartial();
        $route->shouldReceive('buildResponse')
              ->andReturn('RESPONSE')
              ->once();

        $serializer = Mockery::mock('Infuse\RestApi\Serializer\SerializerInterface');
        $serializer->shouldReceive('serialize')
                   ->withArgs(['RESPONSE', $route])
                   ->once();
        $route->setSerializer($serializer);

        $route->run();
    }

    public function testRunFail()
    {
        $route = Mockery::mock(static::ROUTE_CLASS, [self::$req, self::$res])->makePartial();
        $route->shouldReceive('buildResponse')
              ->andThrow(new InvalidRequest('error'))
              ->once();

        $serializer = Mockery::mock('Infuse\RestApi\Serializer\SerializerInterface');
        $expectedError = [
            'type' => 'invalid_request',
            'message' => 'error',
        ];
        $serializer->shouldReceive('serialize')
                   ->withArgs([$expectedError, $route])
                   ->once();
        $route->setSerializer($serializer);

        $route();
    }

    public function getRoute($req = null, $res = null)
    {
        if (!$req) {
            $req = self::$req;
        }

        if (!$res) {
            $res = self::$res;
        }

        $class = static::ROUTE_CLASS;
        $route = new $class($req, $res);
        $route->setApp(Test::$app);

        return $route;
    }
}
