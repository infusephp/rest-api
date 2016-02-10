<?php

use App\RestApi\Error\Api;

class ApiErrorTest extends PHPUnit_Framework_TestCase
{
    public function testGetMessage()
    {
        $error = new Api('error');
        $this->assertEquals('error', $error->getMessage());
    }

    public function testGetHttpStatus()
    {
        $error = new Api('error', 500);
        $this->assertEquals(500, $error->getHttpStatus());
    }
}
