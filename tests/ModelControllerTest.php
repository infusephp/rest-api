<?php

namespace Infuse\RestApi\Tests;

use Infuse\Request;
use Infuse\Response;
use Infuse\RestApi\ModelController;
use Infuse\RestApi\Serializer\ChainedSerializer;
use Infuse\RestApi\Serializer\JsonSerializer;
use Infuse\RestApi\Serializer\ModelSerializer;
use Infuse\Test;
use Mockery\Adapter\Phpunit\MockeryTestCase;

class ModelControllerTest extends MockeryTestCase
{
    public function testCreate()
    {
        $controller = $this->getController();

        $req = new Request();
        $res = new Response();

        $controller->create($req, $res);
    }

    public function testFindAll()
    {
        $controller = $this->getController();

        $req = new Request();
        $res = new Response();

        $controller->findAll($req, $res);
    }

    public function testRetrieve()
    {
        $controller = $this->getController();

        $req = new Request();
        $res = new Response();

        $controller->retrieve($req, $res);
    }

    public function testEdit()
    {
        $controller = $this->getController();

        $req = new Request();
        $res = new Response();

        $controller->edit($req, $res);
    }

    public function testDelete()
    {
        $controller = $this->getController();

        $req = new Request();
        $res = new Response();

        $controller->delete($req, $res);
    }

    public function testGetSerializer()
    {
        $controller = $this->getController();

        $serializer = $controller->getSerializer(new Request());
        $this->assertInstanceOf(ChainedSerializer::class, $serializer);
        $serializers = $serializer->getSerializers();
        $this->assertCount(2, $serializers);
        $this->assertInstanceOf(ModelSerializer::class, $serializers[0]);
        $this->assertInstanceOf(JsonSerializer::class, $serializers[1]);
    }

    public function getController()
    {
        $controller = new ModelController();
        $controller->setApp(Test::$app);

        return $controller;
    }
}
