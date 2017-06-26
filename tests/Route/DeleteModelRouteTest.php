<?php

use Infuse\RestApi\Error\ApiError;
use Infuse\RestApi\Error\InvalidRequest;
use Infuse\Request;
use Infuse\Test;

class DeleteModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'Infuse\RestApi\Route\DeleteModelRoute';

    public function testParseModelId()
    {
        $req = new Request();
        $req->setParams(['model_id' => 1]);
        $route = $this->getRoute($req);
        $this->assertEquals(1, $route->getModelId());
    }

    public function testBuildResponse()
    {
        $model = new Person(100);
        $model = Mockery::mock($model);
        $model->refreshWith(['name' => 'Bob']);
        $model->shouldReceive('delete')
              ->andReturn(true);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertNull($route->buildResponse());
        $this->assertEquals(204, self::$res->getCode());
    }

    public function testBuildResponseNotFound()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('queryModels')
               ->andReturn([]);
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

    public function testBuildResponseDeleteFail()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        $driver->shouldReceive('queryModels')
            ->andReturn([['id' => 1]]);
        $driver->shouldReceive('deleteModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = 'Post';
        $route = $this->getRoute();
        $route->setModel($model)->setModelId(1);

        try {
            $route->buildResponse();
        } catch (ApiError $e) {
        }

        $this->assertEquals('There was an error deleting the Post.', $e->getMessage());
    }

    public function testBuildResponseValidationError()
    {
        Test::$app['errors']->push('error');

        $model = Mockery::mock();
        $model->shouldReceive('id')
            ->andReturn(1);
        $model->shouldReceive('persisted')
              ->andReturn(true);
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
