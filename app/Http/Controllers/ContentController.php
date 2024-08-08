<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use DateTime;
use Illuminate\Http\Request;
use App\Database\Models\Log;
use App\Database\Constants\ActivationCol;
use App\Database\Constants\LogCol;
use App\Database\Constants\TokenCol;
use App\Database\Constants\UserCol;
use App\Database\Models\Activation;
use App\Database\Models\Token;
use App\Database\Models\User;
use App\Http\Constants\InputRule;
use App\Http\Constants\Messages;
use App\Http\Contracts\IContentInterface;
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
            LogCol::KEYWORDS => InputRule::KEYWORDS,
            LogCol::WEBSITE => InputRule::WEBSITE_EXISTS,
            LogCol::LICENSE_KEY => InputRule::LICENSE_KEY,
        ]);

        $keywords = $fields[LogCol::KEYWORDS];
        $website = $fields[LogCol::WEBSITE];
        $licenseKey = $fields[LogCol::LICENSE_KEY];

        Activation
            ::where(ActivationCol::LICENSE_KEY, '=', $licenseKey)
            ->where(ActivationCol::WEBSITE, '=', $website)
            ->firstOrFail();

        $user = User::where(UserCol::LICENSE_KEY, '=', $licenseKey)->firstOrFail();
        $token = Token::where(TokenCol::USER_ID, '=', $user[UserCol::ID])->first();
        $token
            && $token[TokenCol::PAID_TOKENS] === 0
            && $token[TokenCol::FREE_TOKENS] >= Defaults::FREE_TOKENS_PER_MONTH
            && abort(Response::HTTP_PAYMENT_REQUIRED, Messages::PAYMENT_REQUIRED);

        $response = $this->contentService->getAssistantResponse($keywords);

        Log::create([
            LogCol::KEYWORDS => $keywords,
            LogCol::WEBSITE => $website,
            LogCol::LICENSE_KEY => $licenseKey,
            LogCol::RESPONSE => json_encode($response),
        ]);

        if ($token) {
            $token[TokenCol::FREE_TOKENS] < Defaults::FREE_TOKENS_PER_MONTH
                ? $token->increment(TokenCol::FREE_TOKENS)
                : $token->decrement(TokenCol::PAID_TOKENS);
        } else {
            $token = Token::create([
                TokenCol::USER_ID => $user[UserCol::ID],
                TokenCol::FREE_TOKENS => 1,
                TokenCol::PAID_TOKENS => 0,
            ]);
        }
        $token[TokenCol::LAST_USED] = new DateTime();
        $token->save();

        return response()->json($response);
    }
}
