<?php

namespace Infuse\RestApi\Route;

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

        $this->retrieveModel();

        if (!$this->hasPermission()) {
            throw $this->permissionError();
        }

        return $this->model;
    }
}
