<?php

/**
 * @package infuse\framework
 * @author Jared King <j@jaredtking.com>
 * @link http://jaredtking.com
 * @version 0.1.16
 * @copyright 2013 Jared King
 * @license MIT
 */

namespace app\api;

use app\api\libs\ApiController;

class Controller extends ApiController
{
    public static $properties = [
        'routes' => [
            'post /api/:module' => 'create',
            'post /api/:module/:model' => 'create',
            'get /api/:module' => 'findAll',
            'get /api/:module/:model' => 'findAll',
            'get /api/:module/:model/:id' => 'findOne',
            'put /api/:module/:id' => 'edit',
            'put /api/:module/:model/:id' => 'edit',
            'patch /api/:module/:id' => 'edit',
            'patch /api/:module/:model/:id' => 'edit',
            'delete /api/:module/:id' => 'delete',
            'delete /api/:module/:model/:id' => 'delete',
        ],
    ];
}
