<?php

namespace app\api\libs;

use ICanBoogie\Inflector;
use infuse\Request;
use infuse\Response;
use infuse\Utility as U;
use App;

class ApiController
{
    use \InjectApp;

    ///////////////////////////////
    // ROUTES
    ///////////////////////////////

    public function create($req, $res, $execute = true)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireCreatePermission',
                'parseModelCreateParameters', ])
              ->addQueryStep('queryModelCreate')
              ->addTransformSteps([
                'transformModelCreate',
                'transformOutputJson', ])
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                return SKIP_ROUTE;
            }
        } else {
            return $route;
        }
    }

    public function findAll($req, $res, $execute = true)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseRouteBase',
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseRequireFindPermission',
                'parseModelFindAllParameters', ])
              ->addQueryStep('queryModelFindAll')
              ->addTransformSteps([
                'transformModelFindAll',
                'transformPaginate',
                'transformOutputJson', ])
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                // if the model could not be determined, then it might
                // be the case that the model is actually a model id for
                // a module with only 1 model or a defaultModel set
                if ($req->params('model')) {
                    $req->setParams([
                        'model' => false,
                        'id' => $req->params('model'), ]);

                    return $this->findOne($req, $res);
                }

                return SKIP_ROUTE;
            }
        } else {
            return $route;
        }
    }

    public function findOne($req, $res, $execute = true)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseModelFindOneParameters', ])
              ->addQueryStep('queryModelFindOne')
              ->addTransformSteps([
                'transformModelFindOne',
                'transformModelToArray',
                'transformOutputJson', ])
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                return SKIP_ROUTE;
            }
        } else {
            return $route;
        }
    }

    public function edit($req, $res, $execute = true)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseModelEditParameters', ])
              ->addQueryStep('queryModelEdit')
              ->addTransformSteps([
                'transformModelEdit',
                'transformOutputJson', ])
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                return SKIP_ROUTE;
            }
        } else {
            return $route;
        }
    }

    public function delete($req, $res, $execute = true)
    {
        $route = new ApiRoute();
        $route->addParseSteps([
                'parseFetchModelFromParams',
                'parseRequireApiScaffolding',
                'parseModelDeleteParameters', ])
              ->addQueryStep('queryModelDelete')
              ->addTransformSteps([
                'transformModelDelete',
                'transformOutputJson', ])
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                return SKIP_ROUTE;
            }
        } else {
            return $route;
        }
    }

    ///////////////////////////////
    // PARSE METHODS
    ///////////////////////////////

    public function parseRouteBase(ApiRoute $route)
    {
        $req = $route->getRequest();
        $route->addQueryParams(['route_base' => $req->basePath().$req->path()]);
    }

    public function parseFetchModelFromParams(ApiRoute $route)
    {
        $query = $route->getQuery();
        if (isset($query['model'])) {
            return true;
        }

        $req = $route->getRequest();

        $module = $req->params('module');
        $model = $req->params('model');

        // instantiate the controller
        $controller = '\\app\\'.$module.'\\Controller';
        if (!class_exists($controller)) {
            $route->getResponse()->setCode(404);

            return false;
        }

        $controllerObj = new $controller();
        if (method_exists($controllerObj, 'injectApp')) {
            $controllerObj->injectApp($this->app);
        }

        // TODO this is an inefficient function, needs refactor

        // fetch all available models from the controller
        $modelsInfo = $this->models($controllerObj, $module);

        // look for a default model
        if (!$model) {
            // when there is only one choice, use it
            if (count($modelsInfo) == 1) {
                $model = array_keys($modelsInfo)[0];
            } else {
                $model = U::array_value($controller::$properties, 'defaultModel');
            }
        }

        // convert the route name to the pluralized name
        $inflector = Inflector::get();
        $modelName = $inflector->singularize($inflector->camelize($model));

        // attempt to fetch the model info
        $modelInfo = U::array_value($modelsInfo, $modelName);

        if (!$modelInfo) {
            return false;
        }

        $route->addQueryParams([
            'model' => $modelInfo['class_name'], ]);
    }

    public function parseRequireApiScaffolding(ApiRoute $route)
    {
        // check if api scaffolding is enabled on the model
        if (!property_exists($route->getQuery('model'), 'scaffoldApi')) {
            $route->getResponse()->setCode(404);

            return false;
        }
    }

    public function parseRequireJson(ApiRoute $route)
    {
        if (!$route->getRequest()->isJson()) {
            $route->getResponse()->setCode(415);

            return false;
        }
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
            'expand' => (array) $req->query('expand'),
            'exclude' => (array) $req->query('exclude'),
            'include' => (array) $req->query('include'), ]);
    }

    public function parseModelFindAllParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        // start
        $start = $req->query('start');
        if ($start < 0 || !is_numeric($start)) {
            $start = 0;
        }

        // limit
        $limit = $req->query('limit');
        if ($limit <= 0 || $limit > 1000) {
            $limit = 100;
        }

        $filter = [];
        foreach ((array) $req->query('filter') as $key => $value) {
            if (is_numeric($key) || !preg_match('/^[A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $filter[$key] = $value;
        }

        $route->addQueryParams(array_replace([
            'start' => $start,
            'limit' => $limit,
            'sort' => $req->query('sort'),
            'search' => $req->query('search'),
            'where' => $filter,
            'expand' => (array) $req->query('expand'),
            'exclude' => (array) $req->query('exclude'),
            'include' => (array) $req->query('include'), ], $route->getQuery()));
    }

    public function parseModelFindOneParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $route->addQueryParams([
            'model_id' => $req->params('id'),
            'expand' => (array) $req->query('expand'),
            'exclude' => (array) $req->query('exclude'),
            'include' => (array) $req->query('include'), ]);
    }

    public function parseModelEditParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $route->addQueryParams([
            'model_id' => $req->params('id'),
            'properties' => $req->request(), ]);
    }

    public function parseModelDeleteParameters(ApiRoute $route)
    {
        $route->addQueryParams(['model_id' => $route->getRequest()->params('id')]);
    }

    ///////////////////////////////
    // QUERY METHODS
    ///////////////////////////////

    public function queryModelCreate(ApiRoute $route)
    {
        $modelClass = $route->getQuery('model');
        $model = new $modelClass();
        if ($model->create($route->getQuery('properties'))) {
            return $model;
        }

        return false;
    }

    public function queryModelFindAll(ApiRoute $route)
    {
        $modelClass = $route->getQuery('model');

        return $modelClass::find($route->getQuery());
    }

    public function queryModelFindOne(ApiRoute $route)
    {
        $modelClass = $route->getQuery('model');

        return new $modelClass($route->getQuery('model_id'));
    }

    public function queryModelEdit(ApiRoute $route)
    {
        $modelClass = $route->getQuery('model');

        $modelObj = new $modelClass($route->getQuery('model_id'));

        return $modelObj->set($route->getQuery('properties'));
    }

    public function queryModelDelete(ApiRoute $route)
    {
        $modelClass = $route->getQuery('model');

        $modelObj = new $modelClass($route->getQuery('model_id'));

        return $modelObj->delete();
    }

    ///////////////////////////////
    // TRANSFORM METHODS
    ///////////////////////////////

    public function transformModelCreate(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result) {
            $modelClass = $route->getQuery('model');
            $modelInfo = $modelClass::metadata();
            $modelRouteName = $modelInfo['singular_key'];
            $response->$modelRouteName = $result->toArray(
                $route->getQuery('exclude'),
                $route->getQuery('include'),
                $route->getQuery('expand'));
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
        $modelClass = $route->getQuery('model');
        $modelInfo = $modelClass::metadata();
        $modelRouteName = $modelInfo['plural_key'];
        $response->$modelRouteName = [];

        foreach ($result['models'] as $m) {
            array_push($response->$modelRouteName, $m->toArray(
                $route->getQuery('exclude'),
                $route->getQuery('include'),
                $route->getQuery('expand')));
        }

        $response->filtered_count = $result['count'];

        $result = $response;
    }

    public function transformPaginate(&$result, ApiRoute $route)
    {
        $res = $route->getResponse();

        // total count
        $res->setHeader('X-Total-Count', $result->filtered_count);

        $query = $route->getQuery();
        $start = $query['start'];
        $limit = $query['limit'];
        $page = $start / $limit + 1;
        $page_count = max(1, ceil($result->filtered_count / $limit));
        $last = ($page_count-1) * $limit;

        // compute links
        $modelClass = $query['model'];
        $modelInfo = $modelClass::metadata();
        $base = $route->getQuery('route_base').'/'.$modelInfo['plural_key']."?sort={$query['sort']}&limit=$limit";

        // self/first links
        $links = [
            'self' => "$base&start=$start",
            'first' => "$base&start=0",
        ];

        // previous/next links
        if ($page > 1) {
            $links['previous'] = "$base&start=".max(0, ($page-2) * $limit);
        }

        if ($page < $page_count) {
            $links['next'] = "$base&start=".($page) * $limit;
        }

        // last link
        $links['last'] = "$base&start=$last";

        // add links to Link header
        $linkStr = implode(',', array_map(function ($link, $rel) {
            return "<$link>; rel=\"$rel\"";
        }, $links, array_keys($links)));

        $res->setHeader('Link', $linkStr);

        // add pagination metadata to response body
        // TODO deprecated
        $result->page = $page;
        $result->per_page = $limit;
        $result->page_count = $page_count;
        $result->total_count = $modelClass::totalRecords($query['where']);
        $result->links = $links;
    }

    public function transformModelFindOne(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        // does the model exist?
        if (!$modelObj->exists()) {
            $result = [ 'error' => 'not_found' ];
            $route->getResponse()->setCode(404);

            return;
        }

        // can the model be viewed?
        if (!$modelObj->can('view', $this->app['requester'])) {
            $result = ['error' => 'no_permission'];
            $route->getResponse()->setCode(403);

            return;
        }
    }

    public function transformModelToArray(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        $modelClass = $route->getQuery('model');
        if ($modelObj instanceof $modelClass) {
            $modelInfo = $modelObj::metadata();
            $result = [
                $modelInfo['singular_key'] => $modelObj->toArray(
                    $route->getQuery('exclude'),
                    $route->getQuery('include'),
                    $route->getQuery('expand')), ];
        }
    }

    public function transformModelEdit(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result) {
            $response->success = true;
        } else {
            $errorStack = $this->app['errors'];
            $response->error = $errorStack->messages();

            foreach ($errorStack->errors() as $error) {
                if ($error['error'] == 'no_permission') {
                    $route->getResponse()->setCode(403);
                }
            }
        }

        $result = $response;
    }

    public function transformModelDelete(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result) {
            $response->success = true;
        } else {
            $errorStack = $this->app['errors'];
            $response->error = $errorStack->messages();

            foreach ($errorStack->errors() as $error) {
                if ($error['error'] == 'no_permission') {
                    $route->getResponse()->setCode(403);
                }
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
     * @param object $controller module controller
     * @param string $module     module name
     *
     * @return array
     */
    private function models($controller, $module)
    {
        $properties = $controller::$properties;

        $models = [];

        foreach ((array) U::array_value($properties, 'models') as $model) {
            $modelClassName = '\\app\\'.$module.'\\models\\'.$model;

            $models[$model] = $modelClassName::metadata();
        }

        return $models;
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
        $modelClass = $route->getQuery('model');
        $modelObj = new $modelClass();

        if (!$modelObj->can($permission, $this->app['requester'])) {
            $route->getResponse()->json(['error' => 'no_permission'])
                ->setCode(403);

            return false;
        }

        return true;
    }
}
