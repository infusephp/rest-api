<?php

use app\api\Controller;
use Infuse\Request;
use Infuse\Response;

class ControllerTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultRoutes()
    {
        $controller = new Controller();

        $app = new App();
        $req = new Request();
        $res = new Response();
        $controller->injectApp($app);

        $controller->middleware($req, $res);

        $expected = [
            'post /api/:module' => ['api\\Controller', 'create'],
            'post /api/:module/:model' => ['api\\Controller', 'create'],
            'get /api/:module' => ['api\\Controller', 'findAll'],
            'get /api/:module/:model' => ['api\\Controller', 'findAll'],
            'get /api/:module/:model/:model_id' => ['api\\Controller', 'findOne'],
            'put /api/:module/:model_id' => ['api\\Controller', 'edit'],
            'put /api/:module/:model/:model_id' => ['api\\Controller', 'edit'],
            'patch /api/:module/:model_id' => ['api\\Controller', 'edit'],
            'patch /api/:module/:model/:model_id' => ['api\\Controller', 'edit'],
            'delete /api/:module/:model_id' => ['api\\Controller', 'delete'],
            'delete /api/:module/:model/:model_id' => ['api\\Controller', 'delete'],
        ];

        $this->assertEquals($expected, $app->getRoutes());
    }
}
