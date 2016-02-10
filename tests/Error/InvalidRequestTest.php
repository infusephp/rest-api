<?php

use App\RestApi\Error\InvalidRequest;

class InvalidRequestTest extends PHPUnit_Framework_TestCase
{
    public function testGetMessage()
    {
        $error = new InvalidRequest('error');
        $this->assertEquals('error', $error->getMessage());
    }

    public function testGetHttpStatus()
    {
        $error = new InvalidRequest('error', 401);
        $this->assertEquals(401, $error->getHttpStatus());
    }

    public function testGetParam()
    {
        $error = new InvalidRequest('error', 401, 'username');
        $this->assertEquals('username', $error->getParam());
    }
}
