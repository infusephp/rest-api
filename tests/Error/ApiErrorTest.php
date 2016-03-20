<?php

use App\RestApi\Error\ApiError;

class ApiErrorTest extends PHPUnit_Framework_TestCase
{
    public function testGetMessage()
    {
        $error = new ApiError('error');
        $this->assertEquals('error', $error->getMessage());
    }

    public function testGetHttpStatus()
    {
        $error = new ApiError('error', 500);
        $this->assertEquals(500, $error->getHttpStatus());
    }
}
