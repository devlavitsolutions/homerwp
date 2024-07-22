<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use App\Constants\Messages;
use DateTime;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\Log;
use App\Constants\Persist;
use App\Models\Token;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

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
        $fields = $request->validate([
            Persist::KEYWORDS => Persist::VALIDATE_KEYWORDS,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE,
            Persist::LICENSE_KEY => Persist::VALIDATE_LICENSE_KEY,
        ]);

        $user = User::where(Persist::LICENSE_KEY, '=', $fields[Persist::LICENSE_KEY])->firstOrFail();

        $token = Token::where(Persist::USER_ID, '=', $user[Persist::ID])->first();

        $token
            && $token[Persist::PAID_TOKENS] === 0
            && $token[Persist::FREE_TOKENS] >= Defaults::FREE_TOKENS_PER_MONTH
            && abort(Response::HTTP_PAYMENT_REQUIRED, Messages::PAYMENT_REQUIRED);

        $keywords = $fields[Persist::KEYWORDS];
        $website = $fields[Persist::WEBSITE];
        $licenceKey = $fields[Persist::LICENSE_KEY];

        $response = $this->openAIService->getAssistantResponse($keywords);

        Log::create([
            Persist::KEYWORDS => $keywords,
            Persist::WEBSITE => $website,
            Persist::LICENSE_KEY => $licenceKey,
            Persist::RESPONSE => isset($response['data']) ? json_encode($response['data']) : null,
        ]);

        if ($token) {
            $token[Persist::FREE_TOKENS] < Defaults::FREE_TOKENS_PER_MONTH
                ? $token->increment(Persist::FREE_TOKENS)
                : $token->decrement(Persist::PAID_TOKENS);
        } else {
            $token = Token::create([
                Persist::USER_ID => $user[Persist::ID],
                Persist::FREE_TOKENS => 1,
                Persist::PAID_TOKENS => 0,
            ]);
        }
        $token[Persist::LAST_USED] = new DateTime();
        $token->save();

        return response()->json($response);
    }
}
