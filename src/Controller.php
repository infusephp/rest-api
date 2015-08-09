<?php

namespace app\api;

use app\api\libs\ApiController;

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

class Controller extends ApiController
{
    public function middleware($req, $res)
    {
        // add in the default api routes
        $controller = 'api\\Controller';
        $this->app->post('/api/:module', [$controller, 'create'])
                  ->post('/api/:module/:model', [$controller, 'create'])
                  ->get('/api/:module', [$controller, 'findAll'])
                  ->get('/api/:module/:model', [$controller, 'findAll'])
                  ->get('/api/:module/:model/:id', [$controller, 'findOne'])
                  ->patch('/api/:module/:id', [$controller, 'edit'])
                  ->patch('/api/:module/:model/:id', [$controller, 'edit'])
                  ->delete('/api/:module/:id', [$controller, 'delete'])
                  ->delete('/api/:module/:model/:id', [$controller, 'delete'])
                  // WARNING put will be deprecated in the future
                  ->put('/api/:module/:id', [$controller, 'edit'])
                  ->put('/api/:module/:model/:id', [$controller, 'edit']);
    }
}
