<?php

namespace app\api\libs;

use infuse\Inflector;
use infuse\Request;
use infuse\Response;
use infuse\Util;

use App;

class Api
{
    private $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    ///////////////////////////////
    // PARSE METHODS
    ///////////////////////////////

    public function parseFetchModelFromParams(Request $req, Response $res, array &$query)
    {
        $module = $req->params( 'module' );
        $model = $req->params( 'model' );

        // instantiate the controller
        $controller = '\\app\\' . $module . '\\Controller';
        if ( !class_exists( $controller ) ) {
            $res->setCode( 404 );

            return false;
        }

        $controllerObj = new $controller;
        if (method_exists($controllerObj, 'injectApp'))
            $controllerObj->injectApp($this->app);

        // TODO this is an inefficient function, needs refactor

        // fetch all available models from the controller
        $modelsInfo = $this->models( $controllerObj );

        // look for a default model
        if (!$model) {
            // when there is only one choice, use it
            if( count( $modelsInfo ) == 1 )
                $model = array_keys( $modelsInfo )[ 0 ];
            else
                $model = Util::array_value( $controller::$properties, 'defaultModel' );
        }

        // convert the route name to the pluralized name
        $modelName = Inflector::singularize( Inflector::camelize( $model ) );

        // attempt to fetch the model info
        $modelInfo = Util::array_value( $modelsInfo, $modelName );

        if( !$modelInfo )

            return false;

        $query[ 'model' ] = $modelInfo[ 'class_name' ];
        $query[ 'module' ] = $module;

        return true;
    }

    public function parseRequireApiScaffolding(Request $req, Response $res, array &$query)
    {
        // check if api scaffolding is enabled on the model
        if ( !property_exists( $query[ 'model' ], 'scaffoldApi' ) ) {
            $res->setCode( 404 );

            return false;
        }

        return true;
    }

    public function parseRequireJson(Request $req, Response $res, array &$query)
    {
        if ( !$req->isJson() ) {
            $res->setCode( 415 );

            return false;
        }

        return true;
    }

    public function parseRequireFindPermission(Request $req, Response $res, array &$query)
    {
        return $this->require_permission( $req, $res, $query, 'find' );
    }

    public function parseRequireCreatePermission(Request $req, Response $res, array &$query)
    {
        return $this->require_permission( $req, $res, $query, 'create' );
    }

    public function parseModelCreateParameters(Request $req, Response $res, array &$query)
    {
        $query[ 'properties' ] = $req->request();

        $query[ 'expand' ] = (array) $req->query( 'expand' );

        return true;
    }

    public function parseModelFindAllParameters(Request $req, Response $res, array &$query)
    {
        // start
        $start = $req->query( 'start' );
        if( $start < 0 || !is_numeric( $start ) )
            $start = 0;
        $query[ 'start' ] = $start;

        // limit
        $limit = $req->query( 'limit' );
        if( $limit <= 0 || $limit > 1000 )
            $limit = 100;
        $query[ 'limit' ] = $limit;

        // sort
        $query[ 'sort' ] = $req->query( 'sort' );

        // search
        $query[ 'search' ] = $req->query( 'search' );

        // filter
        $query[ 'where' ] = (array) $req->query( 'filter' );

        // expand
        $query[ 'expand' ] = (array) $req->query( 'expand' );

        return true;
    }

    public function parseModelFindOneParameters(Request $req, Response $res, array &$query)
    {
        $query[ 'model_id' ] = $req->params( 'id' );

        $query[ 'expand' ] = (array) $req->query( 'expand' );

        return true;
    }

    public function parseModelEditParameters(Request $req, Response $res, array &$query)
    {
        $query[ 'model_id' ] = $req->params( 'id' );
        $query[ 'properties' ] = $req->request();

        return true;
    }

    public function parseModelDeleteParameters(Request $req, Response $res, array &$query)
    {
        $query[ 'model_id' ] = $req->params( 'id' );

        return true;
    }

    ///////////////////////////////
    // QUERY METHODS
    ///////////////////////////////

    public function queryModelCreate(array $query)
    {
        $modelClass = $query[ 'model' ];
        $model = new $modelClass();
        if( $model->create( $query[ 'properties' ] ) )

            return $model;

        return false;
    }

    public function queryModelFindAll(array $query)
    {
        $modelClass = $query[ 'model' ];

        return $modelClass::find( $query );
    }

    public function queryModelFindOne(array $query)
    {
        $modelClass = $query[ 'model' ];

        return new $modelClass( $query[ 'model_id' ] );
    }

    public function queryModelEdit(array $query)
    {
        $modelClass = $query[ 'model' ];

        $modelObj = new $modelClass( $query[ 'model_id' ] );

        if( !$modelObj->can( 'edit', $this->app[ 'user' ] ) )

            return false;

        return $modelObj->set( $query[ 'properties' ] );
    }

    public function queryModelDelete(array $query)
    {
        $modelClass = $query[ 'model' ];

        $modelObj = new $modelClass( $query[ 'model_id' ] );

        if( !$modelObj->can( 'delete', $this->app[ 'user' ] ) )

            return false;

        return $modelObj->delete();
    }

    ///////////////////////////////
    // TRANSFORM METHODS
    ///////////////////////////////

