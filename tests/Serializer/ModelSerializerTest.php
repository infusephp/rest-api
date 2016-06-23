<?php

use App\RestApi\Serializer\ModelSerializer;
use Infuse\Request;
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

    public function testConstruct()
    {
        $req = new Request(['exclude' => 'exclude,these', 'include' => 'include,these', 'expand' => 'expand_this']);
        $serializer = new ModelSerializer($req);

        $this->assertEquals(['exclude', 'these'], $serializer->getExclude());
        $this->assertEquals(['include', 'these'], $serializer->getInclude());
        $this->assertEquals(['expand_this'], $serializer->getExpand());
    }

    public function testGettersAndSetters()
    {
        $serializer = new ModelSerializer(new Request());

        $this->assertEquals($serializer, $serializer->setExclude(['exclude']));
        $this->assertEquals(['exclude'], $serializer->getExclude());

        $this->assertEquals($serializer, $serializer->setInclude(['include']));
        $this->assertEquals(['include'], $serializer->getInclude());

        $this->assertEquals($serializer, $serializer->setExpand(['expand']));
        $this->assertEquals(['expand'], $serializer->getExpand());
    }

    public function testSerialize()
    {
        $model = new Post(5);
        $model->author = 6;
        $model->body = 'text';

        $serializer = new ModelSerializer(new Request());

        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');

        $this->assertEquals('blah', $serializer->serialize('blah', $route));
        $this->assertEquals(['blah'], $serializer->serialize(['blah'], $route));

        $expected = [
            'id' => 5,
            'author' => 6,
            'body' => 'text',
            'appended' => null,
            'hook' => true,
        ];

        $this->assertEquals($expected, $serializer->serialize($model, $route));
    }

    public function testSerializeExcluded()
    {
        $model = new Post(5);
        $model->author = 100;

        $serializer = new ModelSerializer(new Request());
        $serializer->setExclude(['id', 'body', 'appended', 'hook']);

        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');

        $expected = [
            'author' => 100,
        ];

        $this->assertEquals($expected, $serializer->serialize($model, $route));
    }

    public function testSerializeIncluded()
    {
        $model = new Post(5);
        $model->body = 'text';
        $model->date = 'Dec 5, 2015';

        $serializer = new ModelSerializer(new Request());
        $serializer->setInclude(['date']);

        $expected = [
            'id' => 5,
            'author' => null,
            'body' => 'text',
            'date' => 'Dec 5, 2015',
            'appended' => null,
            'hook' => true,
        ];

        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');

        $this->assertEquals($expected, $serializer->serialize($model, $route));
    }

    public function testSerializeExpand()
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

        $serializer = new ModelSerializer(new Request());
        $serializer->setExclude(['author.address.created_at'])
                   ->setInclude(['author.balance', 'author.address.updated_at'])
                   ->setExpand(['author.address', 'author.does_not_exist', 'author.id']);

        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');

        $result = $serializer->serialize($model, $route);

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
                'active' => false,
                'created_at' => 1,
                'updated_at' => 2,
            ],
        ];

        $this->assertEquals($expected, $result);
    }

    public function testSerializeMultiple()
    {
        $serializer = new ModelSerializer(new Request());
        $serializer->setExclude(['address'])
                   ->setInclude(['include'])
                   ->setExpand(['expand']);

        $route = Mockery::mock('App\RestApi\Route\AbstractRoute');

        $models = [];
        $expected = [];
        for ($i = 1; $i <= 5; ++$i) {
            $obj = new Person($i);
            $models[] = $obj;

            $expected[] = [
                'id' => $i,
                'email' => null,
                'name' => null,
                'active' => false,
                'include' => null,
                'created_at' => null,
                'updated_at' => null,
            ];
        }

        $result = $serializer->serialize($models, $route);

        $this->assertEquals($expected, $result);
    }
}
