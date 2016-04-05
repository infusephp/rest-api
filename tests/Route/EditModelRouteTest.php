<?php

use App\RestApi\Error\ApiError;
use App\RestApi\Error\InvalidRequest;
use Infuse\Request;
use Infuse\Test;

class EditModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'App\RestApi\Route\EditModelRoute';

    public function testParseModelId()
    {
        $req = new Request();
        $req->setParams(['model_id' => 1]);
        $route = $this->getRoute($req);
        $this->assertEquals(1, $route->getModelId());
    }

    public function testGetUpdateParameters()
    {
        $req = new Request([], ['test' => true]);
        $route = $this->getRoute($req);

        $expected = [
            'test' => true,
        ];
        $this->assertEquals($expected, $route->getUpdateParameters());
    }

    public function testBuildResponse()
    {
        $model = Mockery::mock();
        $model->shouldReceive('exists')
              ->andReturn(true);
        $model->shouldReceive('set')
              ->andReturn(true);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertEquals($model, $route->buildResponse());
    }

    public function testBuildResponseNotFound()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('totalRecords')
               ->andReturn(0);
        Person::setDriver($driver);

        $model = 'Person';
        $route = $this->getRoute();
        $route->setModelId(100)
              ->setModel($model);

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('Person was not found: 100', $e->getMessage());
        $this->assertEquals(404, $e->getHttpStatus());
    }

    public function testBuildResponseSetFail()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('totalRecords')
               ->andReturn(1);
        $driver->shouldReceive('updateModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = 'Post';
        $route = $this->getRoute();
        $route->setModel($model)
              ->setUpdateParameters(['test' => true]);

        try {
            $route->buildResponse();
        } catch (ApiError $e) {
        }

        $this->assertEquals('There was an error updating the Post.', $e->getMessage());
    }

    public function testBuildResponseValidationError()
    {
        Test::$app['errors']->push('error');

        $model = Mockery::mock();
        $model->shouldReceive('exists')
              ->andReturn(true);
        $model->shouldReceive('set')
              ->andReturn(false);
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
