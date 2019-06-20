<?php

namespace Infuse\RestApi\Tests\Route;

use Infuse\Request;
use Infuse\RestApi\Route\ListModelsRoute;
use Infuse\RestApi\Tests\Person;
use Infuse\Test;
use Infuse\RestApi\Error\InvalidRequest;
use Mockery;
use Pulsar\Driver\DriverInterface;
use Pulsar\Query;

class ListModelsRouteTest extends ModelTestBase
{
    const ROUTE_CLASS = ListModelsRoute::class;

    public function testGetPage()
    {
        $route = $this->getRoute();

        $this->assertEquals(1, $route->getPage());
        $route->setPage(10);
        $this->assertEquals(10, $route->getPage());

        $req = new Request(['page' => 50]);
        $route = $this->getRoute($req);
        $this->assertEquals(50, $route->getPage());
    }

    public function testGetPerPage()
    {
        $route = $this->getRoute();

        $this->assertEquals(100, $route->getPerPage());
        $route->setPerPage(10);
        $this->assertEquals(10, $route->getPerPage());

        $req = new Request(['per_page' => 50]);
        $route = $this->getRoute($req);
        $this->assertEquals(50, $route->getPerPage());
    }

    public function testGetPerPageLimit()
    {
        $route = $this->getRoute();

        $req = new Request(['per_page' => 1000]);
        $route = $this->getRoute($req);
        $this->assertEquals(100, $route->getPerPage());
    }

    public function testFilter()
    {
        $route = $this->getRoute();

        $this->assertEquals([], $route->getFilter());
        $route->setFilter(['test' => true]);
        $this->assertEquals(['test' => true], $route->getFilter());

        $filter = ['test' => 'blah', 'invalid' => [], 'invalid2"*)#$*#)%' => []];
        $req = new Request(['filter' => $filter]);
        $route = $this->getRoute($req);
        $this->assertEquals($filter, $route->getFilter());
    }

    public function testExpand()
    {
        $route = $this->getRoute();

        $this->assertEquals([], $route->getExpand());
        $route->setExpand(['test']);
        $this->assertEquals(['test'], $route->getExpand());

        $req = new Request(['expand' => 'test,blah']);
        $route = $this->getRoute($req);
        $this->assertEquals(['test', 'blah'], $route->getExpand());
    }

    public function testJoin()
    {
        $route = $this->getRoute();

        $this->assertFalse($route->getJoin());
        $route->setJoin(['Users', 'Posts', 'Posts.id=Users.id']);
        $this->assertEquals(['Users', 'Posts', 'Posts.id=Users.id'], $route->getJoin());
    }

    public function testSort()
    {
        $route = $this->getRoute();

        $this->assertNull($route->getSort());
        $route->setsort('name asc');
        $this->assertEquals('name asc', $route->getSort());

        $req = new Request(['sort' => 'name desc']);
        $route = $this->getRoute($req);
        $this->assertEquals('name desc', $route->getSort());
    }

    public function testSearch()
    {
        $route = $this->getRoute();

        $this->assertNull($route->getSearch());
        $route->setSearch('test');
        $this->assertEquals('test', $route->getSearch());

        $req = new Request(['search' => 'hello']);
        $route = $this->getRoute($req);
        $this->assertEquals('hello', $route->getSearch());
    }

    public function testBuildQuery()
    {
        $route = $this->getRoute();
        $route->setModel(Person::class)
              ->setPage(3)
              ->setPerPage(50)
              ->setFilter(['active' => true])
              ->setExpand(['address', 'address_shim'])
              ->setJoin([['Address', 'id', 'address_id']])
              ->setSort('name ASC')
              ->setSearch('search!');

        $query = $route->buildQuery();

        $this->assertInstanceOf(Query::class, $query);
        $this->assertEquals(['address'], $query->getWith());
        $this->assertEquals([['Address', 'id', 'address_id']], $query->getJoins());
        $this->assertEquals(['active' => true, "(`name` LIKE '%search!%' OR `email` LIKE '%search!%')"], $query->getWhere());
        $this->assertEquals([['name', 'asc']], $query->getSort());
        $this->assertEquals(100, $query->getStart());
        $this->assertEquals(50, $query->getLimit());
    }

    public function testBuildQueryInvalidFilterPropertyString()
    {
        $this->expectException(InvalidRequest::class, 'Invalid filter parameter: *#)*$J)F(');

        $route = $this->getRoute();
        $route->setModel(Person::class)
              ->setFilter(['*#)*$J)F(' => true]);

        $query = $route->buildQuery();
    }

    public function testBuildQueryInvalidFilterProperty()
    {
        $this->expectException(InvalidRequest::class, 'Invalid filter parameter: test');

        $route = $this->getRoute();
        $route->setModel(Person::class)
              ->setFilter(['test' => true]);

        $query = $route->buildQuery();
    }

    public function testPaginate()
    {
        Test::$app['config']->set('api.url', 'https://example.com/api');

        $req = Request::create('/api/models', 'GET', ['sort' => 'name ASC', 'per_page' => 100]);
        $route = $this->getRoute($req);

        $route->paginate(2, 50, 200);

        $this->assertEquals(200, self::$res->headers('X-Total-Count'));
        $this->assertEquals('<https://example.com/api/models?sort=name+ASC&per_page=50&page=2>; rel="self", <https://example.com/api/models?sort=name+ASC&per_page=50&page=1>; rel="first", <https://example.com/api/models?sort=name+ASC&per_page=50&page=1>; rel="previous", <https://example.com/api/models?sort=name+ASC&per_page=50&page=3>; rel="next", <https://example.com/api/models?sort=name+ASC&per_page=50&page=4>; rel="last"', self::$res->headers('Link'));
    }

    public function testBuildResponse()
    {
        $driver = Mockery::mock(DriverInterface::class);
        $driver->shouldReceive('count')
               ->andReturn(2);
        $driver->shouldReceive('queryModels')
               ->andReturn([['id' => 1], ['id' => 2]]);
        Person::setDriver($driver);

        $route = $this->getRoute();
        $route->setModel(Person::class);

        $models = $route->buildResponse();

        // verify models
        $this->assertCount(2, $models);
        $this->assertInstanceOf(Person::class, $models[0]);
        $this->assertEquals(1, $models[0]->id());
        $this->assertInstanceOf(Person::class, $models[1]);
        $this->assertEquals(2, $models[1]->id());

        // verify headers
        $this->assertGreaterThan(0, strlen(self::$res->headers('Link')));
        $this->assertEquals(2, self::$res->headers('X-Total-Count'));
    }
}
