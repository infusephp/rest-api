<?php

use App\RestApi\Error\Api as ApiError;
use App\RestApi\Error\InvalidRequest;
use Infuse\Request;
use Infuse\Test;

class CreateModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'App\RestApi\Route\CreateModelRoute';

    public function testGetCreateParameters()
    {
        $req = new Request([], ['test' => true]);
        $route = $this->getRoute($req);

        $this->assertEquals(['test' => true], $route->getCreateParameters());
        $route->setCreateParameters(['test' => false]);
        $this->assertEquals(['test' => false], $route->getCreateParameters());
    }

    public function testBuildResponse()
    {
        $model = Mockery::mock();
        $model->shouldReceive('create')
              ->andReturn(true);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertEquals($model, $route->buildResponse());
        $this->assertEquals(201, self::$res->getCode());
    }

    public function testBuildResponseFail()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('createModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = 'Post';
        $route = $this->getRoute();
        $route->setModel($model);

        try {
            $route->buildResponse();
        } catch (ApiError $e) {
        }

        $this->assertEquals('There was an error creating the Post.', $e->getMessage());
    }

    public function testBuildResponseFailWithError()
    {
        Test::$app['errors']->push('error');

        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('createModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = 'Post';
        $route = $this->getRoute();
        $route->setModel($model);

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('error', $e->getMessage());
        $this->assertEquals(400, $e->getHttpStatus());
    }
}