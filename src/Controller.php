<?php

namespace app\api;

use app\api\libs\ApiController;

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
