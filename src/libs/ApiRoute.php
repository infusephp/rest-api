<?php

namespace app\api\libs;

use infuse\Request;
use infuse\Response;
use infuse\Utility as U;

use App;

/*
    An API request can be broken into 3 steps:
    i) Parse:
            Given a request can return the relevant query parameters
    ii) Query:
            Performs the query using the previously generated parameters against a
            model or service
    iii) Transform:
            Takes the result and produces an output
            i.e. an HTTP response containing JSON

    The signatures for each method are:
    bool parse(ApiRoute $route)
    mixed query(ApiRoute $route)
    void transform(mixed &$result, ApiRoute $route)
*/

class ApiRoute
{
    private $query;
    private $parseSteps;
    private $queryStep;
    private $transformSteps;
    private $req;
    private $res;

    /**
     * Creates a new API route object
     *
     * @param array           $parseSteps     collection of ordered callables for parsing
     * @param callable|string $queryStep      step for performing query
     * @param array           $transformSteps collection of ordered callables for transformation
    * @param array $queryParams query parameters
     */
    public function __construct(array $parseSteps = [], $queryStep = false, array $transformSteps = [], array $queryParams = [])
    {
        $this->query = $queryParams;
        $this->parseSteps = $parseSteps;
        $this->queryStep = $queryStep;
        $this->transformSteps = $transformSteps;
    }

    ////////////////////////
    // SETUP
    ////////////////////////

    public function addQueryParams(array $query)
    {
        $this->query = array_replace($this->query, $query);

        return $this;
    }

    /**
     * Appends parse steps to the existing steps
     *
     * @param array $parseSteps collection of ordered callables for parsing
     *
     * @return ApiRoute
     */
    public function addParseSteps(array $parseSteps)
    {
        $this->parseSteps = array_merge($this->parseSteps, $parseSteps);

        return $this;
    }

    /**
     * Sets (and replaces any existing) the query step
     *
     * @param callable|string $queryStep step for performing the query
     *
     * @return ApiRoute
     */
    public function addQueryStep($queryStep)
    {
        $this->queryStep = $queryStep;

        return $this;
    }

    /**
     * Appends transform steps to the existing steps
     *
     * @param array $transformSteps collection of ordered callables for transforming the result
     *
     * @return ApiRoute
     */
    public function addTransformSteps(array $transformSteps)
    {
        $this->transformSteps = array_merge($this->transformSteps, $transformSteps);

        return $this;
    }

    /**
     * Sets the request object
     *
     * @param Response $res
     *
     * @return ApiRoute
     */
    public function setRequest(Request $req)
    {
        $this->req = $req;

        return $this;
    }

    /**
     * Sets the response object
     *
     * @param Response $res
     *
     * @return ApiRoute
     */
    public function setResponse(Response $res)
    {
        $this->res = $res;

        return $this;
    }

    ////////////////////////
    // GETTERS
    ////////////////////////

    /**
     * Gets one or all query parameters
     *
     * @param string $index
     *
     * @return mixed
     */
    public function getQueryParams($index = false)
    {
        return ($index) ? U::array_value($this->query, $index) : $this->query;
    }

    /**
     * Gets the request object
     *
     * @return Request
     */
    public function getRequest()
    {
        return $this->req;
    }

    /**
     * Gets the response object
     *
     * @return Response
     */
    public function getResponse()
    {
        return $this->res;
    }

    /**
     * Gets the parse steps
     *
     * @return array
     */
    public function getParseSteps()
    {
        return $this->parseSteps;
    }

    /**
     * Gets the query step
     *
     * @return string|callable
     */
    public function getQueryStep()
    {
        return $this->queryStep;
    }

    /**
     * Gets the transform steps
     *
     * @return array
     */
    public function getTransformSteps()
    {
        return $this->transformSteps;
    }

    ////////////////////////
    // EXECUTION
    ////////////////////////

    /**
     * Executes the steps in this API route in this order:
     * Parse, Query, Transform
     *
     * @param Request  $req request object
     * @param Response $res response object
     *
     * @return boolean true when completed, false when failed at some step
     */
    public function execute(Request $req, Response $res, App $app = null)
    {
        $this->setRequest($req);
        $this->setResponse($res);

        $api = new Api();
        $api->injectApp($app);

        foreach ($this->parseSteps as $parseStep) {
            if (is_string($parseStep))
                $parseStep = [$api, $parseStep];

            if ($parseStep($this) === false)
                return false;
        }

        $queryStep = $this->queryStep;
        if (is_string($queryStep))
            $queryStep = [$api, $queryStep];

        $result = $queryStep($this);

        foreach ($this->transformSteps as $transformStep) {
            if (is_string($transformStep))
                $transformStep = [$api, $transformStep];

            if ($transformStep($result, $this) === false)
                return false;
        }

        return true;
    }
}
