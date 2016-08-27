<?php

namespace Infuse\RestApi\Route;

use Infuse\RestApi\Error\InvalidRequest;
use Pulsar\ACLModel;

abstract class AbstractModelRoute extends AbstractRoute
{
    /**
     * @var mixed
     */
    protected $modelId = false;

    /**
     * @var Model
     */
    protected $model;

    protected function parseRequest()
    {
        if ($model = $this->request->params('model')) {
            $this->setModel($model);
        }
    }

    /**
     * Sets the model ID.
     *
     * @return self
     */
    public function setModelId($id)
    {
        $this->modelId = $id;

        // rebuild the model instance with ID
        if ($this->model) {
            $model = (is_object($this->model)) ? get_class($this->model) : $this->model;

            $this->model = new $model($id);
        }

        return $this;
    }

    /**
     * Gets the model ID.
     *
     * @return mixed
     */
    public function getModelId()
    {
        return $this->modelId;
    }

    /**
     * Sets the model for this route.
     *
     * @param Model|string $model
     *
     * @return self
     */
    public function setModel($model)
    {
        // convert the model class into an instance
        if (!is_object($model)) {
            $model = new $model($this->modelId);
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Gets the model for this route.
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Checks if the requester has permission to perform this
     * route on this model (if the model implements an ACL).
     *
     * @return bool
     */
    public function hasPermission()
    {
        if (!($this->model instanceof ACLModel)) {
            return true;
        }

        return $this->model->can(static::MODEL_PERMISSION, $this->app['requester']);
    }

    /**
     * Gets the first error off the error stack.
     *
     * @return array|false
     */
    public function getFirstError()
    {
        $errors = $this->app['errors']->errors();

        return (count($errors) > 0) ? $errors[0] : false;
    }

    /**
     * Builds a validation error from a CRUD operation.
     *
     * @param array $error
     *
     * @return InvalidRequest
     */
    protected function modelValidationError(array $error)
    {
        $code = ($error['error'] == 'no_permission') ? 403 : 400;
        $param = array_value($error, 'params.field');

        return new InvalidRequest($error['message'], $code, $param);
    }

    /**
     * Builds a model 404 error.
     *
     * @return InvalidRequest
     */
    protected function modelNotFoundError()
    {
        return new InvalidRequest($this->humanClassName($this->model).' was not found: '.$this->modelId, 404);
    }

    /**
     * Builds a model permission error.
     *
     * @return InvalidRequest
     */
    protected function permissionError()
    {
        return new InvalidRequest('You do not have permission to do that', 403);
    }

    public function buildResponse()
    {
        if (!$this->model) {
            throw $this->requestNotRecognizedError();
        }
    }
}
