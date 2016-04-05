<?php

namespace App\RestApi\Route;

class ListModelsRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'find';
    const DEFAULT_PER_PAGE = 100;

    /**
     * @staticvar int
     */
    protected static $pageLimit = 1000;

    /**
     * @var int
     */
    protected $perPage = self::DEFAULT_PER_PAGE;

    /**
     * @var int
     */
    protected $page = 1;

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var array
     */
    protected $join = false;

    /**
     * @var string
     */
    protected $sort;

    /**
     * @var string
     */
    protected $search;

    protected function parseRequest()
    {
        parent::parseRequest();

        // parse paging
        if ($this->request->query('per_page')) {
            $this->perPage = (int) $this->request->query('per_page');
        }

        $this->perPage = max(0, min(static::$pageLimit, $this->perPage));

        if ($page = $this->request->query('page')) {
            $this->page = $page;
        }

        $this->page = max(1, $this->page);

        // parse filter/sort/search parameters
        $filter = [];
        foreach ((array) $this->request->query('filter') as $key => $value) {
            if (is_numeric($key) || !preg_match('/^[A-Za-z0-9_]*$/', $key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $filter[$key] = $value;
        }

        $this->filter = $filter;
        $this->sort = $this->request->query('sort');
        $this->search = $this->request->query('search');
    }

    /**
     * Sets the maximum # of results to return.
     *
     * @return self
     */
    public function setPerPage($perPage)
    {
        $this->perPage = $perPage;

        return $this;
    }

    /**
     * Gets the maximum # of results to return.
     *
     * @return int
     */
    public function getPerPage()
    {
        return $this->perPage;
    }

    /**
     * Sets the page #.
     *
     * @return self
     */
    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    /**
     * Gets the page #.
     *
     * @return int
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Sets the query filter.
     *
     * @param string $filter
     *
     * @return self
     */
    public function setFilter(array $filter)
    {
        $this->filter = $filter;

        return $this;
    }

    /**
     * Gets the query filter.
     *
     * @return array
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * Sets the join conditions.
     * Use internally only, not from user input!
     *
     * @param array $join
     *
     * @return self
     */
    public function setJoin(array $join)
    {
        $this->join = $join;

        return $this;
    }

    /**
     * Gets the join conditions.
     *
     * @return array
     */
    public function getJoin()
    {
        return $this->join;
    }

    /**
     * Sets the sort string.
     *
     * @param string $sort
     *
     * @return self
     */
    public function setSort($sort)
    {
        $this->sort = $sort;

        return $this;
    }

    /**
     * Gets the sort string.
     *
     * @return string
     */
    public function getSort()
    {
        return $this->sort;
    }

    /**
     * Sets the search string.
     *
     * @param string $search
     *
     * @return self
     */
    public function setSearch($search)
    {
        $this->search = $search;

        return $this;
    }

    /**
     * Gets the search string.
     *
     * @return string
     */
    public function getSearch()
    {
        return $this->search;
    }

    public function buildResponse()
    {
        parent::buildResponse();

        if (!$this->hasPermission()) {
            throw $this->permissionError();
        }

        $query = $this->buildQuery();
        $models = $query->execute();

        $model = $this->model;
        $total = $model::totalRecords($query->getWhere());

        $this->paginate($this->page, $this->perPage, $total);

        return $models;
    }

    /**
     * Builds the model query.
     *
     * @return Pulsar\Query
     */
    public function buildQuery()
    {
        $model = $this->model;
        $query = $model::query();

        // perform joins
        if (is_array($this->join)) {
            foreach ($this->join as $condition) {
                list($joinModel, $column, $foreignKey) = $condition;
                $query->join($joinModel, $column, $foreignKey);
            }
        }

        $query->where($this->filter);

        // performs a search using LIKE queries
        // WARNING use sparingly, these queries are expensive
        if (!empty($this->search) && is_string($this->search) && property_exists($model, 'searchableProperties')) {
            $w = [];
            $search = addslashes($this->search);
            foreach ($model::$searchableProperties as $name) {
                $w[] = "`$name` LIKE '%$search%'";
            }

            if (count($w) > 0) {
                $query->where('('.implode(' OR ', $w).')');
            }
        }

        if (isset($this->sort)) {
            $query->sort($this->sort);
        }

        // calculate limit and offset
        $start = ($this->page - 1) * $this->perPage;
        $query->start($start)->limit($this->perPage);

        return $query;
    }

    /**
     * Paginates the results from this route.
     *
     * @param int $page
     * @param int $perPage
     * @param int $total
     */
    public function paginate($page, $perPage, $total)
    {
        // set X-Total-Count header
        $this->response->setHeader('X-Total-Count', $total);

        // compute links
        $pageCount = max(1, ceil($total / $perPage));
        $base = $this->getEndpoint();

        $requestQuery = $this->request->query();

        // remove any previously set per_page value
        if (isset($requestQuery['per_page'])) {
            unset($requestQuery['per_page']);
        }

        // set the per_page value unless it's the default
        if ($perPage != self::DEFAULT_PER_PAGE) {
            $requestQuery['per_page'] = $perPage;
        }

        // self/first links
        $links = [
            'self' => $this->link($base, array_replace($requestQuery, ['page' => $page])),
            'first' => $this->link($base, array_replace($requestQuery, ['page' => 1])),
        ];

        // previous/next links
        if ($page > 1) {
            $links['previous'] = $this->link($base, array_replace($requestQuery, ['page' => $page - 1]));
        }

        if ($page < $pageCount) {
            $links['next'] = $this->link($base, array_replace($requestQuery, ['page' => $page + 1]));
        }

        // last link
        $links['last'] = $this->link($base, array_replace($requestQuery, ['page' => $pageCount]));

        // build Link header
        $linkStr = implode(', ', array_map(function ($link, $rel) {
            return "<$link>; rel=\"$rel\"";
        }, $links, array_keys($links)));

        $this->response->setHeader('Link', $linkStr);
    }

    /**
     * Generates a pagination link.
     *
     * @param string $url
     * @param array  $query URL query parameters
     *
     * @return string URL
     */
    private function link($url, array $query)
    {
        return $url.((count($query) > 0) ? '?'.http_build_query($query) : '');
    }
}
