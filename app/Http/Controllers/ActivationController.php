<?php

namespace App\Http\Controllers;

use App\Constants\Defaults;
use App\Constants\Messages;
use App\Constants\Persist;
use App\Models\Activation;
use App\Models\User;
use Symfony\Component\HttpFoundation\Response;
use DateInterval;
use Illuminate\Http\Request;
use Nette\Utils\DateTime;

class ActivationController extends Controller
{
    private function getUserByLicenseKey(string $licenseKey): User
    {
        return User
            ::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->firstOrFail();
    }

    private function getLatestActivationByLicenseKey(string $licenseKey): ?Activation
    {
        return Activation
            ::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->orderBy(Persist::UPDATED_AT, Persist::DESC)
            ->first();
    }

    private function validateUserIsPremiumOrHaventActivatedRecently(
        User $user,
        ?Activation $latestActivation,
    ) {
        $currentDate = new DateTime();
        $requiredInterval = new DateInterval(Defaults::PERIOD_BETWEEN_ACTIVATIONS_FOR_FREE_USER);
        $oneMonthAgo = (clone $currentDate)->sub($requiredInterval);
        $latestActivationDateTime = new DateTime($latestActivation[Persist::UPDATED_AT]);

        if (
            !$user[Persist::IS_PREMIUM]
            && $latestActivation
            && $latestActivationDateTime >= $oneMonthAgo
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }
    }

    private function deleteActivationIfUserIsFree(User $user, $licenseKey)
    {
        if (!$user[Persist::IS_PREMIUM]) {
            Activation
                ::where(Persist::LICENSE_KEY, '=', $licenseKey)
                ->delete();
        }
    }

    public function postActivation(Request $request)
    {
        $fields = $request->validate(([
            Persist::LICENSE_KEY => Persist::VALIDATE_EXISTING_LICENSE_KEY,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE
        ]));

        $licenseKey = $fields[Persist::LICENSE_KEY];
        $user = $this->getUserByLicenseKey($licenseKey);
        $latestActivation = $this->getLatestActivationByLicenseKey($licenseKey);

        $this->validateUserIsPremiumOrHaventActivatedRecently($user, $latestActivation);

        $this->deleteActivationIfUserIsFree($user, $licenseKey);

        Activation::create([
            ...$fields,
            Persist::USER_ID => $user[Persist::ID],
        ]);

        return [
            Persist::ACTIVATIONS => $fields
        ];
    }

    public function deleteActivation(Request $request)
    {
        $fields = $request->validate([
            Persist::LICENSE_KEY => Persist::VALIDATE_EXISTING_LICENSE_KEY,
            Persist::WEBSITE => Persist::VALIDATE_WEBSITE_EXISTS,
        ]);

        $licenseKey = $fields[Persist::LICENSE_KEY];
        $website = $fields[Persist::WEBSITE];

        $user = $this->getUserByLicenseKey($licenseKey);

        if (!$user[Persist::IS_PREMIUM]) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }

        $activation = Activation
            ::where(Persist::LICENSE_KEY, '=', $licenseKey)
            ->where(Persist::WEBSITE, '=', $website)
            ->firstOrFail();

        $activation->delete();

        return response()->noContent();
    }
}
