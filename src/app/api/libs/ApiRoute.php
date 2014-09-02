<?php

namespace app\api\libs;

use infuse\Request;
use infuse\Response;

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
	 * @param callable $queryStep step for performing query
	 * @param array $transformSteps collection of ordered callables for transformation
	 */
    public function __construct(array $parseSteps, callable $queryStep, array $transformSteps)
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
	 * @return void
	 */
    public function execute(Request $req, Response $res)
    {
        $query = [];

        foreach ($this->parseSteps as $step) {
            if( $step( $req, $res, $query ) === false )

                return;
        }

        $q = $this->queryStep;
        $result = $q( $query );

        foreach ($this->transformSteps as $step) {
            if( $step( $res, $query, $result ) === false )

                return;
        }
    }
}
