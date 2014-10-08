<?php

use infuse\Request;
use infuse\Response;

use app\api\libs\Api;
use app\api\libs\ApiRoute;

class ApiRouteTest extends \PHPUnit_Framework_TestCase
{
    public function testQueryParams()
    {
        $route = new ApiRoute();
        $this->assertEquals($route, $route->addQueryParams(['test' => true]));
        $this->assertEquals($route, $route->addQueryParams(['test2' => true]));

        $this->assertEquals(['test'=>true, 'test2'=>true], $route->getQuery());
        $this->assertTrue($route->getQuery('test'));
    }

    public function testRequest()
    {
        $route = new ApiRoute();
        $req = new Request();
        $this->assertEquals($route, $route->setRequest($req));
        $this->assertEquals($req, $route->getRequest());
    }

    public function testResponse()
    {
        $route = new ApiRoute();
        $res = new Response();
        $this->assertEquals($route, $route->setResponse($res));
        $this->assertEquals($res, $route->getResponse());
    }

    public function testParseSteps()
    {
        $route = new ApiRoute();
        $this->assertEquals($route, $route->addParseSteps(['step1', 'step2']));
        $this->assertEquals($route, $route->addParseSteps(['step3']));

        $this->assertEquals(['step1','step2','step3'], $route->getParseSteps());
    }

    public function testQueryStep()
    {
        $route = new ApiRoute();
        $this->assertEquals($route, $route->addQueryStep('step1'));
        $this->assertEquals($route, $route->addQueryStep('step2'));

        $this->assertEquals('step2', $route->getQueryStep());
    }

    public function testTransformSteps()
    {
        $route = new ApiRoute();
        $this->assertEquals($route, $route->addTransformSteps(['step1', 'step2']));
        $this->assertEquals($route, $route->addTransformSteps(['step3']));

        $this->assertEquals(['step1','step2','step3'], $route->getTransformSteps());
    }

    public function testExecute()
    {
        $route = new ApiRoute();

        $app = TestBootstrap::app();
        $req = new Request();
        $res = new Response();

        $mock = Mockery::mock();
        $mock->shouldReceive('parse')->withArgs([$route])->andReturn(true)->once();
        $mock->shouldReceive('query')->withArgs([$route])->andReturn(100)->once();
        $mock->shouldReceive('transform')->withArgs([100, $route])->andReturn(true)->once();

        $route->addParseSteps([[$mock, 'parse']])
              ->addQueryStep([$mock, 'query'])
              ->addTransformSteps([[$mock, 'transform']]);

        $this->assertTrue($route->execute($req, $res, $app));

        $this->assertEquals($req, $route->getRequest());
        $this->assertEquals($res, $route->getResponse());
    }
}
