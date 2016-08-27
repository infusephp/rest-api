<?php

namespace Infuse\RestApi\Route;

use Infuse\RestApi\Error\ApiError;

class EditModelRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'edit';

    /**
     * @var array
     */
    private $updateParameters = [];

    protected function parseRequest()
    {
        parent::parseRequest();

        $this->setModelId($this->request->params('model_id'));
        $this->updateParameters = $this->request->request();
    }

    /**
     * Sets the parameters to pass to set().
     *
     * @param array $params
     *
     * @return self
     */
    public function setUpdateParameters(array $params)
    {
        $this->updateParameters = $params;

        return $this;
    }

    /**
     * Gets the parameters to pass to set().
     *
     * @return array
     */
    public function getUpdateParameters()
    {
        return $this->updateParameters;
    }

    public function buildResponse()
    {
        parent::buildResponse();

        if (!$this->model->exists()) {
            throw $this->modelNotFoundError();
        }

        if (!$this->hasPermission()) {
            throw $this->permissionError();
        }

        if ($this->model->set($this->updateParameters)) {
            return $this->model;
        }

        // get the first error
        if ($error = $this->getFirstError()) {
            throw $this->modelValidationError($error);
        }

        // no specific errors available, throw a generic one
        throw new ApiError('There was an error updating the '.$this->humanClassName($this->model).'.');
    }
}
