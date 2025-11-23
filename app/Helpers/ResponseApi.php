<?php

namespace App\Helpers;

class ResponseApi
{
    public function continue()
    {
        return json_encode([
            'code' => 100,
            'data' => null,
            'message' => 'Request approved.'
        ]);
    }

    public function switchProtocol()
    {
        return json_encode([
            'code' => 101,
            'data' => null,
            'message' => 'Request switch protocol.'
        ]);
    }

    public function success($data = null)
    {
        return json_encode([
            'code' => 200,
            'data' => $data,
            'message' => 'Success.'
        ]);
    }

    public function created($data = null)
    {
        return json_encode([
            'code' => 201,
            'data' => $data,
            'message' => 'Resource created successfully.'
        ]);
    }

    public function accepted($data = null)
    {
        return json_encode([
            'code' => 202,
            'data' => $data,
            'message' => 'Request accepted.'
        ]);
    }

    public function noContent()
    {
        return json_encode([
            'code' => 204,
            'data' => null,
            'message' => 'No content.'
        ]);
    }

    public function dataNotFound()
    {
        return json_encode([
            'code' => 404,
            'data' => null,
            'message' => 'Data not found.'
        ]);
    }

    public function badResource()
    {
        return json_encode([
            'code' => 301,
            'data' => null,
            'message' => 'Bad resource.'
        ]);
    }

    public function badRequest($message = 'Bad request.')
    {
        return json_encode([
            'code' => 400,
            'data' => null,
            'message' => $message
        ]);
    }

    public function unauthorized($message = 'Unauthorized.')
    {
        return json_encode([
            'code' => 401,
            'data' => null,
            'message' => $message
        ]);
    }

    public function forbidden($message = 'Forbidden.')
    {
        return json_encode([
            'code' => 403,
            'data' => null,
            'message' => $message
        ]);
    }

    public function conflict($message = 'Conflict.')
    {
        return json_encode([
            'code' => 409,
            'data' => null,
            'message' => $message
        ]);
    }

    public function unprocessableEntity($message = 'Unprocessable entity.')
    {
        return json_encode([
            'code' => 422,
            'data' => null,
            'message' => $message
        ]);
    }

    public function internalServerError($message = 'Internal server error.')
    {
        return json_encode([
            'code' => 500,
            'data' => null,
            'message' => $message
        ]);
    }

    public function notImplemented($message = 'Not implemented.')
    {
        return json_encode([
            'code' => 501,
            'data' => null,
            'message' => $message
        ]);
    }

    public function serviceUnavailable($message = 'Service unavailable.')
    {
        return json_encode([
            'code' => 503,
            'data' => null,
            'message' => $message
        ]);
    }
}
