<?php

namespace App\Http\Controllers;

use App\Constants\Consts;
use App\Constants\Messages;
use App\Constants\Persist;
use App\Constants\Routes;
use App\Models\Activation;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use DateInterval;
use Illuminate\Http\Request;
use Nette\Utils\DateTime;

class ActivationController extends Controller
{

    // TODO
    // Return user details, along with paid and free tokens
    public function postActivation(Request $request)
    {
        $fields = $request->validate(([
            Persist::EMAIL => Persist::VALIDATE_EXISTING_EMAIL,
            Persist::LICENSE_KEY => Persist::VALIDATE_EXISTING_LICENSE_KEY,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE
        ]));

        $user = User::where(Persist::EMAIL, '=', $fields[Persist::EMAIL])->first();

        // If user does not own the license
        if ($user[Persist::LICENSE_KEY] !== $fields[Persist::LICENSE_KEY]) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::USER_NOT_OWNER_OF_KEY
            );
        }

        $latestActivation = Activation
            ::where(Persist::LICENSE_KEY, '=', $fields[Persist::LICENSE_KEY])
            ->orderBy(Persist::UPDATED_AT, Persist::DESC)
            ->first();

        $currentDate = new DateTime();
        $oneMonthAgo = (clone $currentDate)->sub(new DateInterval(Consts::ONE_MONTH_INTERVAL));

        // If user is not premium
        // and if user already activated a website
        // and if activation happened less than one month ago...
        if (
            !$user[Persist::IS_PREMIUM]
            && $latestActivation
            && new DateTime($latestActivation[Persist::UPDATED_AT]) >= $oneMonthAgo
        ) {
            // ... then throw error
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }

        // If user is not premium, first delete old activation
        if (!$user[Persist::IS_PREMIUM]) {
            Activation
                ::where(Persist::LICENSE_KEY, '=', $fields[Persist::LICENSE_KEY])
                ->delete();
        }

        // Then create new activation
        Activation::create([
            ...$fields,
            Persist::USER_ID => $user[Persist::ID],
        ]);

        return response([
            Persist::ACTIVATIONS => $fields
        ]);
    }

    public function deleteActivation(Request $request)
    {
        $fields = $request->validate([
            Persist::LICENSE_KEY => Persist::VALIDATE_EXISTING_LICENSE_KEY,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE,
        ]);

        $licenseKey = $fields[Persist::LICENSE_KEY];
        $website = $fields[Persist::WEBSITE];

        $user = User::where(Persist::LICENSE_KEY, '=', $licenseKey)->first();

        if (!$user || !$user[Persist::IS_PREMIUM]) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }

        Activation
            ::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->where(Persist::WEBSITE, '=', $website)
            ::delete();

        return response()->noContent();
    }
}
