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
use Infuse\Request;
use Infuse\Response;

class ModelController
{
    use HasApp;

    public function create($req, $res)
    {
        $this->getCreateRoute($req, $res)->run();
    }

    public function findAll($req, $res)
    {
        $this->getListRoute($req, $res)->run();
    }

    public function retrieve($req, $res)
    {
        $this->getRetrieveRoute($req, $res)->run();
    }

    public function edit($req, $res)
    {
        $this->getEditRoute($req, $res)->run();
    }

    public function delete($req, $res)
    {
        $this->getDeleteRoute($req, $res)->run();
    }

    /**
     * Builds a create route object.
     *
     * @param Request  $req
     * @param Response $res
     *
     * @return CreateModelRoute
     */
    protected function getCreateRoute(Request $req, Response $res)
    {
        $route = new CreateModelRoute($req, $res);
        $route->setApp($this->app)
              ->setSerializer($this->getSerializer($req));

        return $route;
    }

    /**
     * Builds a list route object.
     *
     * @param Request  $req
     * @param Response $res
     *
     * @return ListModelsRoute
     */
    protected function getListRoute(Request $req, Response $res)
    {
        $route = new ListModelsRoute($req, $res);
        $route->setApp($this->app)
              ->setSerializer($this->getSerializer($req));

        return $route;
    }

    /**
     * Builds a retrieve route object.
     *
     * @param Request  $req
     * @param Response $res
     *
     * @return RetrieveModelRoute
     */
    protected function getRetrieveRoute(Request $req, Response $res)
    {
        $route = new RetrieveModelRoute($req, $res);
        $route->setApp($this->app)
              ->setSerializer($this->getSerializer($req));

        return $route;
    }

    /**
     * Builds an edit route object.
     *
     * @param Request  $req
     * @param Response $res
     *
     * @return EditModelRoute
     */
    protected function getEditRoute(Request $req, Response $res)
    {
        $route = new EditModelRoute($req, $res);
        $route->setApp($this->app)
              ->setSerializer($this->getSerializer($req));

        return $route;
    }

    /**
     * Builds a delete route object.
     *
     * @param Request  $req
     * @param Response $res
     *
     * @return DeleteModelRoute
     */
    protected function getDeleteRoute(Request $req, Response $res)
    {
        $route = new DeleteModelRoute($req, $res);
        $route->setApp($this->app)
              ->setSerializer($this->getSerializer($req));

        return $route;
    }

    /**
     * Builds a serializer object.
     *
     * @param Request $req
     *
     * @return ChainedSerializer
     */
    public function getSerializer(Request $req)
    {
        $modelSerializer = new ModelSerializer($req);
        $jsonSerializer = new JsonSerializer($req);

        $serializer = new ChainedSerializer();
        $serializer->add($modelSerializer)
                   ->add($jsonSerializer);

        return $serializer;
    }
}
