<?php

namespace Infuse\RestApi\Route;

use Infuse\RestApi\Error\ApiError;

class DeleteModelRoute extends AbstractModelRoute
{
    const MODEL_PERMISSION = 'delete';

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

        if ($this->model->delete()) {
            $this->response->setCode(204);

            return;
        }

        // get the first error
        if ($error = $this->getFirstError()) {
            throw $this->modelValidationError($error);
        }

        // no specific errors available, throw a generic error
        throw new ApiError('There was an error deleting the '.$this->humanClassName($this->model).'.');
    }
}
