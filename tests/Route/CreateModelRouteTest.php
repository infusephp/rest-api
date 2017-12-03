<?php

use Infuse\Request;
use Infuse\Response;
use Infuse\RestApi\Error\ApiError;
use Infuse\RestApi\Error\InvalidRequest;
use Infuse\RestApi\Route\CreateModelRoute;
use Infuse\Test;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class CreateModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'Infuse\RestApi\Route\CreateModelRoute';

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
        $model->shouldReceive('id')
              ->andReturn(1);
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
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('createModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $post = new Post();
        $model = Mockery::mock($post);
        $model->shouldReceive('create')->andReturn(false);
        $model->getErrors()->add('error');
        $route = $this->getRoute();
        $route->setModel($model);

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('error', $e->getMessage());
        $this->assertEquals(400, $e->getHttpStatus());
    }

    public function testBuildResponseMassAssignmentError()
    {
        $req = Request::create('/', 'POST', ['not_allowed' => true]);
        $route = $this->getRoute($req);
        $route->setModel(Book::class);

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('Mass assignment of not_allowed on Book is not allowed', $e->getMessage());
        $this->assertEquals(400, $e->getHttpStatus());
    }

    public function testInvalidRequestBody()
    {
        $this->expectException('Infuse\RestApi\Error\InvalidRequest');

        $req = Mockery::mock(new Request());
        $req->shouldReceive('request')
            ->andReturn('invalid');

        $res = new Response();

        $route = new CreateModelRoute($req, $res);
        $route->setModel('Post');

        $route->buildResponse();
    }
}
