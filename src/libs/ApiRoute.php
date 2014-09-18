<?php

namespace app\api\libs;

use infuse\Request;
use infuse\Response;

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
    bool parse( Request $req, Response $res, array &$query )
    mixed query( array $query )
    void transform( Response $res, array $query, mixed &$result )
*/

class ApiRoute
{
    private $parseSteps;
    private $queryStep;
    private $transformSteps;

    /**
     * Creates a new API route object
     *
     * @param array $parseSteps collection of ordered callables for parsing
     * @param callable|string $queryStep step for performing query
     * @param array $transformSteps collection of ordered callables for transformation
     */
    public function __construct(array $parseSteps, $queryStep, array $transformSteps)
    {
        $this->parseSteps = $parseSteps;
        $this->queryStep = $queryStep;
        $this->transformSteps = $transformSteps;
    }

    /**
     * Executes the steps in this API route in this order:
     * Parse, Query, Transform
     *
     * @param Request $req request object
     * @param Response $res response object
     *
     * @return boolean true when completed, false when failed at some step
     */
    public function execute(Request $req, Response $res, App $app = null)
    {
        $api = new Api($app);

        $query = [];

        foreach ($this->parseSteps as $step) {
            if (is_string($step))
                $step = [$api, $step];

            if( $step( $req, $res, $query ) === false )

                return false;
        }

        $q = $this->queryStep;
        if (is_string($q))
            $q = [$api, $q];

        $result = $q( $query );

        foreach ($this->transformSteps as $step) {
            if (is_string($step))
                $step = [$api, $step];

            if( $step( $res, $query, $result ) === false )

                return false;
        }

        return true;
    }
}
