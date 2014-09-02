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

use App;
use app\api\libs\Api;
use app\api\libs\ApiRoute;

class Controller
{
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

    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    public function create($req, $res)
    {
        $api = new Api( $this->app );

        $route = new ApiRoute(
            [ [ $api, 'parseFetchModelFromParams' ],
                [ $api, 'parseRequireApiScaffolding' ],
                [ $api, 'parseRequireJson' ],
                [ $api, 'parseRequireCreatePermission' ],
                [ $api, 'parseModelCreateParameters' ] ],
            [ $api, 'queryModelCreate' ],
            [ [ $api, 'transformModelCreate' ],
                [ $api, 'transformOutputJson' ] ] );

        $route->execute( $req, $res );
    }

    public function findAll($req, $res)
    {
        $api = new Api( $this->app );

        $route = new ApiRoute(
            [
                function ($req, $res, &$query) use ($api) {
                    // if the model could not be determined, then it might
                    // be the case that the model is actually a model id for
                    // a module with only 1 model or a defaultModel set
                    if ( !$api->parseFetchModelFromParams( $req, $res, $query ) ) {
                        if ( $req->params( 'model' ) ) {
                            $req->setParams( [
                                'model' => false,
                                'id' => $req->params( 'model' ) ] );
                            $this->findOne( $req, $res );
                        }

                        return false;
                    }

                    return true;
                },
                [ $api, 'parseRequireApiScaffolding' ],
                [ $api, 'parseRequireJson' ],
                [ $api, 'parseRequireFindPermission' ],
                [ $api, 'parseModelFindAllParameters' ] ],
            [ $api, 'queryModelFindAll' ],
            [
                [ $api, 'transformModelFindAll' ],
                [ $api, 'transformPaginate' ],
                [ $api, 'transformOutputJson' ] ] );

        $route->execute( $req, $res );
    }

    public function findOne($req, $res)
    {
        $api = new Api( $this->app );

        $route = new ApiRoute(
            [ [ $api, 'parseFetchModelFromParams' ],
                [ $api, 'parseRequireApiScaffolding' ],
                [ $api, 'parseRequireJson' ],
                [ $api, 'parseModelFindOneParameters' ] ],
            [ $api, 'queryModelFindOne' ],
            [ [ $api, 'transformModelFindOne' ],
                [ $api, 'transformModelToArray' ],
                [ $api, 'transformOutputJson' ] ] );

        $route->execute( $req, $res );
    }

    public function edit($req, $res)
    {
        $api = new Api( $this->app );

        $route = new ApiRoute(
            [ [ $api, 'parseFetchModelFromParams' ],
                [ $api, 'parseRequireApiScaffolding' ],
                [ $api, 'parseRequireJson' ],
                [ $api, 'parseModelEditParameters' ] ],
            [ $api, 'queryModelEdit' ],
            [ [ $api, 'transformModelEdit' ],
                [ $api, 'transformOutputJson' ] ] );

        $route->execute( $req, $res );
    }

    public function delete($req, $res)
    {
        $api = new Api( $this->app );

        $route = new ApiRoute(
            [ [ $api, 'parseFetchModelFromParams' ],
                [ $api, 'parseRequireApiScaffolding' ],
                [ $api, 'parseRequireJson' ],
                [ $api, 'parseModelDeleteParameters' ] ],
            [ $api, 'queryModelDelete' ],
            [ [ $api, 'transformModelDelete' ],
                [ $api, 'transformOutputJson' ] ] );

        $route->execute( $req, $res );
    }
}
