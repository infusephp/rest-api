<?php

namespace app\api\libs;

use infuse\Inflector;
use infuse\Request;
use infuse\Response;
use infuse\Utility as U;

use App;

class Api
{
    use \InjectApp;

    ///////////////////////////////
    // PARSE METHODS
    ///////////////////////////////

    public function parseFetchModelFromParams(ApiRoute $route)
    {
        $query = $route->getQueryParams();
        if (isset($query['module']) && isset($query['model']))
            return true;

        $req = $route->getRequest();

        $module = $req->params('module');
        $model = $req->params('model');

        // instantiate the controller
        $controller = '\\app\\' . $module . '\\Controller';
        if (!class_exists($controller)) {
            $route->getResponse()->setCode(404);

            return false;
        }

        $controllerObj = new $controller();
        if (method_exists($controllerObj, 'injectApp'))
            $controllerObj->injectApp($this->app);

        // TODO this is an inefficient function, needs refactor

        // fetch all available models from the controller
        $modelsInfo = $this->models($controllerObj);

        // look for a default model
        if (!$model) {
            // when there is only one choice, use it
            if (count($modelsInfo) == 1)
                $model = array_keys($modelsInfo)[0];
            else
                $model = U::array_value($controller::$properties, 'defaultModel');
        }

        // convert the route name to the pluralized name
        $modelName = Inflector::singularize(Inflector::camelize($model));

        // attempt to fetch the model info
        $modelInfo = U::array_value($modelsInfo, $modelName);

        if (!$modelInfo)
            return false;

        $route->addQueryParams([
            'model' => $modelInfo['class_name'],
            'module' => $module]);

        return true;
    }

    public function parseRequireApiScaffolding(ApiRoute $route)
    {
        // check if api scaffolding is enabled on the model
        if (!property_exists($route->getQueryParams('model'), 'scaffoldApi')) {
            $route->getResponse()->setCode(404);

            return false;
        }

        return true;
    }

    public function parseRequireJson(ApiRoute $route)
    {
        if (!$route->getRequest()->isJson()) {
            $route->getResponse()->setCode(415);

            return false;
        }

        return true;
    }

    public function parseRequireFindPermission(ApiRoute $route)
    {
        return $this->require_permission('find', $route);
    }

    public function parseRequireCreatePermission(ApiRoute $route)
    {
        return $this->require_permission('create', $route);
    }

    public function parseModelCreateParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $route->addQueryParams([
            'properties' => $req->request(),
            'expand' => (array) $req->query('expand')]);

