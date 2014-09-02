<?php

use infuse\Request;
use infuse\Response;

use app\api\libs\Api;

class ApiTest extends \PHPUnit_Framework_TestCase
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

    public function testParseFetchModel()
    {
        $this->markTestIncomplete();
    }

    public function testParseRequireApiScaffolding()
    {
        $model = new \stdClass();
        $model->scaffoldApi = true;

        $query = [ 'model' => $model ];

        $this->assertTrue( self::$api->parseRequireApiScaffolding( self::$req, self::$res, $query ) );
    }

    public function testParseRequireApiScaffoldingFail()
    {
        $query = [ 'model' => new \stdClass() ];

        $this->assertFalse( self::$api->parseRequireApiScaffolding( self::$req, self::$res, $query ) );
    }

    public function testParseRequireJson()
    {
        $req = new Request( null, null, null, null, [ 'HTTP_ACCEPT' => 'application/json' ] );
        $query = [];

        $this->assertTrue( self::$api->parseRequireJson( $req, self::$res, $query ) );
    }

    public function testParseRequireJsonFail()
    {
        $query = [];
        $this->assertFalse( self::$api->parseRequireJson( self::$req, self::$res, $query ) );
    }

    public function testParseRequireFindPermission()
    {
        $this->markTestIncomplete();
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
        $test = [ 'test' => 'hello' ];
        $req = new Request( [ 'expand' => 'invoice' ], $test );
        $query = [];

        $this->assertTrue( self::$api->parseModelCreateParameters( $req, self::$res, $query ) );

        $this->assertEquals( $test, $query[ 'properties' ] );
        $this->assertEquals( [ 'invoice' ], $query[ 'expand' ] );
    }

    public function testParseModelFindAllParameters()
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

    public function testParseModelFindOneParameters()
    {
        self::$req->setParams( [ 'id' => 101 ] );
        $query = [];

        $this->assertTrue( self::$api->parseModelFindOneParameters( self::$req, self::$res, $query ) );

        $expected = [
            'model_id' => 101,
            'expand' => [] ];
        $this->assertEquals( $expected, $query );
    }

    public function testParseModelEditParameters()
    {
        $test = [ 'test' => 'hello' ];
        $req = new Request( null, $test );
        $req->setParams( [ 'id' => 101 ] );
        $query = [];

        $this->assertTrue( self::$api->parseModelEditParameters( $req, self::$res, $query ) );
        $this->assertEquals( 101, $query[ 'model_id' ] );
        $this->assertEquals( $test, $query[ 'properties' ] );
    }

    public function testParseModelDeleteParameters()
    {
        self::$req->setParams( [ 'id' => 102 ] );
        $query = [];

        $this->assertTrue( self::$api->parseModelDeleteParameters( self::$req, self::$res, $query ) );
        $this->assertEquals( 102, $query[ 'model_id' ] );
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
        $this->markTestIncomplete();
    }

    public function testTransformModelFindOne()
    {
        $this->markTestIncomplete();
    }

    public function testTransformModelEdit()
    {
        $result = true;

        self::$api->transformModelEdit( self::$res, [], $result );

        $expected = new \stdClass();
        $expected->success = true;

        $this->assertEquals( $expected, $result );
    }

    public function testTransformModelEditFail()
    {
        $result = false;

        self::$api->transformModelEdit( self::$res, [], $result );

        $expected = new \stdClass();
        $expected->error = [];

        $this->assertEquals( $expected, $result );
    }

    public function testTransformModelDelete()
    {
        $result = true;

        self::$api->transformModelDelete( self::$res, [], $result );

        $expected = new \stdClass();
        $expected->success = true;

        $this->assertEquals( $expected, $result );
    }

    public function testTransformModelDeleteFail()
    {
        $result = false;

        self::$api->transformModelDelete( self::$res, [], $result );

        $expected = new \stdClass();
        $expected->error = [];

        $this->assertEquals( $expected, $result );
    }

    public function testTansformOutputJson()
    {
        $result = new \stdClass();
        $result->answer = 42;

        self::$api->transformOutputJson( self::$res, [], $result );

        $this->assertEquals( '{"answer":42}', self::$res->getBody() );
    }
}
