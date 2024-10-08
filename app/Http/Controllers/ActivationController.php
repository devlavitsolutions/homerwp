<?php

namespace App\Http\Controllers;

use App\Database\Interfaces\IActivationDbService;
use App\Database\Interfaces\IUserDbService;
use App\Http\Interfaces\IActivationService;
use Illuminate\Http\Request;

class ActivationController extends Controller
{
    public function __construct(
        private IUserDbService $userDbService,
        private IActivationDbService $activationDbService,
        private IActivationService $activationService,
    ) {}

    public function deleteActivation(Request $request)
    {
        $activationData = $this->activationService->validateDeleteActivationEndpoint($request);

        $this->activationDbService->deleteActivation(
            $activationData->licenseKey,
            $activationData->website,
        );

        return response()->noContent();
    }

    public function postActivation(Request $request)
    {
        $activationData = $this->activationService->validatePostActivationEndpoint($request);

        $this->activationDbService->createActivation($activationData);

        return $activationData;
    }
}
