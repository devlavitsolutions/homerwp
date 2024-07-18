<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Log;

class ChatGPTController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getAssistantResponse(Request $request)
    {
        $keywords = $request->input('keywords');
        $website = $request->input('website');
        $licenceKey = $request->input('licence_key');
        $response = $this->openAIService->getAssistantResponse($keywords);

        Log::create([
            'keywords' => $keywords,
            'website' => $website,
            'licence_key' => $licenceKey,
            'response' => isset($response['data']) ? json_encode($response['data']) : null, 
        ]);

        return response()->json($response);
    }
}
