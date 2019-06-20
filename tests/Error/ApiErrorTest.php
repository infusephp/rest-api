<?php

namespace Infuse\RestApi\Tests\Error;

use Infuse\RestApi\Error\ApiError;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ApiErrorTest extends MockeryTestCase
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
