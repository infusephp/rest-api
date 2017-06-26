<?php

use Infuse\RestApi\Error\InvalidRequest;
use Infuse\Request;
use Pulsar\Driver\DriverInterface;

class RetrieveModelRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = 'Infuse\RestApi\Route\RetrieveModelRoute';

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
        $model->refreshWith(['name' => 'Bob']);
        $route = $this->getRoute();
        $route->setModel($model);

        $this->assertEquals($model, $route->buildResponse());
    }

    public function testBuildResponseNotFound()
    {
        $driver = Mockery::mock(DriverInterface::class);
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
}
