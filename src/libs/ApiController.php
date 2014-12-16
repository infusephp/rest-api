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

    protected static $apiBase = '/api';
    protected static $pageLimit = 100;

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
                'transformModelDelete', ])
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

        $url = $this->app['config']->get('api.url');
        if (!$url) {
            $url = $this->app['base_url'].substr(static::$apiBase, 1);
        }

        // replace the default API base with a full URL
        $path = $req->path();
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }

        $url =  str_replace(static::$apiBase, $url, $path);

        $route->addQueryParams(['endpoint_url' => $url]);
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

        $exclude = $req->query('exclude');
        if (!is_array($exclude)) {
            $exclude = explode(',', $req->query('exclude'));
        }

        $include = $req->query('include');
        if (!is_array($include)) {
            $include = explode(',', $req->query('include'));
        }

        $expand = $req->query('expand');
        if (!is_array($expand)) {
            $expand = explode(',', $req->query('expand'));
        }

        $route->addQueryParams(array_replace([
            'properties' => $req->request(),
            'exclude' => array_filter($exclude),
            'include' => array_filter($include),
            'expand' => array_filter($expand), ]), $route->getQuery());
    }

    public function parseModelFindAllParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        // calculate limit
        $perPage = static::$pageLimit;

        if ($req->query('per_page')) {
            $perPage = $req->query('per_page');
        // WARNING the `limit` parameter is deprecated
        } elseif ($req->query('limit')) {
            $perPage = $req->query('limit');
        }

        $perPage = max(0, min(1000, (int) $perPage));

        // calculate offset
        $offset = 0;
        $page = 1;

        if ($req->query('page')) {
            $page = $req->query('page');
            $offset = ($page - 1) * $perPage;
        // WARNING the `start` parameter is deprecated
        } elseif ($req->query('start')) {
            $offset = $req->query('start');
            $page = floor($offset / $perPage) + 1;
        }

        $offset = max(0, $offset);

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

        $exclude = $req->query('exclude');
        if (!is_array($exclude)) {
            $exclude = explode(',', $req->query('exclude'));
        }

        $include = $req->query('include');
        if (!is_array($include)) {
            $include = explode(',', $req->query('include'));
        }

        $expand = $req->query('expand');
        if (!is_array($expand)) {
            $expand = explode(',', $req->query('expand'));
        }

        $route->addQueryParams(array_replace([
            'page' => $page,
            'per_page' => $perPage,
            'start' => $offset,
            'limit' => $perPage,
            'sort' => $req->query('sort'),
            'search' => $req->query('search'),
            'where' => $filter,
            'exclude' => array_filter($exclude),
            'include' => array_filter($include),
            'expand' => array_filter($expand), ], $route->getQuery()));
    }

    public function parseModelFindOneParameters(ApiRoute $route)
    {
        $req = $route->getRequest();

        $exclude = $req->query('exclude');
        if (!is_array($exclude)) {
            $exclude = explode(',', $req->query('exclude'));
        }

        $include = $req->query('include');
        if (!is_array($include)) {
            $include = explode(',', $req->query('include'));
        }

        $expand = $req->query('expand');
        if (!is_array($expand)) {
            $expand = explode(',', $req->query('expand'));
        }

        $route->addQueryParams(array_replace([
            'model_id' => $req->params('id'),
            'exclude' => array_filter($exclude),
            'include' => array_filter($include),
            'expand' => array_filter($expand), ], $route->getQuery()));
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

        $page = $query['page'];
        $perPage = $query['per_page'];
        $pageCount = max(1, ceil($result->filtered_count / $perPage));

        // compute links
        $base = $route->getQuery('endpoint_url');

        $baseQuery = $route->getRequest()->query();
        if (isset($baseQuery['page'])) {
            unset($baseQuery['page']);
        }

        if ($query['per_page'] != self::$pageLimit) {
            $baseQuery['per_page'] = $perPage;
        } elseif (isset($baseQuery['per_page'])) {
            unset($baseQuery['per_page']);
        }

        // self/first links
        $links = [
            'self' => $this->link($base, array_replace($baseQuery, ['page' => $page])),
            'first' => $this->link($base, array_replace($baseQuery, ['page' => 1])),
        ];

        // previous/next links
        if ($page > 1) {
            $links['previous'] = $this->link($base, array_replace($baseQuery, ['page' => $page-1]));
        }

        if ($page < $pageCount) {
            $links['next'] = $this->link($base, array_replace($baseQuery, ['page' => $page+1]));
        }

        // last link
        $links['last'] = $this->link($base, array_replace($baseQuery, ['page' => $pageCount]));

        // add links to Link header
        $linkStr = implode(', ', array_map(function ($link, $rel) {
            return "<$link>; rel=\"$rel\"";
        }, $links, array_keys($links)));

        $res->setHeader('Link', $linkStr);

        // add pagination metadata to response body
        // TODO deprecated
        $modelClass = $query['model'];

        $result->page = $page;
        $result->per_page = $perPage;
        $result->page_count = $pageCount;
        $result->total_count = $modelClass::totalRecords($query['where']);
        $result->links = $links;
    }

    private function link($url, array $query)
    {
        return $url.((count($query) > 0) ? '?'.http_build_query($query) : '');
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
        $res = $route->getResponse();

        if ($result) {
            $res->setCode(204);
        } else {
            $errorStack = $this->app['errors'];

            if (count($errorStack->errors()) == 0) {
                $errorStack->push(['error' => 'could_not_delete']);
            }

            $result = new \stdClass();
            $result->error = $errorStack->messages();

            foreach ($errorStack->errors() as $error) {
                if ($error['error'] == 'no_permission') {
                    $res->setCode(403);
                }
            }
        }
    }

    public function transformOutputJson(&$result, ApiRoute $route)
    {
        if (!is_object($result) && !is_array($result)) {
            return;
        }

        $params = 0;
        if ($route->getQuery('pretty') || $route->getRequest()->query('pretty')) {
            $params = JSON_PRETTY_PRINT;
        }

        $route->getResponse()->setContentType('application/json')
            ->setBody(json_encode($result, $params));
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
