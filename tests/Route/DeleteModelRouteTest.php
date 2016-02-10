<?php

use App\RestApi\Error\Api as ApiError;
use App\RestApi\Error\InvalidRequest;
use Infuse\Request;
use Infuse\Test;

class DeleteModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'App\RestApi\Route\DeleteModelRoute';

    public function testParseModelId()
    {
        $req = new Request();
        $req->setParams(['model_id' => 1]);
        $route = $this->getRoute($req);
        $this->assertEquals(1, $route->getModelId());
    }

    public function testBuildResponse()
    {
        $model = Mockery::mock();
        $model->shouldReceive('delete')
              ->andReturn(true);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertNull($route->buildResponse());
        $this->assertEquals(204, self::$res->getCode());
    }

    public function testBuildResponseFail()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('deleteModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = 'Post';
        $route = $this->getRoute();
        $route->setModel($model);

        try {
            $route->buildResponse();
        } catch (ApiError $e) {
        }

        $this->assertEquals('There was an error deleting the Post.', $e->getMessage());
    }

    public function testBuildResponseFailWithError()
    {
        Test::$app['errors']->push('error');

        $model = Mockery::mock();
        $model->shouldReceive('delete')
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
