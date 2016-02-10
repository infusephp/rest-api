<?php

use App\RestApi\ModelController;
use Infuse\Request;
use Infuse\Response;
use Infuse\Test;

class ModelControllerTest extends PHPUnit_Framework_TestCase
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
        $this->assertInstanceOf('App\RestApi\Serializer\ChainedSerializer', $serializer);
        $serializers = $serializer->getSerializers();
        $this->assertCount(2, $serializers);
        $this->assertInstanceOf('App\RestApi\Serializer\ModelSerializer', $serializers[0]);
        $this->assertInstanceOf('App\RestApi\Serializer\JsonSerializer', $serializers[1]);
    }

    public function getController()
    {
        $controller = new ModelController();
        $controller->injectApp(Test::$app);

        return $controller;
    }
}
