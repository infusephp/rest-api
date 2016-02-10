<?php

namespace App\RestApi\Route;

use App\RestApi\Error\InvalidRequest;

class RetrieveModelRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'view';

    protected function parseRequest()
    {
        parent::parseRequest();

        $this->setModelId($this->request->params('model_id'));
    }

    public function buildResponse()
    {
        parent::buildResponse();

        if ($this->model->exists()) {
            return $this->model;
        }

        throw new InvalidRequest($this->humanClassName($this->model).' was not found: '.$this->modelId, 404);
    }
}
