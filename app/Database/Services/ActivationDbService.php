<?php

namespace App\Database\Services;

use App\Constants\Ops;
use App\Database\Constants\ActivationCol;
use App\Database\Interfaces\IActivationDbService;
use App\Database\Models\Activation;
use App\Http\DTOs\ActivationDTO;

class ActivationDbService implements IActivationDbService
{
    public function createActivation(ActivationDTO $activationData): Activation
    {
        return Activation::create([
            ActivationCol::LICENSE_KEY => $activationData->licenseKey,
            ActivationCol::WEBSITE => $activationData->website,
            ActivationCol::USER_ID => $activationData->userId,
        ]);
    }

    public function deleteActivation(string $licenseKey, string $website): void
    {
        $activation = Activation::where(ActivationCol::LICENSE_KEY, $licenseKey)
            ->where(ActivationCol::WEBSITE, $website)
            ->firstOrFail();

        $activation->delete();
    }

    public function selectLatestActivationByLicenseKey(string $licenseKey): ?Activation
    {
        return Activation::where(ActivationCol::LICENSE_KEY, $licenseKey)
            ->orderBy(ActivationCol::UPDATED_AT, Ops::DESC)
            ->first();
    }
}
