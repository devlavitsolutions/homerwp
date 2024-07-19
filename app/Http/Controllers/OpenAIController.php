<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Log;
use App\Constants\Persist;

class OpenAIController extends Controller
{
    protected $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    public function getAssistantResponse(Request $request)
    {
        // Automatically throws a ValidationException and return a 422 Unprocessable Entity response, if not validated
        $validatedData = $request->validate([
            Persist::KEYWORDS => Persist::VALIDATE_KEYWORDS,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE,
            Persist::LICENSE_KEY => Persist::VALIDATE_LICENSE_KEY,
        ]);

        $keywords = $validatedData[Persist::KEYWORDS];
        $website = $validatedData[Persist::WEBSITE];
        $licenceKey = $validatedData[Persist::LICENSE_KEY];

        $response = $this->openAIService->getAssistantResponse($keywords);

        Log::create([
            Persist::KEYWORDS => $keywords,
            Persist::WEBSITE => $website,
            Persist::LICENSE_KEY => $licenceKey,
            Persist::RESPONSE => isset($response['data']) ? json_encode($response['data']) : null, 
        ]);

        return response()->json($response);
    }
}
