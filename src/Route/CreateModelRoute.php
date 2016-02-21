<?php

namespace App\RestApi\Route;

use App\RestApi\Error\Api as ApiError;

class CreateModelRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'create';

    /**
     * @var array
     */
    private $createParameters = [];

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
     * @return self
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
        parent::buildResponse();

        if ($this->model->create($this->createParameters)) {
            $this->response->setCode(201);

            return $this->model;
        }

        // get the first error
        if ($error = $this->getFirstError()) {
            throw $this->modelValidationError($error);
        }

        // no specific errors available, throw a server error
        throw new ApiError('There was an error creating the '.$this->humanClassName($this->model).'.');
    }
}
