<?php

namespace app\api\libs;

use ICanBoogie\Inflector;
use infuse\Request;
use infuse\Response;

if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg()
    {
        static $errors = array(
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        );
        $error = json_last_error();

        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}

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
              ->setErrorHandler('handleError')
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
              ->setErrorHandler('handleError')
              ->setRequest($req)
              ->setResponse($res)
              ->setController($this);

        if ($execute) {
            if (!$route->execute() && $res->getCode() == 200) {
                // if the model could not be determined, then it might
                // be the case that the model is actually a model id for
                // a module with only 1 model
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
              ->setErrorHandler('handleError')
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
              ->setErrorHandler('handleError')
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
              ->setErrorHandler('handleError')
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
        // skip this method if a model class has already been supplied
        if ($route->getQuery('model')) {
            return true;
        }

        $req = $route->getRequest();
        $module = $req->params('module');

        // instantiate the controller
        $controller = 'app\\'.$module.'\\Controller';
        if (!class_exists($controller)) {
            throw new Error\InvalidRequest('Request was not recognized: '.$req->method().' '.$req->path(), 404);
        }

        // pick a default model if one isn't provided
        $model = $req->params('model');
        if (!$model && isset($controller::$properties['models']) && count($controller::$properties['models']) > 0) {
            $model = $controller::$properties['models'][0];
        }

        // convert the route name (pluralized underscore) to the class name
        $inflector = Inflector::get();
        $modelClassName = $inflector->singularize($inflector->camelize($model));
        $modelClassName = 'app\\'.$module.'\\models\\'.$modelClassName;

        if (!class_exists($modelClassName)) {
            return false;
        }

        $route->addQueryParams(['model' => $modelClassName]);
    }

    public function parseRequireApiScaffolding(ApiRoute $route)
    {
        // check if api scaffolding is enabled on the model
        if (!property_exists($route->getQuery('model'), 'scaffoldApi')) {
            $req = $route->getRequest();
            throw new Error\InvalidRequest('Request was not recognized: '.$req->method().' '.$req->path(), 404);
        }
    }

    public function parseRequireFindPermission(ApiRoute $route)
    {
        return $this->requirePermission('find', $route);
    }

    public function parseRequireCreatePermission(ApiRoute $route)
    {
        return $this->requirePermission('create', $route);
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
            'expand' => array_filter($expand), ], $route->getQuery()));
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
            $modelRouteName = $this->singularClassName($route->getQuery('model'));

            $response->$modelRouteName = $result->toArray(
                $route->getQuery('exclude'),
                $route->getQuery('include'),
                $route->getQuery('expand'));

            $route->getResponse()->setCode(201);
        } else {
            // get the first error
            if ($error = $this->getFirstError()) {
                $code = ($error['error'] == 'no_permission') ? 403 : 400;
                $param = (isset($error['params']['field'])) ? $error['params']['field'] : '';
                throw new Error\InvalidRequest($error['message'], $code, $param);
            // no specific errors available, throw a server error
            } else {
                throw new Error\Api('There was an error creating the '.$this->humanClassName($route->getQuery('model')).'.');
            }
        }

        $result = $response;
    }

    public function transformModelFindAll(&$result, ApiRoute $route)
    {
        $response = new \stdClass();
        $modelRouteName = $this->pluralClassName($route->getQuery('model'));
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

    public function transformModelFindOne(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        // check if the model exists
        if (!$modelObj->exists()) {
            throw new Error\InvalidRequest($this->humanClassName($modelObj).' was not found: '.$route->getQuery('model_id'), 404);
        }

        // verify the requester has `view` permission on the model
        $this->requirePermission('view', $route, $modelObj);
    }

    public function transformModelEdit(&$result, ApiRoute $route)
    {
        $response = new \stdClass();

        if ($result) {
            // TODO
        } else {
            // get the first error
            if ($error = $this->getFirstError()) {
                $code = ($error['error'] == 'no_permission') ? 403 : 400;
                $param = (isset($error['params']['field'])) ? $error['params']['field'] : '';
                throw new Error\InvalidRequest($error['message'], $code, $param);
            // no specific errors available, throw a generic one
            } else {
                throw new Error\InvalidRequest('There was an error performing the update.');
            }
        }

        $result = $response;
    }

    public function transformModelToArray(&$result, ApiRoute $route)
    {
        $modelObj = $result;

        $modelClass = $route->getQuery('model');
        if (is_object($modelObj) && method_exists($modelClass, 'toArray')) {
            $modelRouteName = $this->singularClassName($modelClass);
            $result = [
                $modelRouteName => $modelObj->toArray(
                    $route->getQuery('exclude'),
                    $route->getQuery('include'),
                    $route->getQuery('expand')),
            ];
        }
    }

    public function transformModelDelete(&$result, ApiRoute $route)
    {
        $res = $route->getResponse();

        if ($result) {
            $res->setCode(204);
        } else {
            // get the first error
            if ($error = $this->getFirstError()) {
                $code = ($error['error'] == 'no_permission') ? 403 : 400;
                $param = (isset($error['params']['field'])) ? $error['params']['field'] : '';
                throw new Error\InvalidRequest($error['message'], $code, $param);
            // no specific errors available, throw a server error
            } else {
                throw new Error\Api('There was an error performing the delete.');
            }
        }
    }

    public function transformOutputJson(&$result, ApiRoute $route)
    {
        if (!is_object($result) && !is_array($result)) {
            return;
        }

        $params = JSON_PRETTY_PRINT;
        if ($route->getQuery('compact') || $route->getRequest()->query('compact')) {
            $params = 0;
        }

        $route->getResponse()
              ->setContentType('application/json')
              ->setBody(json_encode($result, $params));

        if (json_last_error()) {
            $this->app['logger']->error(json_last_error_msg());
        }
    }

    ///////////////////////////////
    // ERROR HANDLING
    ///////////////////////////////

    /**
     * Handles exceptions thrown in API routes.
     *
     * @param Error\Base $ex
     * @param ApiRoute   $route
     */
    public function handleError(Error\Base $ex, ApiRoute $route)
    {
        // build response body
        $body = [
            'type' => $this->singularClassName($ex),
            'message' => $ex->getMessage(),
        ];

        if ($ex instanceof Error\InvalidRequest && $param = $ex->getParam()) {
            $body['param'] = $param;
        }

        // set HTTP status code
        $route->getResponse()
              ->setCode($ex->getHttpStatus());

        $this->transformOutputJson($body, $route);
    }

    ///////////////////////////////
    // PRIVATE METHODS
    ///////////////////////////////

    /**
     * Checks for the specified permission on a model. Returns 403 if it fails.
     *
     * @param string   $permission
     * @param ApiRoute $route
     * @param object   $modelObj   optional model Object
     *
     * @return boolean
     */
    private function requirePermission($permission, ApiRoute $route, $modelObj = false)
    {
        if (!$modelObj) {
            $modelClass = $route->getQuery('model');
            if (is_object($modelClass)) {
                $modelObj = $modelClass;
            } else {
                $modelObj = new $modelClass();
            }
        }

        if (!$modelObj->can($permission, $this->app['requester'])) {
            throw new Error\InvalidRequest('You do not have permission to do that', 403);
        }

        return true;
    }

    /**
     * Generates the human name for a class
     * i.e. LineItem -> Line Item.
     *
     * @param string|object $class
     *
     * @return string
     */
    private function humanClassName($class)
    {
        // get the class name if an object is given
        if (is_object($class)) {
            $class = get_class($class);
        }

        // split the class name up by namespaces
        $namespace = explode('\\', $class);
        $className = end($namespace);

        // convert the class name into the humanized version
        $inflector = Inflector::get();

        return $inflector->humanize($className);
    }

    /**
     * Generates the singular key from a class
     * i.e. LineItem -> line_item.
     *
     * @param string|object $class
     *
     * @return string
     */
    private function singularClassName($class)
    {
        // get the class name if an object is given
        if (is_object($class)) {
            $class = get_class($class);
        }

        // split the class name up by namespaces
        $namespace = explode('\\', $class);
        $className = end($namespace);

        // convert the class name into the underscore version
        $inflector = Inflector::get();

        return $inflector->underscore($className);
    }

    /**
     * Generates the plural key from a class
     * i.e. LineItem -> line_items.
     *
     * @param string|object $class
     *
     * @return string
     */
    private function pluralClassName($class)
    {
        // get the class name if an object is given
        if (is_object($class)) {
            $class = get_class($class);
        }

        // split the class name up by namespaces
        $namespace = explode('\\', $class);
        $className = end($namespace);

        // convert the class name into the pluralized underscore version
        $inflector = Inflector::get();

        return $inflector->pluralize($inflector->underscore($className));
    }

    private function link($url, array $query)
    {
        return $url.((count($query) > 0) ? '?'.http_build_query($query) : '');
    }

    /**
     * Gets the first error off the error stack.
     *
     * @return array|false
     */
    private function getFirstError()
    {
        $errors = $this->app['errors']->errors();

        return (count($errors) > 0) ? $errors[0] : false;
    }
}
