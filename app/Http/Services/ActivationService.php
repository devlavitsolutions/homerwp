<?php

namespace App\Http\Services;

use App\Constants\Defaults;
use App\Database\Constants\ActivationCol;
use App\Database\Constants\UserCol;
use App\Database\Interfaces\IActivationDbService;
use App\Database\Interfaces\IUserDbService;
use App\Http\Constants\InputRule;
use App\Http\Constants\Messages;
use App\Http\DTOs\ActivationDTO;
use App\Http\Interfaces\IActivationService;
use DateInterval;
use DateTime;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ActivationService implements IActivationService
{
    public function __construct(
        private IUserDbService $userDbService,
        private IActivationDbService $activationDbService,
    ) {
    }

    public function validatePostActivationEndpoint(Request $request): ActivationDTO
    {
        $fields = $request->validate([
            ActivationCol::LICENSE_KEY => InputRule::EXISTING_LICENSE_KEY,
            ActivationCol::WEBSITE => InputRule::WEBSITE
        ]);

        $user = $this->userDbService->selectUserByLicenseKey(
            $fields[ActivationCol::LICENSE_KEY]
        );
        $latestActivation = $this->activationDbService->selectLatestActivationByLicenseKey(
            $fields[ActivationCol::LICENSE_KEY]
        );

        $currentDate = new DateTime();
        $requiredInterval = new DateInterval(
            Defaults::PERIOD_BETWEEN_ACTIVATIONS_FOR_FREE_USER
        );
        $oneMonthAgo = (clone $currentDate)->sub($requiredInterval);
        $latestActivationDateTime = new DateTime($latestActivation[ActivationCol::UPDATED_AT]);

        if (
            !$user[UserCol::IS_PREMIUM]
            && $latestActivation
            && $latestActivationDateTime >= $oneMonthAgo
        ) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }

        return new ActivationDTO(
            $user[UserCol::ID],
            $user[UserCol::IS_PREMIUM],
            $fields[ActivationCol::LICENSE_KEY],
            $fields[ActivationCol::WEBSITE],
        );
    }

    function validateDeleteActivationEndpoint(Request $request): ActivationDTO
    {
        $fields = $request->validate([
            ActivationCol::LICENSE_KEY => InputRule::EXISTING_LICENSE_KEY,
            ActivationCol::WEBSITE => InputRule::WEBSITE_EXISTS,
        ]);

        $user = $this->userDbService->selectUserByLicenseKey(
            $fields[ActivationCol::LICENSE_KEY]
        );

        if (!$user[UserCol::IS_PREMIUM]) {
            abort(
                Response::HTTP_FORBIDDEN,
                Messages::PREMIUM_CONTENT
            );
        }

        return new ActivationDTO(
            $user[UserCol::ID],
            $user[UserCol::IS_PREMIUM],
            $fields[ActivationCol::LICENSE_KEY],
            $fields[ActivationCol::WEBSITE],
        );
    }
}
