<?php

use App\RestApi\Error\Api as ApiError;
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
        $model->shouldReceive('set')
              ->andReturn(true);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertEquals($model, $route->buildResponse());
    }

    public function testBuildResponseFail()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
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

    public function testBuildResponseFailWithError()
    {
        Test::$app['errors']->push('error');

        $model = Mockery::mock();
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
