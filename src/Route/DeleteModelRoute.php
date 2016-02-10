<?php

namespace App\RestApi\Route;

use App\RestApi\Error\Api as ApiError;
use App\RestApi\Error\InvalidRequest;

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

        if ($this->model->delete()) {
            $this->response->setCode(204);

            return;
        }

        // get the first error
        if ($error = $this->getFirstError()) {
            $code = ($error['error'] == 'no_permission') ? 403 : 400;
            $param = array_value($error, 'params.field');
            throw new InvalidRequest($error['message'], $code, $param);
        // no specific errors available, throw a server error
        } else {
            throw new ApiError('There was an error deleting the '.$this->humanClassName($this->model).'.');
        }
    }
}
