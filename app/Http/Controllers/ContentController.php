<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use App\Constants\Messages;
use DateTime;
use Illuminate\Http\Request;
use App\Http\Contracts\IContentInterface;
use App\Models\Log;
use App\Constants\Persist;
use App\Models\Activation;
use App\Models\Token;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;

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
        $fields = $request->validate([
            Persist::KEYWORDS => Persist::VALIDATE_KEYWORDS,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE_EXISTS,
            Persist::LICENSE_KEY => Persist::VALIDATE_LICENSE_KEY,
        ]);

        $keywords = $fields[Persist::KEYWORDS];
        $website = $fields[Persist::WEBSITE];
        $licenseKey = $fields[Persist::LICENSE_KEY];

        Activation
            ::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->where(Persist::WEBSITE, '=', $website)
            ->firstOrFail();

        $user = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->firstOrFail();
        $token = Token::where(Persist::USER_ID, '=', $user[Persist::ID])->first();
        $token
            && $token[Persist::PAID_TOKENS] === 0
            && $token[Persist::FREE_TOKENS] >= Defaults::FREE_TOKENS_PER_MONTH
            && abort(Response::HTTP_PAYMENT_REQUIRED, Messages::PAYMENT_REQUIRED);

        $response = $this->contentService->getAssistantResponse($keywords);

        Log::create([
            Persist::KEYWORDS => $keywords,
            Persist::WEBSITE => $website,
            Persist::LICENSE_KEY => $licenseKey,
            Persist::RESPONSE => json_encode($response),
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
