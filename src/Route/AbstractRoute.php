<?php

namespace App\RestApi\Route;

use App\RestApi\Error;
use App\RestApi\Error\InvalidRequest;
use App\RestApi\Serializer\SerializerInterface;
use ICanBoogie\Inflector;
use Infuse\HasApp;
use Infuse\Request;
use Infuse\Response;

abstract class AbstractRoute
{
    use HasApp;

    protected static $apiBase = '/api';

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Response
     */
    protected $response;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @param Request  $req
     * @param Response $res
     */
    public function __construct(Request $req, Response $res)
    {
        $this->request = $req;
        $this->response = $res;

        $this->parseRequest();
    }

    public function __invoke()
    {
        $this->run();
    }

    /**
     * Gets the request object.
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Gets the response object.
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * Sets the response serializer.
     *
     * @param SerializerInterface $serializer
     *
     * @return self
     */
    public function setSerializer(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;

        return $this;
    }

    /**
     * Gets the response serializer.
     *
     * @return SerializerInterface
     */
    public function getSerializer()
    {
        return $this->serializer;
    }

    /**
     * Runs the API route.
     *
     * @return self
     */
    public function run()
    {
        try {
            $response = $this->buildResponse();
        } catch (Error\Base $ex) {
            $response = $this->handleError($ex);
        }

        $this->serializer->serialize($response, $this);

        return $this;
    }

    /**
     * Handles exceptions thrown in this route.
     *
     * @param Error\Base $ex
     *
     * @return mixed
     */
    public function handleError(Error\Base $ex)
    {
        // build response body
        $body = [
            'type' => $this->singularClassName($ex),
            'message' => $ex->getMessage(),
        ];

        if ($ex instanceof InvalidRequest && $param = $ex->getParam()) {
            $body['param'] = $param;
        }

        // set HTTP status code
        $this->response->setCode($ex->getHttpStatus());

        return $body;
    }

    /**
     * This is called within the route constructor. It should be
     * used to customize the route with properties from the request.
     *
     * @throws Error\Base when an API error occurs
     */
    abstract protected function parseRequest();

    /**
     * Builds a response body.
     *
     * @throws Error\Base when an API error occurs
     *
     * @return mixed
     */
    abstract public function buildResponse();

    ///////////////////////////////
    // HELPER METHODS
    ///////////////////////////////

    /**
     * Gets the full URL for this API route.
     *
     * @return string
     */
    public function getEndpoint()
    {
        $url = $this->app['config']->get('api.url');
        if (!$url) {
            $url = $this->app['base_url'].substr(static::$apiBase, 1);
        }

        // replace the default API base with a full URL
        $path = $this->request->path();
        if (substr($path, -1) == '/') {
            $path = substr($path, 0, -1);
        }

        return str_replace(static::$apiBase, $url, $path);
    }

    /**
     * Generates the human name for a class
     * i.e. LineItem -> Line Item.
     *
     * @param string|object $class
     *
     * @return string
     */
    public function humanClassName($class)
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

        return $inflector->titleize($inflector->underscore($className));
    }

    /**
     * Generates the singular key from a class
     * i.e. LineItem -> line_item.
     *
     * @param string|object $class
     *
     * @return string
     */
    public function singularClassName($class)
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
    public function pluralClassName($class)
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
}