    public function transformModelCreate(Response $res, array $query, &$result)
    {
        $response = new \stdClass();

        if ($result) {
            $modelInfo = $query[ 'model' ]::metadata();
            $modelRouteName = $modelInfo[ 'singular_key' ];
            $response->$modelRouteName = $result->toArray( [], [], $query[ 'expand' ] );
            $response->success = true;
            $res->setCode( 201 );
        } else {
            $response->error = $this->app[ 'errors' ]->messages();
        }

        $result = $response;
    }

    public function transformModelFindAll(Response $res, array $query, &$result)
    {
        $response = new \stdClass();
        $modelInfo = $query[ 'model' ]::metadata();
        $modelRouteName = $modelInfo[ 'plural_key' ];
        $response->$modelRouteName = [];

        foreach( $result[ 'models' ] as $m )
            array_push( $response->$modelRouteName, $m->toArray( [], [], $query[ 'expand' ] ) );

        $response->filtered_count = $result[ 'count' ];

        $result = $response;
    }

    public function transformPaginate(Response $res, array $query, &$result)
    {
        $modelClass = $query[ 'model' ];
        $total = $modelClass::totalRecords( $query[ 'where' ] );
        $page = $query[ 'start' ] / $query[ 'limit' ] + 1;
        $page_count = max( 1, ceil( $result->filtered_count / $query[ 'limit' ] ) );

        $result->page = $page;
        $result->per_page = $query[ 'limit' ];
        $result->page_count = $page_count;
        $result->total_count = $total;

        // links
        $modelInfo = $modelClass::metadata();
        $routeBase = '/' . $query[ 'module' ] . '/' . $modelInfo[ 'plural_key' ];
        $base = $routeBase . "?sort={$query['sort']}&limit={$query['limit']}";
        $last = ($page_count-1) * $query[ 'limit' ];
        $result->links = [
            'self' => "$base&start={$query['start']}",
            'first' => "$base&start=0",
            'last' => "$base&start=$last",
        ];
        if( $page > 1 )
            $result->links[ 'previous' ] = "$base&start=" . ($page-2) * $query[ 'limit' ];
        if( $page < $page_count )
            $result->links[ 'next' ] = "$base&start=" . ($page) * $query[ 'limit' ];
    }

    public function transformModelFindOne(Response $res, array $query, &$result)
    {
        $modelObj = $result;

        // does the model exist?
        if ( !$modelObj->exists() ) {
            $result = [ 'error' => 'not_found' ];
            $res->setCode( 404 );

            return;
        }

        // can the model be viewed?
        if ( !$modelObj->can( 'view', $this->app[ 'user' ] ) ) {
            $result = [ 'error' => 'no_permission' ];
            $res->setCode( 403 );

            return;
        }
    }

    public function transformModelToArray(Response $res, array $query, &$result)
    {
        $modelObj = $result;

        if ( ($modelObj instanceof $query[ 'model' ]) ) {
            $modelInfo = $modelObj::metadata();
            $result = [
                $modelInfo[ 'singular_key' ] => $modelObj->toArray( [], [], $query[ 'expand' ] ) ];
        }
    }

    public function transformModelEdit(Response $res, array $query, &$result)
    {
        $response = new \stdClass();

        if( $result )
            $response->success = true;
        else {
            $errorStack = $this->app[ 'errors' ];
            $response->error = $errorStack->messages();

            foreach ( $errorStack->errors() as $error ) {
                if( $error[ 'error' ] == 'no_permission' )
                    $res->setCode( 403 );
            }
        }

        $result = $response;
    }

    public function transformModelDelete(Response $res, array $query, &$result)
    {
        $response = new \stdClass();

        if( $result )
            $response->success = true;
        else {
            $errorStack = $this->app[ 'errors' ];
            $response->error = $errorStack->messages();

            foreach ( $errorStack->errors() as $error ) {
                if( $error[ 'error' ] == 'no_permission' )
                    $res->setCode( 403 );
            }
        }

        $result = $response;
    }

    public function transformOutputJson(Response $res, array $query, &$result)
    {
        $res->setBodyJson( $result );
    }

    ///////////////////////////////
    // PRIVATE METHODS
    ///////////////////////////////

    /**
	 * Fetches the models for a given controller
	 *
	 * @param object $controller
	 *
	 * @return array
	 */
    private function models($controller)
    {
        $properties = $controller::$properties;
        $module = $this->name( $controller );

        $models = [];

        foreach ( (array) Util::array_value( $properties, 'models' ) as $model ) {
            $modelClassName = '\\app\\' . $module . '\\models\\' . $model;

            $models[ $model ] = $modelClassName::metadata();
        }

        return $models;
    }

    /**
	 * Computes the name for a given controller
	 *
	 * @param object $controller
	 *
	 * @return string
	 */
    private function name($controller)
    {
        // compute module name
        $parts = explode( '\\', get_class( $controller ) );

        return $parts[ 1 ];
    }

    private function require_permission(Request $req, Response $res, array &$query, $permission)
    {
        $modelClass = $query[ 'model' ];
        $modelObj = new $modelClass();

        if ( !$modelObj->can( $permission, $this->app[ 'user' ] ) ) {
            $result = [ 'error' => 'no_permission' ];
            $res->setCode( 403 );

            return false;
        }

        return true;
    }
}
