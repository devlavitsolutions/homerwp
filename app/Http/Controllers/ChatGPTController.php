<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;

class ChatGPTController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getAssistantResponse(Request $request)
    {
        $message = $request->input('message');
        $response = $this->openAIService->getAssistantResponse($message);

        return response()->json($response);
    }
}
