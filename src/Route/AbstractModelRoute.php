<?php

namespace Infuse\RestApi\Route;

use Infuse\RestApi\Error\InvalidRequest;
use Pulsar\ACLModel;
use Pulsar\ACLModelRequester;

abstract class AbstractModelRoute extends AbstractRoute
{
    /**
     * @var mixed
     */
    protected $modelId = false;

    /**
     * @var \Pulsar\Model|false
     */
    protected $model;

    /**
     * @var string
     */
    protected $modelClass;

    protected function parseRequest()
    {
        if ($model = $this->request->params('model')) {
            $this->setModel($model);
        }
    }

    /**
     * Sets the model ID.
     *
     * @param mixed $id
     *
     * @return $this
     */
    public function setModelId($id)
    {
        if (is_array($id)) {
            $this->modelId = $id;
        } else {
            $this->modelId = [$id];
        }

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
     * @return array
     */
    public function getModelId()
    {
        return $this->modelId;
    }

    /**
     * Sets the model for this route.
     *
     * @param \Pulsar\Model|string $model
     *
     * @return $this
     */
    public function setModel($model)
    {
        // convert the model class into an instance
        if (!is_object($model)) {
            $this->modelClass = $model;
            $model = new $model($this->modelId);
        } else {
            $this->modelId = $model->ids();
            $this->modelClass = get_class($model);
        }

        $this->model = $model;

        return $this;
    }

    /**
     * Gets the model for this route.
     *
     * @return \Pulsar\Model
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

        return $this->model->can(static::MODEL_PERMISSION, ACLModelRequester::get());
    }

    /**
     * Gets the first error off the error stack.
     *
     * @return array|false
     */
    public function getFirstError()
    {
        if (!$this->model) {
            return false;
        }

        $errors = $this->model->getErrors()->errors();

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
        $code = ('no_permission' == $error['error']) ? 403 : 400;
        $param = array_value($error, 'params.field');

        return new InvalidRequest($error['message'], $code, $param);
    }

    /**
     * Retrieves the model associated on this class with
     * the persisted version from the data layer.
     *
     * @throws InvalidRequest if the model cannot be found
     */
    public function retrieveModel()
    {
        if ($this->model->persisted()) {
            return;
        }

        $modelClass = $this->modelClass;
        $this->model = $modelClass::find($this->modelId);

        if (!$this->model) {
            throw $this->modelNotFoundError();
        }
    }

    /**
     * Builds a model 404 error.
     *
     * @return InvalidRequest
     */
    protected function modelNotFoundError()
    {
        return new InvalidRequest($this->humanClassName($this->modelClass).' was not found: '.implode(',', $this->modelId), 404);
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
