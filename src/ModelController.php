<?php

namespace App\RestApi;

use App\RestApi\Route\CreateModelRoute;
use App\RestApi\Route\DeleteModelRoute;
use App\RestApi\Route\EditModelRoute;
use App\RestApi\Route\ListModelsRoute;
use App\RestApi\Route\RetrieveModelRoute;
use App\RestApi\Serializer\ChainedSerializer;
use App\RestApi\Serializer\JsonSerializer;
use App\RestApi\Serializer\ModelSerializer;
use Infuse\HasApp;

class ModelController
{
    use HasApp;

    public function create($req, $res)
    {
        $route = new CreateModelRoute($req, $res);
        $route->injectApp($this->app)
              ->setSerializer($this->getSerializer($req))
              ->run();
    }

    public function findAll($req, $res)
    {
        $route = new ListModelsRoute($req, $res);
        $route->injectApp($this->app)
              ->setSerializer($this->getSerializer($req))
              ->run();
    }

    public function retrieve($req, $res)
    {
        $route = new RetrieveModelRoute($req, $res);
        $route->injectApp($this->app)
              ->setSerializer($this->getSerializer($req))
              ->run();
    }

    public function edit($req, $res)
    {
        $route = new EditModelRoute($req, $res);
        $route->injectApp($this->app)
              ->setSerializer($this->getSerializer($req))
              ->run();
    }

    public function delete($req, $res)
    {
        $route = new DeleteModelRoute($req, $res);
        $route->injectApp($this->app)
              ->setSerializer($this->getSerializer($req))
              ->run();
    }

    public function getSerializer($req)
    {
        $modelSerializer = new ModelSerializer($req);
        $jsonSerializer = new JsonSerializer($req);

        $serializer = new ChainedSerializer();
        $serializer->add($modelSerializer)
                   ->add($jsonSerializer);

        return $serializer;
    }
}
