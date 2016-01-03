<?php

namespace App\Api\Libs;

use ICanBoogie\Inflector;
use Infuse\HasApp;
use Infuse\Request;
use Infuse\Response;

class ApiControllerV2
{
    use HasApp;

    protected static $apiBase = '/api';
    protected static $pageLimit = 100;

    ///////////////////////////////
    // ROUTES
    ///////////////////////////////

    public function create($req, $res, $execute = true)
    {
        return $this->newRoute($req, $res, '_create', $execute);
    }

    public function _create($route)
    {
        $this->parseRequireCreatePermission($route);
        $this->parseModelCreateParameters($route);

        $result = $this->queryModelCreate($route);

        $this->transformModelCreate($result, $route);
        $this->transformModelToArray($result, $route);
        $this->transformOutputJson($result, $route);
    }

    public function findAll($req, $res, $execute = true)
    {
        return $this->newRoute($req, $res, '_findAll', $execute);
    }

    public function _findAll($route)
    {
        $this->parseRouteBase($route);
        $this->parseRequireFindPermission($route);
        $this->parseModelFindAllParameters($route);

        $result = $this->queryModelFindAll($route);

        $this->transformModelToArray($result, $route);
        $this->transformPaginate($result, $route);
        $this->transformOutputJson($result, $route);
    }

    public function findOne($req, $res, $execute = true)
    {
        return $this->newRoute($req, $res, '_findOne', $execute);
    }

    public function _findOne($route)
    {
        $this->parseModelFindOneParameters($route);

        $result = $this->queryModelFindOne($route);

        $this->transformModelFindOne($result, $route);
        $this->transformModelToArray($result, $route);
        $this->transformOutputJson($result, $route);
    }

    public function edit($req, $res, $execute = true)
    {
        return $this->newRoute($req, $res, '_edit', $execute);
    }

    public function _edit($route)
    {
        $this->parseModelEditParameters($route);

        $result = $this->queryModelEdit($route);

        $this->transformModelEdit($result, $route);
        $this->transformModelToArray($result, $route);
        $this->transformOutputJson($result, $route);
    }

    public function delete($req, $res, $execute = true)
    {
        return $this->newRoute($req, $res, '_delete', $execute);
    }

    public function _delete($route)
    {
        $result = $this->queryModelDelete($route);

        $this->transformModelDelete($result, $route);
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

        $url = str_replace(static::$apiBase, $url, $path);

        $route->addQueryParams(['endpoint_url' => $url]);
    }

    public function parseRequireFindPermission(ApiRoute $route)
    {
        $this->requirePermission('find', $route);
    }

    public function parseRequireCreatePermission(ApiRoute $route)
    {
        $this->requirePermission('create', $route);
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
            'exclude' => array_filter($exclude),
            'include' => array_filter($include),
            'expand' => array_filter($expand), ], $route->getQuery()));
    }

    public function parseModelEditParameters(ApiRoute $route)
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
        $parameters = $route->getQuery();

        $where = $parameters['where'];

        // perform a search on all searchable properties
        // WARNING LIKE queries are extremely inefficient
        // use sparingly
        if (!empty($parameters['search']) && property_exists($modelClass, 'searchableProperties')) {
            $w = [];
            $search = addslashes($parameters['search']);
            foreach ($modelClass::$searchableProperties as $name) {
                $w[] = "`$name` LIKE '%$search%'";
            }

            if (count($w) > 0) {
                $where[] = '('.implode(' OR ', $w).')';
            }
        }

        // build the model query
        $query = $modelClass::query();

        // perform joins - use internally only, not from user input!
        if (isset($parameters['join'])) {
            foreach ($parameters['join'] as $condition) {
                list($model, $column, $foreignKey) = $condition;
                $query->join($model, $column, $foreignKey);
            }
        }

        $query->where($where)
              ->start($parameters['start'])
              ->limit($parameters['limit'])
              ->sort($parameters['sort']);

        // load models by executing the query
        $result = $query->execute();

        // total records
        $total = $modelClass::totalRecords($where);
        $route->addQueryParams(['total_count' => $total]);

        return $result;
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

        if ($modelObj->set($route->getQuery('properties'))) {
            return $modelObj;
        } else {
            return false;
        }
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
        if ($result) {
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
    }

    public function transformPaginate(&$result, ApiRoute $route)
    {
        $res = $route->getResponse();
        $query = $route->getQuery();

        // total count
        $totalCount = $query['total_count'];
        $res->setHeader('X-Total-Count', $totalCount);

        $page = $query['page'];
        $perPage = $query['per_page'];
        $pageCount = max(1, ceil($totalCount / $perPage));

        // compute links
        $base = $route->getQuery('endpoint_url');

        $requestQuery = $route->getRequest()->query();
        if (isset($requestQuery['page'])) {
            unset($requestQuery['page']);
        }

        if ($query['per_page'] != self::$pageLimit) {
            $requestQuery['per_page'] = $perPage;
        } elseif (isset($requestQuery['per_page'])) {
            unset($requestQuery['per_page']);
        }

        // self/first links
        $links = [
            'self' => $this->link($base, array_replace($requestQuery, ['page' => $page])),
            'first' => $this->link($base, array_replace($requestQuery, ['page' => 1])),
        ];

        // previous/next links
        if ($page > 1) {
            $links['previous'] = $this->link($base, array_replace($requestQuery, ['page' => $page - 1]));
        }

        if ($page < $pageCount) {
            $links['next'] = $this->link($base, array_replace($requestQuery, ['page' => $page + 1]));
        }

        // last link
        $links['last'] = $this->link($base, array_replace($requestQuery, ['page' => $pageCount]));

        // add links to Link header
        $linkStr = implode(', ', array_map(function ($link, $rel) {
            return "<$link>; rel=\"$rel\"";
        }, $links, array_keys($links)));

        $res->setHeader('Link', $linkStr);
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
        if (!$result) {
            // get the first error
            if ($error = $this->getFirstError()) {
                $code = ($error['error'] == 'no_permission') ? 403 : 400;
                $param = (isset($error['params']['field'])) ? $error['params']['field'] : '';
                throw new Error\InvalidRequest($error['message'], $code, $param);
            // no specific errors available, throw a generic one
            } else {
                throw new Error\Api('There was an error performing the update.');
            }
        }
    }

    public function transformModelToArray(&$result, ApiRoute $route)
    {
        if (is_object($result)) {
            $_model = $result->toArrayDeprecated(
                $route->getQuery('exclude'),
                $route->getQuery('include'),
                $route->getQuery('expand'));

            $result = $_model;
        } elseif (is_array($result)) {
            $models = $result;
            $result = [];

            foreach ($models as $model) {
                $this->transformModelToArray($model, $route, false);
                $result[] = $model;
            }
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

    private function newRoute($req, $res, $action, $execute)
    {
        $route = new ApiRoute([$this, $action]);
        $route->setRequest($req)
              ->setResponse($res);

        $route->addQueryParams($req->params());

        return ($execute) ? $route->execute() : $route;
    }

    /**
     * Checks for the specified permission on a model. Returns 403 if it fails.
     *
     * @param string   $permission
     * @param ApiRoute $route
     * @param object   $modelObj   optional model Object
     *
     * @return bool
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