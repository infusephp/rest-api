<?php

namespace App\RestApi\Serializer;

use App\RestApi\Route\AbstractRoute;
use Infuse\Request;
use Pulsar\Model;

class ModelSerializer implements SerializerInterface
{
    /**
     * @var array
     */
    private $exclude = [];

    /**
     * @var array
     */
    private $include = [];

    /**
     * @var array
     */
    private $expand = [];

    /**
     * @var Request
     */
    private $request;

    /**
     * @param Request $req
     */
    public function __construct(Request $req)
    {
        $this->request = $req;

        // exclude parameter
        $exclude = $req->query('exclude');
        if (is_string($exclude) && !empty($exclude)) {
            $exclude = explode(',', $exclude);
        }

        if (is_array($exclude)) {
            $this->setExclude(array_filter($exclude));
        }

        // include parameter
        $include = $req->query('include');
        if (is_string($include) && !empty($include)) {
            $include = explode(',', $include);
        }

        if (is_array($include)) {
            $this->setInclude(array_filter($include));
        }

        // expand parameter
        $expand = $req->query('expand');
        if (is_string($expand) && !empty($expand)) {
            $expand = explode(',', $expand);
        }

        if (is_array($expand)) {
            $this->setExpand(array_filter($expand));
        }
    }

    /**
     * Sets properties to be excluded.
     *
     * @param array $exclude
     *
     * @return self
     */
    public function setExclude(array $exclude)
    {
        $this->exclude = $exclude;

        return $this;
    }

    /**
     * Gets properties to be excluded.
     *
     * @return array
     */
    public function getExclude()
    {
        return $this->exclude;
    }

    /**
     * Sets properties to be included.
     *
     * @param array $exclude
     *
     * @return self
     */
    public function setInclude(array $include)
    {
        $this->include = $include;

        return $this;
    }

    /**
     * Gets properties to be included.
     *
     * @return array
     */
    public function getInclude()
    {
        return $this->include;
    }

    /**
     * Sets properties to be expanded.
     *
     * @param array $exclude
     *
     * @return self
     */
    public function setExpand(array $expand)
    {
        $this->expand = $expand;

        return $this;
    }

    /**
     * Gets properties to be expanded.
     *
     * @return array
     */
    public function getExpand()
    {
        return $this->expand;
    }

    /**
     * Serializes a model to an array.
     *
     * @param Model $model model to be serialized
     *
     * @return array properties
     */
    public function toArray(Model $model)
    {
        // start with the base representation of the model
        $result = $model->toArray();

        // apply namespacing to excluded properties
        $namedExc = [];
        foreach ($this->exclude as $k) {
            array_set($namedExc, $k, true);
        }

        // apply namespacing to included properties
        $namedInc = [];
        foreach ($this->include as $k) {
            array_set($namedInc, $k, true);
        }

        // apply namespacing to expanded properties
        $namedExp = [];
        foreach ($this->expand as $k) {
            array_set($namedExp, $k, true);
        }

        // remove excluded properties
        foreach (array_keys($result) as $k) {
            if (isset($namedExc[$k]) && !is_array($namedExc[$k])) {
                unset($result[$k]);
            }
        }

        // add included properties
        foreach (array_keys($namedInc) as $k) {
            if (!isset($result[$k]) && isset($namedInc[$k])) {
                $result[$k] = $model->$k;
            }
        }

        // expand any relational model properties
        $result = $this->expand($model, $result, $namedExc, $namedInc, $namedExp);

        // apply hooks, if available
        if (method_exists($model, 'toArrayHook')) {
            $model->toArrayHook($result, $namedExc, $namedInc, $namedExp);
        }

        return $result;
    }

    /**
     * Expands any relational properties within a result.
     *
     * @param array $model
     * @param array $result
     * @param array $namedExc
     * @param array $namedInc
     * @param array $namedExp
     *
     * @return array
     */
    private function expand(Model $model, array $result, array $namedExc, array $namedInc, array $namedExp)
    {
        foreach ($namedExp as $k => $subExp) {
            // if not a property, or the value is null is null, excluded, or not included
            // then we are not going to expand it
            if (!$model::hasProperty($k) || !isset($result[$k]) || !$result[$k]) {
                continue;
            }

            $subExc = array_value($namedExc, $k);
            $subInc = array_value($namedInc, $k);

            // convert exclude, include, and expand into dot notation
            // then take the keys for a flattened dot notation
            $flatExc = is_array($subExc) ? array_keys(array_dot($subExc)) : [];
            $flatInc = is_array($subInc) ? array_keys(array_dot($subInc)) : [];
            $flatExp = is_array($subExp) ? array_keys(array_dot($subExp)) : [];

            $relation = $model->relation($k);
            $serializer = new self($this->request);
            $serializer->setExclude($flatExc)
                       ->setInclude($flatInc)
                       ->setExpand($flatExp);
            $result[$k] = $serializer->toArray($relation);
        }

        return $result;
    }

    public function serialize($input, AbstractRoute $route)
    {
        // serialize a collection of models
        if (is_array($input)) {
            $models = [];
            foreach ($input as $model) {
                // skip serialization if we are not dealing with models
                if (!($model instanceof Model)) {
                    return $input;
                }

                $models[] = $this->toArray($model);
            }

            return $models;
        }

        // serialize a single model
        if ($input instanceof Model) {
            return $this->toArray($input);
        }

        return $input;
    }
}
