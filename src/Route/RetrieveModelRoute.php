<?php

namespace App\RestApi\Route;

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

        if (!$this->model->exists()) {
            throw $this->modelNotFoundError();
        }

        if (!$this->hasPermission()) {
            throw $this->permissionError();
        }

        return $this->model;
    }
}
