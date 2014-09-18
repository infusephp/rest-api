<?php

use infuse\Request;
use infuse\Response;

use app\api\libs\Api;
use app\api\libs\ApiRoute;

class ApiRouteTest extends \PHPUnit_Framework_TestCase
{
    private static $api;
    private static $req;
    private static $res;

    public static function setUpBeforeClass()
    {
        $app = TestBootstrap::app();
        self::$api = new Api( $app );
        self::$req = new Request();
        self::$res = new Response( $app );
    }

    public function testExecute()
    {
        $route = new ApiRoute( [], function ($query) { return new \stdClass(); }, [] );

        $app = TestBootstrap::app();
        $req = new Request();
        $res = new Response($app);

        $route->execute($req, $res, $app);

        $this->markTestIncomplete();
    }
}
