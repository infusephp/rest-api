<?php

use App\Api\Libs\ApiController;
use App\Api\Libs\ApiRoute;
use App\Api\Libs\Error;
use Infuse\Application;
use Infuse\Request;
use Infuse\Response;
use Infuse\Test;
use Pulsar\Model;

class ApiControllerTest extends PHPUnit_Framework_TestCase
{
    public static $api;

    public static function setUpBeforeClass()
    {
        Test::$app['requester'] = Mockery::mock();

        self::$api = new ApiController();
        self::$api->injectApp(Test::$app);
    }

    public function testNewApiRoute()
    {
        $req = new Request();
        $req->setParams(['test' => true]);
        $res = new Response();

        $route = self::$api->newApiRoute($req, $res);
        $this->assertInstanceOf('App\Api\Libs\ApiRoute', $route);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
        $this->assertEquals(['test' => true], $route->getQueryParams());
    }

    public function testCreateRoute()
    {
        $req = new Request();
        $res = new Response();

        $route = self::$api->create($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
    }

    public function testFindAllRoute()
    {
        $req = new Request();
        $res = new Response();

        $route = self::$api->findAll($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
    }

    public function testFindOneRoute()
    {
        $req = new Request();
        $res = new Response();

        $route = self::$api->findOne($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
    }

    public function testEditRoute()
    {
        $req = new Request();
        $res = new Response();

        $route = self::$api->edit($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
    }

    public function testDeleteRoute()
    {
        $req = new Request();
        $res = new Response();

        $route = self::$api->delete($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals(self::$api, $route->getController());
    }

    public function testParseRouteBase()
    {
        Test::$app['base_url'] = 'https://example.com/';

        $route = new ApiRoute();
        $req = Mockery::mock('Infuse\Request');
        $req->shouldReceive('path')->andReturn('/api/users/');
        $route->setRequest($req);

        $this->assertNull(self::$api->parseRouteBase($route));
        $this->assertEquals('https://example.com/api/users', $route->getQuery('endpoint_url'));

        Test::$app['config']->set('api.url', 'https://api.example.com');
        $this->assertNull(self::$api->parseRouteBase($route));
        $this->assertEquals('https://api.example.com/users', $route->getQuery('endpoint_url'));
    }

    public function testParseFetchModelFromParamsAlreadySet()
    {
        $req = new Request();

        Mockery::mock('alias:ModelAlreadyExists');

        $route = new ApiRoute();
        $route->setRequest($req);
        $route->addQueryParams(['model' => 'ModelAlreadyExists']);

        $this->assertNull(self::$api->parseFetchModelFromParams($route));
    }

    public function testParseFetchModelFromParamsNoController()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([
            'module' => 'Test',
            'model' => 'Test', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        try {
            self::$api->parseFetchModelFromParams($route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals(404, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testParseFetchModelFromParamsNotFound()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([
            'module' => 'Test',
            'model' => 'TestModelNotFound', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        $testController = Mockery::mock('alias:App\Test\Controller');

        $this->assertFalse(self::$api->parseFetchModelFromParams($route));
    }

    public function testParseFetchModelFromParams()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([
            'module' => 'Test',
            'model' => 'TestModel', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        $testController = Mockery::mock('alias:App\Test\Controller');

        $testModel = Mockery::mock('alias:App\Test\Models\TestModel');

        $this->assertNull(self::$api->parseFetchModelFromParams($route));

        $this->assertEquals('App\Test\Models\TestModel', $route->getQuery('model'));
    }

    public function testParseRequireApiScaffolding()
    {
        $model = new stdClass();
        $model->scaffoldApi = true;

        $route = new ApiRoute();
        $route->addQueryParams(['model' => $model]);

        $this->assertNull(self::$api->parseRequireApiScaffolding($route));
    }

    public function testParseRequireApiScaffoldingFail()
    {
        $req = Mockery::mock('Infuse\Request');
        $req->shouldReceive('method')->andReturn('GET');
        $req->shouldReceive('path')->andReturn('/users');

        $route = new ApiRoute();
        $route->addQueryParams(['model' => new \stdClass()]);
        $route->setRequest($req);

        try {
            self::$api->parseRequireApiScaffolding($route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals(404, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testParseRequireFindPermission()
    {
        $testModel = Mockery::mock();
        $testModel->shouldReceive('can')
                  ->withArgs(['find', Test::$app['requester']])
                  ->andReturn(true)
                  ->once();

        $route = new ApiRoute();
        $route->addQueryParams(['model' => $testModel]);

        self::$api->parseRequireFindPermission($route);
    }

    public function testParseRequireCreatePermission()
    {
        $testModel = Mockery::mock();
        $testModel->shouldReceive('can')
                  ->withArgs(['create', Test::$app['requester']])
                  ->andReturn(true)
                  ->once();

        $route = new ApiRoute();
        $route->addQueryParams(['model' => $testModel]);

        self::$api->parseRequireCreatePermission($route);
    }

    public function testParseModelCreateParameters()
    {
        $route = new ApiRoute();

        $test = ['test' => 'hello'];
        $req = new Request(['expand' => 'invoice,customer'], $test);
        $route->setRequest($req);

        $this->assertNull(self::$api->parseModelCreateParameters($route));

        $this->assertEquals($test, $route->getQuery('properties'));
        $this->assertEquals(['invoice', 'customer'], $route->getQuery('expand'));
    }

    public function testParseModelFindAllParameters()
    {
        $route = new ApiRoute();

        $req = new Request([
            'page' => 2,
            'per_page' => 100,
            'sort' => 'name ASC',
            'search' => 'test',
            'filter' => [
                'name' => 'john',
                'year' => 2012,
                // the elements below are invalid and should be removed
                'test',
                'OR1=1' => 'whatever',
                'test' => ['test'],
                'test2' => new stdClass(), ],
            'include' => ['customer'],
            'exclude' => ['password'],
            'expand' => [
                'customer.address',
                'invoice', ], ]);
        $route->setRequest($req);

        $this->assertNull(self::$api->parseModelFindAllParameters($route));

        $expected = [
            'page' => 2,
            'per_page' => 100,
            'start' => 100,
            'limit' => 100,
            'sort' => 'name ASC',
            'search' => 'test',
            'where' => [
                'name' => 'john',
                'year' => 2012, ],
            'include' => ['customer'],
            'exclude' => ['password'],
            'expand' => [
                'customer.address',
                'invoice', ], ];
        $this->assertEquals($expected, $route->getQuery());

        // deprecated pagination
        $route = new ApiRoute();

        $req = new Request([
            'start' => 90,
            'limit' => 90, ]);
        $route->setRequest($req);

        $this->assertNull(self::$api->parseModelFindAllParameters($route));

        $expected = [
            'page' => 2,
            'per_page' => 90,
            'start' => 90,
            'limit' => 90,
            'sort' => '',
            'search' => '',
            'where' => [],
            'include' => [],
            'exclude' => [],
            'expand' => [], ];
        $this->assertEquals($expected, $route->getQuery());
    }

    public function testParseModelFindOneParameters()
    {
        $route = new ApiRoute();

        $req = new Request();
        $route->setRequest($req);

        $this->assertNull(self::$api->parseModelFindOneParameters($route));

        $expected = [
            'exclude' => [],
            'include' => [],
            'expand' => [], ];
        $this->assertEquals($expected, $route->getQuery());
    }

    public function testParseModelEditParameters()
    {
        $route = new ApiRoute();

        $test = ['test' => 'hello'];
        $req = new Request(null, $test);
        $route->setRequest($req);

        $this->assertNull(self::$api->parseModelEditParameters($route));
        $this->assertEquals($test, $route->getQuery('properties'));
    }

    public function testQueryModelCreate()
    {
        $this->markTestIncomplete();
    }

    public function testQueryModelFindAll()
    {
        $this->markTestIncomplete();
    }

    public function testQueryModelFindOne()
    {
        $this->markTestIncomplete();
    }

    public function testQueryModelEdit()
    {
        $this->markTestIncomplete();
    }

    public function testQueryModelDelete()
    {
        $this->markTestIncomplete();
    }

    public function testTransformModelCreate()
    {
        $res = new Response();
        $route = new ApiRoute();
        $route->setResponse($res);

        $result = true;

        // shouldn't throw any exceptions
        self::$api->transformModelCreate($result, $route);

        // should return 201 code
        $this->assertEquals(201, $res->getCode());
    }

    public function testTransformModelCreateNoPermission()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('setCode')->withArgs([403])->once();
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $error = [
            'error' => 'no_permission',
            'message' => 'No Permission',
        ];
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([$error]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelCreate($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals('No Permission', $ex->getMessage());
            $this->assertEquals(403, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelCreateFail()
    {
        $route = new ApiRoute();
        $route->addQueryParams([
            'model' => 'user', ]);

        $res = Mockery::mock('Infuse\Response');
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelCreate($result, $route);
        } catch (Error\Api $ex) {
            $this->assertEquals('There was an error creating the User.', $ex->getMessage());
            $this->assertEquals(500, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformPaginate()
    {
        $route = new ApiRoute();
        $res = new Response();
        $route->setResponse($res)
              ->addQueryParams([
                    'model' => 'ModelClass',
                    'page' => 2,
                    'per_page' => 50,
                    'endpoint_url' => '/api/models',
                    'total_count' => 200, ]);

        $req = new Request(['sort' => 'name ASC']);
        $route->setRequest($req);

        $result = new stdClass();
        self::$api->transformPaginate($result, $route);

        $res = $route->getResponse();
        $this->assertEquals('</api/models?sort=name+ASC&per_page=50&page=2>; rel="self", </api/models?sort=name+ASC&per_page=50&page=1>; rel="first", </api/models?sort=name+ASC&per_page=50&page=1>; rel="previous", </api/models?sort=name+ASC&per_page=50&page=3>; rel="next", </api/models?sort=name+ASC&per_page=50&page=4>; rel="last"', $res->headers('Link'));
    }

    public function testTransformModelFindOne()
    {
        $route = new ApiRoute();

        $result = Mockery::mock();
        $result->shouldReceive('exists')
               ->andReturn(true)
               ->once();
        $result->shouldReceive('can')
               ->withArgs(['view', Test::$app['requester']])
               ->andReturn(true)
               ->once();

        self::$api->transformModelFindOne($result, $route);
    }

    public function testTransformModelFindOneNotExists()
    {
        $route = new ApiRoute();

        $result = Mockery::mock('TestModel');
        $result->shouldReceive('exists')
               ->andReturn(false);

        try {
            self::$api->transformModelFindOne($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals(404, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelFindOneNoPermission()
    {
        $route = new ApiRoute();

        $result = Mockery::mock();
        $result->shouldReceive('exists')
               ->andReturn(true)
               ->once();
        $result->shouldReceive('can')
               ->withArgs(['view', Test::$app['requester']])
               ->andReturn(false)
               ->once();

        try {
            self::$api->transformModelFindOne($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals(403, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelEdit()
    {
        $route = new ApiRoute();

        $result = true;

        // shouldn't throw any exceptions
        self::$api->transformModelEdit($result, $route);
    }

    public function testTransformModelEditNoPermission()
    {
        $route = new ApiRoute();

        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('setCode')
            ->withArgs([403])
            ->once();
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $error = [
            'error' => 'no_permission',
            'message' => 'No Permission',
        ];
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([$error]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelEdit($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals('No Permission', $ex->getMessage());
            $this->assertEquals(403, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelEditInvalid()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('Infuse\Response');
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $error = [
            'error' => 'invalid_parameter',
            'message' => 'Invalid',
            'params' => ['field' => 'number'],
        ];
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([$error]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelEdit($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals('Invalid', $ex->getMessage());
            $this->assertEquals(400, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelEditFail()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('Infuse\Response');
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelEdit($result, $route);
        } catch (Error\Api $ex) {
            $this->assertEquals('There was an error performing the update.', $ex->getMessage());
            $this->assertEquals(500, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelToArray()
    {
        $route = new ApiRoute();
        $route->addQueryParams([
            'model' => 'TestModel',
            'exclude' => ['exclude'],
            'include' => ['include'],
            'expand' => ['expand'], ]);

        $req = new Request();
        $route->setRequest($req);

        $result = Mockery::mock('TestModel');
        $result->shouldReceive('toArrayDeprecated')
               ->withArgs([['exclude'], ['include'], ['expand']])
               ->andReturn(['model'])
               ->once();

        self::$api->transformModelToArray($result, $route);

        // result should be replaced with the output from toArray()
        $this->assertEquals(['model'], $result);
    }

    public function testTransformModelToArrayMultiple()
    {
        $route = new ApiRoute();
        $route->addQueryParams([
            'model' => 'TestModel',
            'exclude' => ['exclude'],
            'include' => ['include'],
            'expand' => ['expand'], ]);

        $result = [];
        for ($i = 1; $i <= 5; ++$i) {
            $obj = Mockery::mock('TestModel');
            $obj->shouldReceive('toArrayDeprecated')
                ->withArgs([['exclude'], ['include'], ['expand']])
                ->andReturn($i)
                ->once();
            $result[] = $obj;
        }

        self::$api->transformModelToArray($result, $route);

        // result should be replaced with the output from toArray()
        $this->assertEquals([1, 2, 3, 4, 5], $result);
    }

    public function testTransformModelDelete()
    {
        $route = new ApiRoute();
        $res = new Response();
        $route->setResponse($res);

        $result = true;

        self::$api->transformModelDelete($result, $route);

        $this->assertEquals(204, $res->getCode());
    }

    public function testTransformModelDeleteNoPermission()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('setCode')->withArgs([403])->once();
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $errors = Mockery::mock();
        $error = [
            'error' => 'no_permission',
            'message' => 'No Permission',
        ];
        $errors->shouldReceive('errors')
               ->andReturn([$error]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelDelete($result, $route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals('No Permission', $ex->getMessage());
            $this->assertEquals(403, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTransformModelDeleteFail()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('Infuse\Response');
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();
        $app = new Application();
        $errors = Mockery::mock();
        $errors->shouldReceive('errors')
               ->andReturn([]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        try {
            $api->transformModelDelete($result, $route);
        } catch (Error\Api $ex) {
            $this->assertEquals('There was an error performing the delete.', $ex->getMessage());
            $this->assertEquals(500, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testTansformOutputJson()
    {
        $route = new ApiRoute();

        $result = 'blah';
        self::$api->transformOutputJson($result, $route);
        $this->assertEquals('blah', $result);

        $res = new Response();
        $route->setResponse($res);

        $req = new Request();
        $route->setRequest($req);

        $result = new stdClass();
        $result->answer = 42;
        $result->nested = new stdClass();
        $result->nested->id = 10;
        $result->nested->name = 'John Appleseed';

        self::$api->transformOutputJson($result, $route);

        // JSON should be pretty-printed by default
        $expected = '{
    "answer": 42,
    "nested": {
        "id": 10,
        "name": "John Appleseed"
    }
}';
        $this->assertEquals($expected, $res->getBody());

        // JSON should be compacted with ?compact=true
        $route->addQueryParams(['compact' => true]);
        self::$api->transformOutputJson($result, $route);

        $expected = '{"answer":42,"nested":{"id":10,"name":"John Appleseed"}}';
        $this->assertEquals($expected, $res->getBody());
    }

    public function testHandleError()
    {
        $ex = new Error\InvalidRequest('Test', 404, 'param');

        $req = new Request();

        $expectedJSON = '{
    "type": "invalid_request",
    "message": "Test",
    "param": "param"
}';
        $res = Mockery::mock('Infuse\Response');
        $res->shouldReceive('setCode')
            ->withArgs([404])
            ->once();
        $res->shouldReceive('setContentType->setBody')
            ->withArgs([$expectedJSON])
            ->once();

        $route = new ApiRoute();
        $route->setResponse($res);
        $route->setRequest($req);

        self::$api->handleError($ex, $route);
    }

    public function testToArrayDeprecated()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel(5);

        $expected = [
            'id' => 5,
            'relation' => null,
            'answer' => null,
            'test_hook' => null,
            'appended' => true,
            // this is tacked on in toArrayHook() below
            'toArray' => true,
        ];

        $this->assertEquals($expected, $model->toArrayDeprecated([], [], ['relation']));
    }

    public function testToArrayDeprecatedExcluded()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel(5);
        $model->relation = 100;

        $expected = [
            'relation' => 100,
        ];

        $this->assertEquals($expected, $model->toArrayDeprecated(['id', 'answer', 'toArray', 'test_hook', 'appended']));
    }

    public function testToArrayDeprecatedAutoTimestamps()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel2::setDriver($driver);

        $model = new TestModel2(5);
        $model->created_at = 100;
        $model->updated_at = 102;

        $expected = [
            'created_at' => 100,
            'updated_at' => '102',
        ];

        $this->assertEquals($expected, $model->toArrayDeprecated(['id', 'id2', 'default', 'validate', 'unique', 'required']));

        $model->created_at = '-1';
        $this->assertEquals(-1, $model->created_at);
    }

    public function testToArrayDeprecatedIncluded()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel2(5);
        $model->hidden = true;
        $model->object = new stdClass();

        $expected = [
            'hidden' => true,
            'array' => [
                'tax' => '%',
                'discounts' => false,
                'shipping' => false,
            ],
            'object' => new stdClass(),
            'toArrayHook' => true,
        ];

        $this->assertEquals($expected, $model->toArrayDeprecated(['id', 'id2', 'default', 'validate', 'unique', 'required', 'created_at', 'updated_at'], ['hidden', 'toArrayHook', 'array', 'object']));
    }

    public function testToArrayDeprecatedExpand()
    {
        $driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        $driver->shouldReceive('loadModel')
               ->andReturn([]);

        TestModel::setDriver($driver);

        $model = new TestModel(10);
        $model->relation = 100;
        $model->answer = 42;

        $result = $model->toArrayDeprecated(
            [
                'id',
                'toArray',
                'test_hook',
                'appended',
                'relation.created_at',
                'relation.updated_at',
                'relation.validate',
                'relation.unique',
                'relation.person.email',
            ],
            [
                'relation.hidden',
                'relation.person',
            ],
            [
                'relation.person',
            ]);

        $expected = [
            'answer' => 42,
            'relation' => [
                'id' => 100,
                'id2' => 0,
                'required' => null,
                'default' => 'some default value',
                'hidden' => false,
                'person' => [
                    'id' => 20,
                    'name' => 'Jared',
                ],
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}

class TestModel extends Model
{
    protected static $properties = [
        'relation' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'answer' => [
            'type' => Model::TYPE_STRING,
        ],
        'mutator' => [],
        'accessor' => [],
        'test_model2_id' => [],
    ];

    protected static $hidden = [
        'mutator',
        'accessor',
        'test_model2_id',
    ];
    protected static $appended = ['appended'];
}
