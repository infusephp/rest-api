<?php

use Pulsar\Model;

class Post extends Model
{
    protected static $properties = [
        'author' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'Person',
            'null' => true,
        ],
        'body' => [
            'type' => Model::TYPE_STRING,
        ],
        'date' => [
            'type' => Model::TYPE_DATE,
        ],
    ];

    protected static $hidden = ['date'];
    protected static $appended = ['appended'];

    public static $without;

    public function withoutArrayHook()
    {
        self::$without = true;
    }

    public function toArrayHook(&$result, array $exclude, array $include, array $expand)
    {
        if (!isset($exclude['hook'])) {
            $result['hook'] = true;
        }

        if (isset($incldue['include'])) {
            $result['include'] = true;
        }
    }
}

class Person extends Model
{
    protected static $properties = [
        'name' => [],
        'email' => [],
        'address' => [
            'type' => Model::TYPE_NUMBER,
            'relation' => 'Address',
        ],
        'balance' => [
            'type' => Model::TYPE_NUMBER,
        ],
        'active' => [
            'type' => Model::TYPE_BOOLEAN,
            'default' => false,
        ],
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['balance'];
    public static $filterableProperties = ['active'];
    public static $searchableProperties = ['name', 'email'];
}

class Address extends Model
{
    protected static $properties = [
        'street' => [],
        'city' => [],
        'state' => [],
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['updated_at'];
}
