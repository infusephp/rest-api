<?php

use infuse\Request;
use infuse\Response;

use app\api\libs\Api;
use app\api\libs\ApiRoute;

class ApiRouteTest extends \PHPUnit_Framework_TestCase
{
	static $app;
	private static $api;
	private static $req;
	private static $res;

	static function setUpBeforeClass()
	{
		self::$api = new Api( TestBootstrap::app() );
		self::$req = new Request();
		self::$res = new Response( TestBootstrap::app() );
	}

	function testExecute()
	{
		$route = new ApiRoute( [], function( $query ) { return new \stdClass; }, [] );

		$req = new Request();
		$res = new Response( TestBootstrap::app() );

		$route->execute( $req, $res );

		$this->markTestIncomplete();
	}
}