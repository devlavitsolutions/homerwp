<?php

namespace App\Database\Services;

use App\Constants\Ops;
use App\Database\Constants\ActivationCol;
use App\Database\Interfaces\IActivationDbService;
use App\Database\Models\Activation;

class ActivationDbService implements IActivationDbService
{
    public function selectLatestActivationByLicenseKey(string $licenseKey): ?Activation
    {
        return Activation
            ::where(ActivationCol::LICENSE_KEY, $licenseKey)
            ->orderBy(ActivationCol::UPDATED_AT, Ops::DESC)
            ->first();
    }

    public function deleteActivation(): void
    {
    }

    public function createActivation(string $licenseKey): Activation
    {
        return new Activation([]);
    }
}
