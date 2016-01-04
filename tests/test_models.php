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
    ];

    protected static $autoTimestamps;
    protected static $hidden = ['balance'];
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
