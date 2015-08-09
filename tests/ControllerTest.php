<?php

use app\api\Controller;
use infuse\Request;
use infuse\Response;

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
            'get /api/:module/:model/:id' => ['api\\Controller', 'findOne'],
            'put /api/:module/:id' => ['api\\Controller', 'edit'],
            'put /api/:module/:model/:id' => ['api\\Controller', 'edit'],
            'patch /api/:module/:id' => ['api\\Controller', 'edit'],
            'patch /api/:module/:model/:id' => ['api\\Controller', 'edit'],
            'delete /api/:module/:id' => ['api\\Controller', 'delete'],
            'delete /api/:module/:model/:id' => ['api\\Controller', 'delete'],
        ];

        $this->assertEquals($expected, $app->getRoutes());
    }
}
