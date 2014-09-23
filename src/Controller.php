<?php

/**
 * @package infuse\framework
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace app\api;

use app\api\libs\ApiRoute;

class Controller
{
    use \InjectApp;

    public static $properties = [
        'routes' => [
            'post /api/:module' => 'create',
            'post /api/:module/:model' => 'create',
            'get /api/:module' => 'findAll',
            'get /api/:module/:model' => 'findAll',
            'get /api/:module/:model/:id' => 'findOne',
            'put /api/:module/:id' => 'edit',
            'put /api/:module/:model/:id' => 'edit',
            'delete /api/:module/:id' => 'delete',
            'delete /api/:module/:model/:id' => 'delete',
        ],
    ];

    public function create($req, $res)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireJson',
                'parseRequireCreatePermission',
                'parseModelCreateParameters' ])
              ->addQueryStep('queryModelCreate')
              ->addTransformSteps([
                'transformModelCreate',
                'transformOutputJson']);

        if (!$route->execute($req, $res, $this->app) && $res->getCode() == 200)
            return SKIP_ROUTE;
    }

    public function findAll($req, $res)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireJson',
                'parseRequireFindPermission',
                'parseModelFindAllParameters'])
              ->addQueryStep('queryModelFindAll')
              ->addTransformSteps([
                'transformModelFindAll',
                'transformPaginate',
                'transformOutputJson']);

        if (!$route->execute($req, $res, $this->app) && $res->getCode() == 200) {
            // if the model could not be determined, then it might
            // be the case that the model is actually a model id for
            // a module with only 1 model or a defaultModel set
            if ($req->params('model')) {
                $req->setParams( [
                    'model' => false,
                    'id' => $req->params('model') ] );

                return $this->findOne($req, $res);
            }

            return SKIP_ROUTE;
        }
    }

    public function findOne($req, $res)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireJson',
                'parseModelFindOneParameters' ])
              ->addQueryStep('queryModelFindOne')
              ->addTransformSteps([
                'transformModelFindOne',
                'transformModelToArray',
                'transformOutputJson']);

        if (!$route->execute($req, $res, $this->app) && $res->getCode() == 200)
            return SKIP_ROUTE;
    }

    public function edit($req, $res)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireJson',
                'parseModelEditParameters' ])
              ->addQueryStep('queryModelEdit')
              ->addTransformSteps([
                'transformModelEdit',
                'transformOutputJson']);

        if (!$route->execute($req, $res, $this->app) && $res->getCode() == 200)
            return SKIP_ROUTE;
    }

    public function delete($req, $res)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireJson',
                'parseModelDeleteParameters' ])
              ->addQueryStep('queryModelDelete')
              ->addTransformSteps([
                'transformModelDelete',
                'transformOutputJson']);

        if (!$route->execute($req, $res, $this->app) && $res->getCode() == 200)
            return SKIP_ROUTE;
    }
}
