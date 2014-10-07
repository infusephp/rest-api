<?php

use app\api\Controller;

class ControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testController()
    {
        $controller = new Controller();

        $expected = [
            'post /api/:module' => 'create',
            'post /api/:module/:model' => 'create',
            'get /api/:module' => 'findAll',
            'get /api/:module/:model' => 'findAll',
            'get /api/:module/:model/:id' => 'findOne',
            'put /api/:module/:id' => 'edit',
            'put /api/:module/:model/:id' => 'edit',
            'delete /api/:module/:id' => 'delete',
            'delete /api/:module/:model/:id' => 'delete',
        ];
        $this->assertEquals($expected, $controller::$properties['routes']);
    }
}
