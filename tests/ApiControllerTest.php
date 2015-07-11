<?php

use infuse\Request;
use infuse\Response;
use app\api\libs\ApiController;
use app\api\libs\ApiRoute;
use app\api\libs\Error;

class ApiControllerTest extends PHPUnit_Framework_TestCase
{
    public function testCreateRoute()
    {
        $api = new ApiController();
        $req = new Request();
        $res = new Response();

        $route = $api->create($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals($api, $route->getController());
    }

    public function testFindAllRoute()
    {
        $api = new ApiController();
        $req = new Request();
        $res = new Response();

        $route = $api->findAll($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals($api, $route->getController());
    }

    public function testFindOneRoute()
    {
        $api = new ApiController();
        $req = new Request();
        $res = new Response();

        $route = $api->findOne($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals($api, $route->getController());
    }

    public function testEditRoute()
    {
        $api = new ApiController();
        $req = new Request();
        $res = new Response();

        $route = $api->edit($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals($api, $route->getController());
    }

    public function testDeleteRoute()
    {
        $api = new ApiController();
        $req = new Request();
        $res = new Response();

        $route = $api->delete($req, $res, false);

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
        $this->assertEquals($api, $route->getController());
    }

    public function testParseRouteBase()
    {
        Test::$app['base_url'] = 'https://example.com/';

        $route = new ApiRoute();
        $req = Mockery::mock('\\infuse\\Request');
        $req->shouldReceive('path')->andReturn('/api/users/');
        $route->setRequest($req);

        $api = new ApiController();
        $api->injectApp(Test::$app);

        $this->assertNull($api->parseRouteBase($route));
        $this->assertEquals('https://example.com/api/users', $route->getQuery('endpoint_url'));

        Test::$app['config']->set('api.url', 'https://api.example.com');
        $this->assertNull($api->parseRouteBase($route));
        $this->assertEquals('https://api.example.com/users', $route->getQuery('endpoint_url'));
    }

    public function testParseFetchModelFromParamsAlreadySet()
    {
        $route = new ApiRoute();
        $route->addQueryParams([
            'module' => 'test',
            'model' => 'Test', ]);

        $api = new ApiController();
        $this->assertTrue($api->parseFetchModelFromParams($route));
    }

    public function testParseFetchModelFromParamsNoController()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([
            'module' => 'test',
            'model' => 'Test', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        $api = new ApiController();

        try {
            $api->parseFetchModelFromParams($route);
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
            'module' => 'test',
            'model' => 'TestModelNotFound', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        $testController = Mockery::mock('alias:app\\test\\Controller');

        $api = new ApiController();
        $this->assertFalse($api->parseFetchModelFromParams($route));
    }

    public function testParseFetchModelFromParams()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([
            'module' => 'test',
            'model' => 'TestModel', ]);
        $route->setRequest($req);

        $res = new Response();
        $route->setResponse($res);

        $testController = Mockery::mock('alias:app\\test\\Controller');

        $testModel = Mockery::mock('alias:app\\test\\models\\TestModel');

        $api = new ApiController();
        $this->assertNull($api->parseFetchModelFromParams($route));

        $this->assertEquals('app\\test\\models\\TestModel', $route->getQuery('model'));
    }

    public function testParseRequireApiScaffolding()
    {
        $model = new stdClass();
        $model->scaffoldApi = true;

        $route = new ApiRoute();
        $route->addQueryParams(['model' => $model]);

        $api = new ApiController();
        $this->assertNull($api->parseRequireApiScaffolding($route));
    }

    public function testParseRequireApiScaffoldingFail()
    {
        $req = Mockery::mock('infuse\\Request');
        $req->shouldReceive('method')->andReturn('GET');
        $req->shouldReceive('path')->andReturn('/users');

        $route = new ApiRoute();
        $route->addQueryParams(['model' => new \stdClass()]);
        $route->setRequest($req);

        $api = new ApiController();

        try {
            $api->parseRequireApiScaffolding($route);
        } catch (Error\InvalidRequest $ex) {
            $this->assertEquals(404, $ex->getHttpStatus());

            return;
        }

        $this->fail('An exception was not raised.');
    }

    public function testParseRequireFindPermission()
    {
        $this->markTestIncomplete();

        $model = Mockery::mock();

        $route = new ApiRoute();
        $route->addQueryParams(['model' => $model]);
    }

    public function testParseRequireViewPermission()
    {
        $this->markTestIncomplete();
    }

    public function testParseRequireCreatePermission()
    {
        $this->markTestIncomplete();
    }

    public function testParseModelCreateParameters()
    {
        $route = new ApiRoute();

        $test = ['test' => 'hello'];
        $req = new Request(['expand' => 'invoice,customer'], $test);
        $route->setRequest($req);

        $api = new ApiController();
        $this->assertNull($api->parseModelCreateParameters($route));

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

        $api = new ApiController();
        $this->assertNull($api->parseModelFindAllParameters($route));

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

        $api = new ApiController();
        $this->assertNull($api->parseModelFindAllParameters($route));

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
        $req->setParams(['id' => 101]);
        $route->setRequest($req);

        $api = new ApiController();
        $this->assertNull($api->parseModelFindOneParameters($route));

        $expected = [
            'model_id' => 101,
            'exclude' => [],
            'include' => [],
            'expand' => [], ];
        $this->assertEquals($expected, $route->getQuery());
    }

    public function testParseModelEditParameters()
    {
        $route = new ApiRoute();

        $test = [ 'test' => 'hello' ];
        $req = new Request(null, $test);
        $req->setParams([ 'id' => 101 ]);
        $route->setRequest($req);

        $api = new ApiController();
        $this->assertNull($api->parseModelEditParameters($route));
        $this->assertEquals(101, $route->getQuery('model_id'));
        $this->assertEquals($test, $route->getQuery('properties'));
    }

    public function testParseModelDeleteParameters()
    {
        $route = new ApiRoute();

        $req = new Request();
        $req->setParams([ 'id' => 102 ]);
        $route->setRequest($req);

        $api = new ApiController();
        $this->assertNull($api->parseModelDeleteParameters($route));
        $this->assertEquals(102, $route->getQuery('model_id'));
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
        $this->markTestIncomplete();
    }

    public function testTransformFindAll()
    {
        $this->markTestIncomplete();
    }

    public function testTransformPaginate()
    {
        $model = Mockery::mock('alias:ModelClass');
        // TODO deprecated
        $model->shouldReceive('totalRecords')->andreturn(500);

        $route = new ApiRoute();
        $res = new Response();
        $route->setResponse($res)
              ->addQueryParams([
                    'model' => 'ModelClass',
                    'page' => 2,
                    'per_page' => 50,
                    'endpoint_url' => '/api/models',
                    // TODO deprecated
                    'where' => [], ]);

        $req = new Request(['sort' => 'name ASC']);
        $route->setRequest($req);

        $result = new stdClass();
        $result->filtered_count = 200;

        $api = new ApiController();
        $api->transformPaginate($result, $route);

        $res = $route->getResponse();
        $this->assertEquals('200', $res->headers('X-Total-Count'));
        $this->assertEquals('</api/models?sort=name+ASC&per_page=50&page=2>; rel="self", </api/models?sort=name+ASC&per_page=50&page=1>; rel="first", </api/models?sort=name+ASC&per_page=50&page=1>; rel="previous", </api/models?sort=name+ASC&per_page=50&page=3>; rel="next", </api/models?sort=name+ASC&per_page=50&page=4>; rel="last"', $res->headers('Link'));
    }

    public function testTransformModelFindOne()
    {
        $this->markTestIncomplete();
    }

    public function testTransformModelEdit()
    {
        $route = new ApiRoute();

        $result = true;

        $api = new ApiController();
        $api->transformModelEdit($result, $route);

        $expected = new stdClass();
        $expected->success = true;

        $this->assertEquals($expected, $result);
    }

    public function testTransformModelEditFail()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('\\infuse\\Response');
        $res->shouldReceive('setCode')->withArgs([403])->once();
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();

        $app = new App();
        $errors = Mockery::mock();
        $errors->shouldReceive('messages')->andReturn(['error_message_1', 'error_message_2']);
        $errors->shouldReceive('errors')->andReturn([['error' => 'no_permission']]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        $api->transformModelEdit($result, $route);

        $expected = new stdClass();
        $expected->error = ['error_message_1','error_message_2'];

        $this->assertEquals($expected, $result);
    }

    public function testTransformModelDelete()
    {
        $route = new ApiRoute();
        $res = new Response();
        $route->setResponse($res);

        $result = true;

        $api = new ApiController();
        $api->transformModelDelete($result, $route);

        $this->assertEquals(204, $res->getCode());

        // TODO test delete failure
    }

    public function testTransformModelDeleteFail()
    {
        $route = new ApiRoute();
        $res = Mockery::mock('\\infuse\\Response');
        $res->shouldReceive('setCode')->withArgs([403])->once();
        $route->setResponse($res);

        $result = false;

        $api = new ApiController();

        $app = new App();
        $errors = Mockery::mock();
        $errors->shouldReceive('messages')->andReturn(['error_message_1', 'error_message_2']);
        $errors->shouldReceive('errors')->andReturn([['error' => 'no_permission']]);
        $app['errors'] = $errors;
        $api->injectApp($app);

        $api->transformModelDelete($result, $route);

        $expected = new stdClass();
        $expected->error = ['error_message_1','error_message_2'];

        $this->assertEquals($expected, $result);
    }

    public function testTansformOutputJson()
    {
        $route = new ApiRoute();

        $res = new Response();
        $route->setResponse($res);

        $req = new Request();
        $route->setRequest($req);

        $result = new stdClass();
        $result->answer = 42;
        $result->nested = new stdClass();
        $result->nested->id = 10;
        $result->nested->name = 'John Appleseed';

        $api = new ApiController();
        $api->transformOutputJson($result, $route);

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
        $api->transformOutputJson($result, $route);

        $expected = '{"answer":42,"nested":{"id":10,"name":"John Appleseed"}}';
        $this->assertEquals($expected, $res->getBody());
    }

    public function testHandleError()
    {
        $ex = new Error\InvalidRequest('Test', 404);

        $req = new Request();

        $expectedJSON = '{
    "type": "invalid_request",
    "message": "Test",
    "param": null
}';
        $res = Mockery::mock('infuse\\Response');
        $res->shouldReceive('setCode')
            ->withArgs([404])
            ->once();
        $res->shouldReceive('setContentType->setBody')
            ->withArgs([$expectedJSON])
            ->once();

        $route = new ApiRoute();
        $route->setResponse($res);
        $route->setRequest($req);

        $api = new ApiController();
        $api->handleError($ex, $route);
    }
}
