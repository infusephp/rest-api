<?php

namespace App\RestApi\Serializer;

use App\RestApi\Route\AbstractRoute;
use Infuse\Request;

// @codeCoverageIgnoreStart
if (!function_exists('json_last_error_msg')) {
    function json_last_error_msg()
    {
        static $errors = array(
            JSON_ERROR_NONE => null,
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded',
        );
        $error = json_last_error();

        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
}
// @codeCoverageIgnoreEnd

class JsonSerializer implements SerializerInterface
{
    /**
     * @var int
     */
    private $jsonParams = JSON_PRETTY_PRINT;

    /**
     * @param Request $req
     */
    public function __construct(Request $req)
    {
        if ($req->query('compact')) {
            $this->compactPrint();
        }
    }

    /**
     * Serializes input to pretty printed JSON.
     *
     * @return self
     */
    public function prettyPrint()
    {
        $this->jsonParams = JSON_PRETTY_PRINT;

        return $this;
    }

    /**
     * Serializes input to compact JSON.
     *
     * @return self
     */
    public function compactPrint()
    {
        $this->jsonParams = 0;

        return $this;
    }

    /**
     * Gets the parameters to be passed to json_encode.
     *
     * @return int
     */
    public function getJsonParams()
    {
        return $this->jsonParams;
    }

    public function serialize($input, AbstractRoute $route)
    {
        // skip serialization if the input cannot be JSON encoded
        if (!is_object($input) && !is_array($input)) {
            return $input;
        }

        $route->getResponse()
              ->setContentType('application/json')
              ->setBody(json_encode($input, $this->jsonParams));

        if (json_last_error()) {
            $route->getApp()['logger']->error(json_last_error_msg());
        }
    }
}
