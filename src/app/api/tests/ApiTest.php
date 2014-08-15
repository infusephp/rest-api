<?php

use infuse\Request;
use infuse\Response;

use app\api\libs\Api;

class ApiTest extends \PHPUnit_Framework_TestCase
{
	private static $api;
	private static $req;
	private static $res;

	static function setUpBeforeClass()
	{
		self::$api = new Api( TestBootstrap::app() );
		self::$req = new Request();
		self::$res = new Response( TestBootstrap::app() );
	}

	function testParseFetchModel()
	{
		$this->markTestIncomplete();
	}

	function testParseRequireApiScaffolding()
	{
		$model = new \stdClass();
		$model->scaffoldApi = true;

		$query = [ 'model' => $model ];

		$this->assertTrue( self::$api->parseRequireApiScaffolding( self::$req, self::$res, $query ) );
	}

	function testParseRequireApiScaffoldingFail()
	{
		$query = [ 'model' => new \stdClass() ];

		$this->assertFalse( self::$api->parseRequireApiScaffolding( self::$req, self::$res, $query ) );
	}

	function testParseRequireJson()
	{
		$req = new Request( null, null, null, null, [ 'HTTP_ACCEPT' => 'application/json' ] );
		$query = [];

		$this->assertTrue( self::$api->parseRequireJson( $req, self::$res, $query ) );
	}

	function testParseRequireJsonFail()
	{
		$query = [];
		$this->assertFalse( self::$api->parseRequireJson( self::$req, self::$res, $query ) );
	}

	function testParseRequireFindPermission()
	{
		$this->markTestIncomplete();
	}

	function testParseRequireViewPermission()
	{
		$this->markTestIncomplete();
	}

	function testParseRequireCreatePermission()
	{
		$this->markTestIncomplete();
	}

	function testParseModelCreateParameters()
	{
		$test = [ 'test' => 'hello' ];
		$req = new Request( [ 'expand' => 'invoice' ], $test );
		$query = [];

		$this->assertTrue( self::$api->parseModelCreateParameters( $req, self::$res, $query ) );

		$this->assertEquals( $test, $query[ 'properties' ] );
		$this->assertEquals( [ 'invoice' ], $query[ 'expand' ] );
	}

	function testParseModelFindAllParameters()
	{
		$req = new Request( [
			'start' => 10,
			'limit' => 90,
			'sort' => 'name ASC',
			'search' => 'test',
			'filter' => [
				'name' => 'john',
				'year' => 2012
			],
			'expand' => [
				'customer.address',
				'invoice'
			] ] );
		$query = [];

		$this->assertTrue( self::$api->parseModelFindAllParameters( $req, self::$res, $query ) );

		$expected = [
			'start' => 10,
			'limit' => 90,
			'sort' => 'name ASC',
			'search' => 'test',
			'where' => [
				'name' => 'john',
				'year' => 2012
			],
			'expand' => [
				'customer.address',
				'invoice'
			] ];
		$this->assertEquals( $expected, $query );
	}

	function testParseModelFindOneParameters()
	{
		self::$req->setParams( [ 'id' => 101 ] );
		$query = [];

		$this->assertTrue( self::$api->parseModelFindOneParameters( self::$req, self::$res, $query ) );

		$expected = [
			'model_id' => 101,
			'expand' => [] ];
		$this->assertEquals( $expected, $query );
	}

	function testParseModelEditParameters()
	{
		$test = [ 'test' => 'hello' ];
		$req = new Request( null, $test );
		$req->setParams( [ 'id' => 101 ] );
		$query = [];

		$this->assertTrue( self::$api->parseModelEditParameters( $req, self::$res, $query ) );
		$this->assertEquals( 101, $query[ 'model_id' ] );
		$this->assertEquals( $test, $query[ 'properties' ] );
	}

	function testParseModelDeleteParameters()
	{
		self::$req->setParams( [ 'id' => 102 ] );
		$query = [];

		$this->assertTrue( self::$api->parseModelDeleteParameters( self::$req, self::$res, $query ) );
		$this->assertEquals( 102, $query[ 'model_id' ] );
	}

	function testQueryModelCreate()
	{
		$this->markTestIncomplete();
	}

	function testQueryModelFindAll()
	{
		$this->markTestIncomplete();
	}

	function testQueryModelFindOne()
	{
		$this->markTestIncomplete();
	}

	function testQueryModelEdit()
	{
		$this->markTestIncomplete();
	}

	function testQueryModelDelete()
	{
		$this->markTestIncomplete();
	}

	function testTransformModelCreate()
	{
		$this->markTestIncomplete();
	}

	function testTransformFindAll()
	{
		$this->markTestIncomplete();
	}

	function testTransformPaginate()
	{
		$this->markTestIncomplete();
	}

	function testTransformModelFindOne()
	{
		$this->markTestIncomplete();
	}

	function testTransformModelEdit()
	{
		$result = true;

		self::$api->transformModelEdit( self::$res, [], $result );

		$expected = new \stdClass();
		$expected->success = true;

		$this->assertEquals( $expected, $result );
	}

	function testTransformModelEditFail()
	{
		$result = false;

		self::$api->transformModelEdit( self::$res, [], $result );

		$expected = new \stdClass();
		$expected->error = [];

		$this->assertEquals( $expected, $result );
	}

	function testTransformModelDelete()
	{
		$result = true;

		self::$api->transformModelDelete( self::$res, [], $result );

		$expected = new \stdClass();
		$expected->success = true;

		$this->assertEquals( $expected, $result );
	}

	function testTransformModelDeleteFail()
	{
		$result = false;

		self::$api->transformModelDelete( self::$res, [], $result );

		$expected = new \stdClass();
		$expected->error = [];

		$this->assertEquals( $expected, $result );
	}

	function testTansformOutputJson()
	{
		$result = new \stdClass();
		$result->answer = 42;

		self::$api->transformOutputJson( self::$res, [], $result );

		$this->assertEquals( '{"answer":42}', self::$res->getBody() );
	}
}