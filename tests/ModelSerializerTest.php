<?php

use App\RestApi\Libs\ModelSerializer;
use Pulsar\Model;

class ModelSerializerTest extends PHPUnit_Framework_TestCase
{
    public static $driver;

    public static function setUpBeforeClass()
    {
        self::$driver = Mockery::mock('Pulsar\Driver\DriverInterface');

        self::$driver->shouldReceive('loadModel')
                     ->andReturn([]);

        Model::setDriver(self::$driver);
    }

    public function testGettersAndSetters()
    {
        $serializer = new ModelSerializer();

        $this->assertEquals($serializer, $serializer->setExclude(['exclude']));
        $this->assertEquals(['exclude'], $serializer->getExclude());

        $this->assertEquals($serializer, $serializer->setInclude(['include']));
        $this->assertEquals(['include'], $serializer->getInclude());

        $this->assertEquals($serializer, $serializer->setExpand(['expand']));
        $this->assertEquals(['expand'], $serializer->getExpand());
    }

    public function testToArray()
    {
        $model = new Post(5);
        $model->author = 6;
        $model->body = 'text';

        $serializer = new ModelSerializer();

        $expected = [
            'id' => 5,
            'author' => 6,
            'body' => 'text',
            'appended' => null,
            'hook' => true,
        ];

        $this->assertEquals($expected, $serializer->toArray($model));
    }

    public function testToArrayExcluded()
    {
        $model = new Post(5);
        $model->author = 100;

        $serializer = new ModelSerializer();
        $serializer->setExclude(['id', 'body', 'appended', 'hook']);

        $expected = [
            'author' => 100,
        ];

        $this->assertEquals($expected, $serializer->toArray($model));
    }

    public function testToArrayIncluded()
    {
        $model = new Post(5);
        $model->body = 'text';
        $model->date = 'Dec 5, 2015';

        $serializer = new ModelSerializer();
        $serializer->setInclude(['date']);

        $expected = [
            'id' => 5,
            'author' => null,
            'body' => 'text',
            'date' => 'Dec 5, 2015',
            'appended' => null,
            'hook' => true,
        ];

        $this->assertEquals($expected, $serializer->toArray($model));
    }

    public function testToArrayExpand()
    {
        self::$driver = Mockery::mock('Pulsar\Driver\DriverInterface');
        self::$driver->shouldReceive('loadModel')
                     ->andReturn([
                        'body' => 'text',
                        'author' => 100,
                        'date' => 3,
                        'appended' => '...',
                      ]);
        Model::setDriver(self::$driver);

        $model = new Post(10);
        $author = $model->relation('author');
        $author->address = 200;
        $author->name = 'Bob';
        $author->email = 'bob@example.com';
        $author->created_at = 1;
        $author->updated_at = 2;
        $author->balance = 150;

        $serializer = new ModelSerializer();
        $serializer->setExclude(['author.address.created_at'])
                   ->setInclude(['author.balance', 'author.address.updated_at'])
                   ->setExpand(['author.address']);

        $result = $serializer->toArray($model);

        $expected = [
            'id' => 10,
            'body' => 'text',
            'appended' => '...',
            'hook' => true,
            'author' => [
                'id' => 100,
                'name' => 'Bob',
                'email' => 'bob@example.com',
                'address' => [
                    'id' => 200,
                    'street' => null,
                    'city' => '',
                    'state' => '',
                    'updated_at' => null,
                ],
                'balance' => 150,
                'created_at' => 1,
                'updated_at' => 2,
            ],
        ];

        $this->assertEquals($expected, $result);
    }
}