        return true;
    }

    public function parseModelFindAllParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        // start
        $start = $req->query('start');
        if( $start < 0 || !is_numeric( $start ) )
            $start = 0;

        // limit
        $limit = $req->query('limit');
        if ($limit <= 0 || $limit > 1000)
            $limit = 100;

        $route->addQueryParams([
            'start' => $start,
            'limit' => $limit,
            'sort' => $req->query('sort'),
            'search' => $req->query('search'),
            'where' => (array) $req->query('filter'),
            'expand' => (array) $req->query('expand')]);

        return true;
    }

    public function parseModelFindOneParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $route->addQueryParams([
            'model_id' => $req->params('id'),
            'expand' => (array) $req->query('expand'),
            'include' => (array) $req->query('include')]);

        return true;
    }

    public function parseModelEditParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $route->addQueryParams([
            'model_id' => $req->params('id'),
            'properties' => $req->request()]);

        return true;
    }

    public function parseModelDeleteParameters(ApiRoute $route)
    {
        $route->addQueryParams(['model_id' => $route->getRequest()->params('id')]);

        return true;
    }

    ///////////////////////////////
    // QUERY METHODS
    ///////////////////////////////

    public function queryModelCreate(ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');
        $model = new $modelClass();
        if($model->create($route->getQueryParams('properties')))

            return $model;

        return false;
    }

    public function queryModelFindAll(ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');

        return $modelClass::find($route->getQueryParams());
    }

    public function queryModelFindOne(ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');

        return new $modelClass($route->getQueryParams('model_id'));
    }

    public function queryModelEdit(ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');

        $modelObj = new $modelClass($route->getQueryParams('model_id'));

        return $modelObj->set($route->getQueryParams('properties'));
    }

    public function queryModelDelete(ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');

        $modelObj = new $modelClass($route->getQueryParams('model_id'));

        return $modelObj->delete();
    }

    ///////////////////////////////
    // TRANSFORM METHODS
    ///////////////////////////////

    public function transformModelCreate(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result) {
            $modelClass = $route->getQueryParams('model');
            $modelInfo = $modelClass::metadata();
            $modelRouteName = $modelInfo['singular_key'];
            $response->$modelRouteName = $result->toArray([], [], $route->getQueryParams('expand'));
            $response->success = true;
            $route->getResponse()->setCode(201);
        } else {
            $response->error = $this->app['errors']->messages();
        }

        $result = $response;
    }

    public function transformModelFindAll(&$result, ApiRoute $route)
    {
        $response = new \stdClass();
        $modelClass = $route->getQueryParams('model');
        $modelInfo = $modelClass::metadata();
        $modelRouteName = $modelInfo['plural_key'];
        $response->$modelRouteName = [];

        foreach ($result['models'] as $m)
            array_push($response->$modelRouteName, $m->toArray([], [], $route->getQueryParams('expand')));

        $response->filtered_count = $result['count'];

        $result = $response;
    }

    public function transformPaginate(&$result, ApiRoute $route)
    {
        $query = $route->getQueryParams();
        $modelClass = $query['model'];
        $total = $modelClass::totalRecords($query['where']);
        $page = $query['start'] / $query['limit'] + 1;
        $page_count = max(1, ceil($result->filtered_count / $query['limit']));

        $result->page = $page;
        $result->per_page = $query['limit'];
        $result->page_count = $page_count;
        $result->total_count = $total;

        // links
        $modelInfo = $modelClass::metadata();
        $routeBase = '/' . $query['module'] . '/' . $modelInfo['plural_key'];
        $base = $routeBase . "?sort={$query['sort']}&limit={$query['limit']}";
        $last = ($page_count-1) * $query['limit'];
        $result->links = [
            'self' => "$base&start={$query['start']}",
            'first' => "$base&start=0",
            'last' => "$base&start=$last",
        ];
        if ($page > 1)
            $result->links['previous'] = "$base&start=" . ($page-2) * $query['limit'];
        if( $page < $page_count )
            $result->links['next'] = "$base&start=" . ($page) * $query['limit'];
    }

    public function transformModelFindOne(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        // does the model exist?
        if ( !$modelObj->exists() ) {
            $result = [ 'error' => 'not_found' ];
            $route->getResponse()->setCode( 404 );

            return;
        }

        // can the model be viewed?
        if (!$modelObj->can('view', $this->app['user'])) {
            $result = ['error' => 'no_permission'];
            $route->getResponse()->setCode(403);

            return;
        }
    }

    public function transformModelToArray(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        $modelClass = $route->getQueryParams('model');
        if ($modelObj instanceof $modelClass) {
            $modelInfo = $modelObj::metadata();
            $result = [
                $modelInfo['singular_key'] => $modelObj->toArray(
                    [], // exclude
                    $route->getQueryParams('include'),
                    $route->getQueryParams('expand'))];
        }
    }

    public function transformModelEdit(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result)
            $response->success = true;
        else {
            $errorStack = $this->app['errors'];
            $response->error = $errorStack->messages();

            foreach ($errorStack->errors() as $error) {
                if ($error['error'] == 'no_permission')
                    $route->getResponse()->setCode(403);
            }
        }

        $result = $response;
    }

    public function transformModelDelete(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result)
            $response->success = true;
        else {
            $errorStack = $this->app['errors'];
            $response->error = $errorStack->messages();

            foreach ($errorStack->errors() as $error) {
                if ($error['error'] == 'no_permission')
                    $route->getResponse()->setCode( 403 );
            }
        }

        $result = $response;
    }

    public function transformOutputJson(&$result, ApiRoute $route)
    {
        $route->getResponse()->json($result);
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
        $module = $this->name($controller);

        $models = [];

        foreach ((array) U::array_value($properties, 'models') as $model) {
            $modelClassName = '\\app\\' . $module . '\\models\\' . $model;

            $models[$model] = $modelClassName::metadata();
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
        $parts = explode('\\', get_class($controller));

        return $parts[1];
    }

    /**
     * Checks for the specified permission on a model. Returns 403 if it fails
     *
     * @param string   $permission
     * @param ApiRoute $route
     *
     * @return boolean
     */
    private function require_permission($permission, ApiRoute $route)
    {
        $modelClass = $route->getQueryParams('model');
        $modelObj = new $modelClass();

        if (!$modelObj->can($permission, $this->app['user'])) {
            $route->getResponse()->json(['error' => 'no_permission'])
                ->setCode(403);

            return false;
        }

        return true;
    }
}