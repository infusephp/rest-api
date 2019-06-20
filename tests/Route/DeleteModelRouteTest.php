<?php

namespace Infuse\RestApi\Tests\Route;

use Infuse\Request;
use Infuse\RestApi\Error\ApiError;
use Infuse\RestApi\Error\InvalidRequest;
use Infuse\RestApi\Route\DeleteModelRoute;
use Infuse\RestApi\Tests\Person;
use Infuse\RestApi\Tests\Post;
use Mockery;
use Pulsar\Driver\DriverInterface;

class DeleteModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = DeleteModelRoute::class;

    public function testParseModelId()
    {
        $req = new Request();
        $req->setParams(['model_id' => 1]);
        $route = $this->getRoute($req);
        $this->assertEquals([1], $route->getModelId());
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
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
               ->andReturn([]);
        Person::setDriver($driver);

        $model = Person::class;
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
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('queryModels')
            ->andReturn([['id' => 1]]);
        $driver->shouldReceive('deleteModel')
               ->andReturn(false);
        Post::setDriver($driver);

        $model = Post::class;
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
        $model = Mockery::mock(new Person(10));
        $model->shouldReceive('delete')
              ->andReturn(false);
        $model->refreshWith(['name' => 'test']);
        $route = $this->getRoute();
        $route->setModel($model);
        $model->getErrors()->add('error');

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('error', $e->getMessage());
        $this->assertEquals(400, $e->getHttpStatus());
    }
}
