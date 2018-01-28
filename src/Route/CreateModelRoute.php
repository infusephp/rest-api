<?php

namespace Infuse\RestApi\Route;

use Infuse\RestApi\Error\ApiError;
use Infuse\RestApi\Error\InvalidRequest;
use Pulsar\Exception\MassAssignmentException;

class CreateModelRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'create';

    /**
     * @var array
     */
    private $createParameters;

    protected function parseRequest()
    {
        parent::parseRequest();

        $this->createParameters = $this->request->request();
    }

    /**
     * Sets the parameters to pass to create().
     *
     * @param array $params
     *
     * @return $this
     */
    public function setCreateParameters(array $params)
    {
        $this->createParameters = $params;

        return $this;
    }

    /**
     * Gets the parameters to pass to create().
     *
     * @return array
     */
    public function getCreateParameters()
    {
        return $this->createParameters;
    }

    public function buildResponse()
    {
        if (!is_array($this->createParameters)) {
            throw new InvalidRequest('Unable to parse request body.');
        }

        parent::buildResponse();

        if (!$this->hasPermission()) {
            throw $this->permissionError();
        }

        try {
            if ($this->model->create($this->createParameters)) {
                $this->response->setCode(201);

                return $this->model;
            }
        } catch (MassAssignmentException $e) {
            throw new InvalidRequest($e->getMessage());
        }

        // get the first error
        if ($error = $this->getFirstError()) {
            throw $this->modelValidationError($error);
        }

        // no specific errors available, throw a server error
        throw new ApiError('There was an error creating the '.$this->humanClassName($this->model).'.');
    }
}
