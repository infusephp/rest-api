<?php

use Infuse\RestApi\Error\InvalidRequest;
use Infuse\RestApi\Libs\ErrorStack;
use Infuse\Application;
use Infuse\Request;
use Infuse\Test;
use Pulsar\Driver\DriverInterface;
use Pulsar\Model;
use Mockery\Adapter\Phpunit\MockeryTestCase;

abstract class ModelTestBase extends RouteTestBase
{
    static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();

        $driver = Mockery::mock(DriverInterface::class);
        Model::setDriver($driver);
    }

    public function testGetModelId()
    {
        $route = $this->getRoute();
        $route->setModelId(10);
        $this->assertEquals(10, $route->getModelId());
    }

    public function testGetModel()
    {
        $req = new Request();
        $req->setParams(['model' => 'Post']);

        $route = $this->getRoute($req);
        $this->assertInstanceOf('Post', $route->getModel());

        $model = new Post();
        $route->setModel($model);
        $this->assertEquals($model, $route->getModel());

        // try with model class name
        $this->assertEquals($route, $route->setModel('Post'));
        $this->assertInstanceOf('Post', $route->getModel());

        // try with model ID
        $route->setModelId(11);
        $this->assertEquals($route, $route->setModel('Post'));
        $model = $route->getModel();
        $this->assertInstanceOf('Post', $model);
        $this->assertEquals(11, $model->id());

        $route->setModelId(10);
        $model = $route->getModel();
        $this->assertInstanceOf('Post', $model);
        $this->assertEquals(10, $model->id());
    }

    public function testNotFound()
    {
        $req = Request::create('https://example.com/api/users', 'post');

        $route = $this->getRoute($req);

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('Request was not recognized: POST /api/users', $e->getMessage());
        $this->assertEquals(404, $e->getHttpStatus());
    }

    public function testNoPermission()
    {
        $class = static::ROUTE_CLASS;

        Test::$app['requester'] = $requester = Mockery::mock('Pulsar\Model');

        $route = $this->getRoute();
        $route->setModel('Post');
        $this->assertTrue($route->hasPermission());

        $model = Mockery::mock('Pulsar\ACLModel');
        $model->shouldReceive('id')
              ->andReturn(1);
        $model->shouldReceive('persisted')
            ->andReturn(true);
        $model->shouldReceive('can')
              ->withArgs([$class::MODEL_PERMISSION, $requester])
              ->andReturn(false);
        $route->setModel($model);
        $this->assertFalse($route->hasPermission());

        try {
            $route->buildResponse();
        } catch (InvalidRequest $e) {
        }

        $this->assertEquals('You do not have permission to do that', $e->getMessage());
        $this->assertEquals(403, $e->getHttpStatus());
    }

    public function testGetFirstError()
    {
        $route = $this->getRoute();
        $this->assertFalse($route->getFirstError());

        $model = new Post();
        $errors = $model->getErrors();
        $errors->add('Test');
        $errors->add('Test 2');
        $errors->add('Test 3');
        $route->setModel($model);

        $expected = [
            'error' => 'Test',
            'params' => [],
            'message' => 'Test',
        ];
        $this->assertEquals($expected, $route->getFirstError());
    }
}
