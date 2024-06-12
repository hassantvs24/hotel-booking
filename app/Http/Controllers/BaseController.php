<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    /**
     * @param $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function sendSuccess($data, string $message = '', int $code = 200) : JsonResponse
    {
        $response = [
            'data'    => $data,
            'message' => $message,
            'success' => true,
        ];

        return response()->json($response, $code);
    }

    /**
     * @param $error
     * @param array $errorMessages
     * @param int $code
     * @return JsonResponse
     */
    public function sendError($error, array $errorMessages = [], int $code = 404) : JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $error,
        ];

        if (!empty($errorMessages)) {
            $response['data'] = $errorMessages;
        }

        return response()->json($response, $code);
    }
}
