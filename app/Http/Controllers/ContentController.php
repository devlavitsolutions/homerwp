<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Contracts\IContentInterface;
use App\Models\Log;
use App\Constants\Persist;

class ContentController extends Controller
{
    protected $contentService;

    public function __construct(IContentInterface $contentService)
    {
        $this->contentService = $contentService;
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

        $response = $this->contentService->getAssistantResponse($keywords);

        Log::create([
            Persist::KEYWORDS => $keywords,
            Persist::WEBSITE => $website,
            Persist::LICENSE_KEY => $licenceKey,
            Persist::RESPONSE => json_encode($response)
        ]);

        return response()->json($response);
    }
}
